<?php

namespace App\Contracts;

use Illuminate\Support\Carbon;

interface InflationServiceInterface
{
    /** @return array<string, float> */
    public function getCpiForPeriod(Carbon $from, Carbon $to): array;

    public function adjustForInflation(int $amountInCents, Carbon $from, Carbon $to): int;

    public function calculatePersonalInflation(int $userId, Carbon $from, Carbon $to): float;
}
