<?php

namespace App\Console\Commands;

use App\Contracts\CpiProviderInterface;
use App\Dto\CpiRecord;
use App\Mail\CpiSyncFailedMail;
use App\Models\CpiCategory;
use App\Models\CpiValue;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CpiSyncCommand extends Command
{
    protected $signature = 'cpi:sync
        {--from= : Start of the period (YYYY-MM-DD). Default: three months back, to pick up revisions}
        {--to= : End of the period (YYYY-MM-DD). Default: today}
        {--no-alert : Do not email the administrator when the sync fails}';

    protected $description = 'Fetch monthly CPI from EMISS (fedstat.ru) and store it in cpi_values';

    private const SOURCE = 'emiss';

    private const VALID_VALUE_MIN = 80.0;

    private const VALID_VALUE_MAX = 130.0;

    private const ALERT_THROTTLE_KEY = 'cpi-sync:failure-alert-sent';

    private const ALERT_THROTTLE_HOURS = 12;

    public function handle(CpiProviderInterface $provider): int
    {
        $from = $this->option('from')
            ? Carbon::parse($this->option('from'))
            : now()->subMonths(3)->startOfMonth();
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : now();

        $this->info("Fetching CPI from EMISS for {$from->toDateString()} - {$to->toDateString()}...");

        try {
            $records = $provider->fetch($from, $to);
        } catch (Throwable $exception) {
            Log::error('CPI sync from EMISS failed, keeping previously stored data', [
                'message' => $exception->getMessage(),
            ]);
            $this->error("EMISS request failed: {$exception->getMessage()}");
            $this->notifyAdmin($exception->getMessage(), $from, $to);

            return self::FAILURE;
        }

        if ($records->isEmpty()) {
            Log::warning('CPI sync from EMISS returned no data', [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ]);
            $this->warn('EMISS returned no data for the period. Stored values were not changed.');
            $this->notifyAdmin(__('cpi_alert.reason_empty'), $from, $to);

            return self::FAILURE;
        }

        $knownCodes = CpiCategory::pluck('code')->all();
        $stored = 0;
        $invalid = 0;
        $unknownCategory = 0;

        foreach ($records as $record) {
            if (! $this->isPlausible($record)) {
                $invalid++;

                continue;
            }

            if (! in_array($record->categoryCode, $knownCodes, true)) {
                $unknownCategory++;

                continue;
            }

            CpiValue::updateOrCreate(
                [
                    'period' => $record->period->toDateString(),
                    'category_code' => $record->categoryCode,
                ],
                [
                    'value' => $record->value,
                    'source' => self::SOURCE,
                ],
            );

            $stored++;
        }

        $latest = $records->max(fn (CpiRecord $record) => $record->period);

        $this->info("CPI: {$stored} stored, {$invalid} invalid, {$unknownCategory} with unknown category. Latest month: {$latest?->format('Y-m')}.");

        if ($unknownCategory > 0) {
            $this->warn('Run `cpi:import --categories` to load CPI categories first.');
        }

        if ($stored === 0) {
            $this->notifyAdmin(__('cpi_alert.reason_nothing_stored'), $from, $to);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function notifyAdmin(string $reason, Carbon $from, Carbon $to): void
    {
        if ($this->option('no-alert')) {
            return;
        }

        $recipient = config('services.admin.email');

        if (! $recipient) {
            Log::warning('ADMIN_EMAIL is not set, CPI sync failure alert was not sent');

            return;
        }

        if (! Cache::add(self::ALERT_THROTTLE_KEY, true, now()->addHours(self::ALERT_THROTTLE_HOURS))) {
            return;
        }

        try {
            Mail::to($recipient)->send(new CpiSyncFailedMail($reason, $from, $to, now()));
        } catch (Throwable $exception) {
            Cache::forget(self::ALERT_THROTTLE_KEY);
            Log::error('Unable to send CPI sync failure alert', ['message' => $exception->getMessage()]);
        }
    }

    private function isPlausible(CpiRecord $record): bool
    {
        return $record->value >= self::VALID_VALUE_MIN && $record->value <= self::VALID_VALUE_MAX;
    }
}
