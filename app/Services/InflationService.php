<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\CpiCategory;
use App\Models\CpiValue;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class InflationService
{
    private const TOTAL_CATEGORY_CODE = 'TOTAL';

    private const DEFAULT_ANNUAL_INFLATION = 0.095;

    /**
     * Current overall CPI — cumulative index for the trailing 12 months.
     * Returns annual inflation rate as a decimal (e.g. 0.095 for 9.5%).
     */
    public function getCurrentCpi(): float
    {
        $to = CpiValue::where('category_code', self::TOTAL_CATEGORY_CODE)
            ->orderByDesc('period')
            ->value('period');

        if (! $to) {
            return self::DEFAULT_ANNUAL_INFLATION;
        }

        $to = Carbon::parse($to);
        $from = $to->copy()->subMonths(11);

        return $this->getCpiForPeriod($from, $to);
    }

    /**
     * Cumulative CPI for an arbitrary period as annual inflation rate (decimal).
     * Multiplies monthly indices to get compound rate.
     */
    public function getCpiForPeriod(Carbon $from, Carbon $to, string $categoryCode = self::TOTAL_CATEGORY_CODE): float
    {
        $values = CpiValue::where('category_code', $categoryCode)
            ->whereBetween('period', [$from->startOfMonth(), $to->startOfMonth()])
            ->orderBy('period')
            ->pluck('value');

        if ($values->isEmpty()) {
            return self::DEFAULT_ANNUAL_INFLATION;
        }

        $compoundIndex = $this->compoundIndex($values);
        $months = max(1, $values->count());

        return $this->annualizeRate($compoundIndex, $months);
    }

    /**
     * CPI for a specific category in a specific period.
     * Returns monthly index value (e.g. 100.84).
     */
    public function getCpiByCategory(string $categoryCode, Carbon $period): ?float
    {
        $value = CpiValue::where('category_code', $categoryCode)
            ->where('period', $period->startOfMonth())
            ->value('value');

        return $value !== null ? (float) $value : null;
    }

    /**
     * Convert nominal amount to real value (adjusted for inflation).
     *
     * Real = Nominal / compound_index
     */
    public function calculateRealValue(int $nominalAmount, Carbon $fromDate, Carbon $toDate): int
    {
        $values = CpiValue::where('category_code', self::TOTAL_CATEGORY_CODE)
            ->whereBetween('period', [$fromDate->copy()->startOfMonth(), $toDate->copy()->startOfMonth()])
            ->orderBy('period')
            ->pluck('value');

        if ($values->isEmpty()) {
            return $nominalAmount;
        }

        $compoundIndex = $this->compoundIndex($values);

        return (int) round($nominalAmount / $compoundIndex);
    }

    /**
     * Personal inflation rate based on user's spending structure.
     *
     * Formula: Σ(share_i × cpi_i) where share_i is the user's spending
     * share in category i, and cpi_i is the annualized inflation for that category.
     *
     * Returns annual rate as decimal (e.g. 0.102 for 10.2%).
     */
    public function calculatePersonalInflation(int $userId, Carbon $from, Carbon $to): float
    {
        $expenses = Transaction::where('user_id', $userId)
            ->where('type', TransactionType::Expense)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->get();

        $totalExpense = $expenses->sum('total');

        if ($totalExpense <= 0) {
            return $this->getCurrentCpi();
        }

        $categoryMappings = CpiCategory::whereNotNull('mapping_to_app_category_id')
            ->get()
            ->keyBy('mapping_to_app_category_id');

        $weightedInflation = 0.0;
        $mappedShare = 0.0;

        foreach ($expenses as $expense) {
            $share = (float) $expense->getAttribute('total') / $totalExpense;
            $cpiCategory = $categoryMappings->get($expense->category_id);

            if ($cpiCategory) {
                $categoryInflation = $this->getCpiForPeriod($from, $to, $cpiCategory->code);
                $weightedInflation += $share * $categoryInflation;
                $mappedShare += $share;
            }
        }

        if ($mappedShare < 0.01) {
            return $this->getCurrentCpi();
        }

        $unmappedShare = 1.0 - $mappedShare;

        if ($unmappedShare > 0) {
            $totalInflation = $this->getCpiForPeriod($from, $to);
            $weightedInflation += $unmappedShare * $totalInflation;
        }

        return $weightedInflation;
    }

    /**
     * Total purchasing power lost to inflation over a period.
     *
     * Loss = total_savings × (1 - 1/compound_index)
     *
     * Returns amount in kopecks.
     */
    public function calculateInflationLoss(int $userId, Carbon $from, Carbon $to): int
    {
        $income = Transaction::where('user_id', $userId)
            ->where('type', TransactionType::Income)
            ->whereBetween('date', [$from, $to])
            ->sum('amount');

        $expense = Transaction::where('user_id', $userId)
            ->where('type', TransactionType::Expense)
            ->whereBetween('date', [$from, $to])
            ->sum('amount');

        $savings = (int) ($income - $expense);

        if ($savings <= 0) {
            return 0;
        }

        $values = CpiValue::where('category_code', self::TOTAL_CATEGORY_CODE)
            ->whereBetween('period', [$from->copy()->startOfMonth(), $to->copy()->startOfMonth()])
            ->orderBy('period')
            ->pluck('value');

        if ($values->isEmpty()) {
            return 0;
        }

        $compoundIndex = $this->compoundIndex($values);

        return (int) round($savings * (1 - 1 / $compoundIndex));
    }

    /**
     * Multiply monthly indices (each like 100.84) into a compound multiplier.
     * E.g. 100.84 × 100.46 / 100^2 = 1.013...
     *
     * @param  Collection<int, string|float>  $monthlyValues
     */
    private function compoundIndex(Collection $monthlyValues): float
    {
        $product = 1.0;

        foreach ($monthlyValues as $value) {
            $product *= (float) $value / 100.0;
        }

        return $product;
    }

    /**
     * Convert a compound multiplier over N months into an annualized rate (decimal).
     * E.g. compound 1.095 over 12 months → 0.095
     */
    private function annualizeRate(float $compoundIndex, int $months): float
    {
        if ($months <= 0 || $compoundIndex <= 0) {
            return self::DEFAULT_ANNUAL_INFLATION;
        }

        return pow($compoundIndex, 12.0 / $months) - 1.0;
    }
}
