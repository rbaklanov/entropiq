<?php

namespace App\Console\Commands;

use App\Contracts\CpiProviderInterface;
use App\Dto\CpiRecord;
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
        {--to= : End of the period (YYYY-MM-DD). Default: today}';

    protected $description = 'Fetch monthly CPI from EMISS (fedstat.ru) and store it in cpi_values';

    private const SOURCE = 'emiss';

    private const VALID_VALUE_MIN = 80.0;

    private const VALID_VALUE_MAX = 130.0;

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

            return self::FAILURE;
        }

        if ($records->isEmpty()) {
            Log::warning('CPI sync from EMISS returned no data', [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ]);
            $this->warn('EMISS returned no data for the period. Stored values were not changed.');

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

        return $stored > 0 ? self::SUCCESS : self::FAILURE;
    }

    private function isPlausible(CpiRecord $record): bool
    {
        return $record->value >= self::VALID_VALUE_MIN && $record->value <= self::VALID_VALUE_MAX;
    }
}
