<?php

namespace App\Services;

use App\Contracts\InflationServiceInterface;
use Illuminate\Support\Carbon;

class InflationService implements InflationServiceInterface
{
    /** @return array<string, float> */
    public function getCpiForPeriod(Carbon $from, Carbon $to): array
    {
        return [];
    }

    public function adjustForInflation(int $amountInCents, Carbon $from, Carbon $to): int
    {
        return $amountInCents;
    }

    public function calculatePersonalInflation(int $userId, Carbon $from, Carbon $to): float
    {
        return 0.0;
    }
}
