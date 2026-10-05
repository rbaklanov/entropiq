<?php

namespace App\Console\Commands;

use App\Contracts\AdminAlertServiceInterface;
use App\Contracts\InflationServiceInterface;
use App\Mail\CpiDataStaleMail;
use Illuminate\Console\Command;

class CpiCheckFreshnessCommand extends Command
{
    protected $signature = 'cpi:check-freshness
        {--no-alert : Do not email the administrator when the data is stale}';

    protected $description = 'Check that the latest published CPI month is not older than allowed and alert the administrator';

    private const MAX_LAG_MONTHS = 2;

    private const ALERT_THROTTLE_KEY = 'cpi-data-stale';

    private const ALERT_THROTTLE_HOURS = 24;

    public function handle(InflationServiceInterface $inflation, AdminAlertServiceInterface $alerts): int
    {
        $expectedAtLeast = now()->subMonths(self::MAX_LAG_MONTHS)->startOfMonth();
        $latest = $inflation->latestPublishedPeriod();

        if ($latest !== null && $latest->gte($expectedAtLeast)) {
            $this->info("CPI is fresh: latest month {$latest->format('Y-m')}, expected not earlier than {$expectedAtLeast->format('Y-m')}.");

            return self::SUCCESS;
        }

        $latestLabel = $latest?->format('Y-m') ?? 'none';
        $this->error("CPI is stale: latest month {$latestLabel}, expected not earlier than {$expectedAtLeast->format('Y-m')}.");

        if (! $this->option('no-alert')) {
            $alerts->send(
                new CpiDataStaleMail($latest, $expectedAtLeast, now()),
                self::ALERT_THROTTLE_KEY,
                self::ALERT_THROTTLE_HOURS,
            );
        }

        return self::FAILURE;
    }
}
