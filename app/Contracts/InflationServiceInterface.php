<?php

namespace App\Contracts;

use Illuminate\Support\Carbon;

interface InflationServiceInterface
{
    public function getCurrentCpi(): float;

    public function getCpiForPeriod(Carbon $from, Carbon $to, string $categoryCode = 'TOTAL'): float;

    public function getCpiByCategory(string $categoryCode, Carbon $period): ?float;

    public function calculateRealValue(int $nominalAmount, Carbon $fromDate, Carbon $toDate): int;

    public function calculatePersonalInflation(int $userId, Carbon $from, Carbon $to): float;

    public function calculateInflationLoss(int $userId, Carbon $from, Carbon $to): int;
}
