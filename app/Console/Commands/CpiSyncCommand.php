<?php

namespace App\Console\Commands;

use App\Contracts\AdminAlertServiceInterface;
use App\Contracts\CpiProviderInterface;
use App\Dto\CpiRecord;
use App\Mail\CpiSyncFailedMail;
use App\Models\CpiCategory;
use App\Models\CpiValue;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class CpiSyncCommand extends Command
{
    protected $signature = 'cpi:sync
        {--from= : Start of the period (YYYY-MM-DD). Default: three months back, to pick up revisions}
        {--to= : End of the period (YYYY-MM-DD). Default: today}
        {--no-alert : Do not email the administrator when the sync fails}';

    protected $description = 'Fetch monthly CPI from the configured source (EMISS, Rosstat) and store it in cpi_values';

    private const VALID_VALUE_MIN = 80.0;

    private const VALID_VALUE_MAX = 130.0;

    private const ALERT_THROTTLE_KEY = 'cpi-sync-failure';

    private const ALERT_THROTTLE_HOURS = 12;

    public function __construct(private readonly AdminAlertServiceInterface $alerts)
    {
        parent::__construct();
    }

    public function handle(CpiProviderInterface $provider): int
    {
        $from = $this->option('from')
            ? Carbon::parse($this->option('from'))
            : now()->subMonths(3)->startOfMonth();
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : now();

        $this->info("Fetching CPI for {$from->toDateString()} - {$to->toDateString()}...");

        try {
            $records = $provider->fetch($from, $to);
        } catch (Throwable $exception) {
            Log::error('CPI sync failed, keeping previously stored data', [
                'message' => $exception->getMessage(),
            ]);
            $this->error("CPI request failed: {$exception->getMessage()}");
            $this->notifyAdmin($exception->getMessage(), $from, $to);

            return self::FAILURE;
        }

        if ($records->isEmpty()) {
            Log::warning('CPI sync returned no data', [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ]);
            $this->warn('The CPI source returned no data for the period. Stored values were not changed.');
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
                    'source' => $record->source,
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

        $this->alerts->send(
            new CpiSyncFailedMail($reason, $from, $to, now()),
            self::ALERT_THROTTLE_KEY,
            self::ALERT_THROTTLE_HOURS,
        );
    }

    private function isPlausible(CpiRecord $record): bool
    {
        return $record->value >= self::VALID_VALUE_MIN && $record->value <= self::VALID_VALUE_MAX;
    }
}
