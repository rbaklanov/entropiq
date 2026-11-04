<?php

namespace App\Contracts;

use Illuminate\Support\Carbon;

interface InflationServiceInterface
{
    public function getCurrentCpi(): float;

    public function getCurrentCategoryCpi(string $categoryCode): float;

    public function getCpiForPeriod(Carbon $from, Carbon $to, string $categoryCode = 'TOTAL'): float;

    public function getCpiByCategory(string $categoryCode, Carbon $period): ?float;

    /**
     * Annual inflation rate per app expense category, keyed by category id.
     * Categories without a mapped CPI group are absent.
     *
     * @return array<int, float>
     */
    public function getAppCategoryRates(): array;

    public function calculateRealValue(int $nominalAmount, Carbon $fromDate, Carbon $toDate): int;

    public function latestPublishedPeriod(): ?Carbon;

    public function hasEstimatedMonths(Carbon $from, Carbon $to): bool;

    public function calculatePersonalInflation(int $userId, Carbon $from, Carbon $to): float;

    public function calculateInflationLoss(int $userId, Carbon $from, Carbon $to): int;
}
