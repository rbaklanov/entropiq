<?php

namespace App\Services;

use App\Contracts\InflationServiceInterface;
use App\Enums\TransactionType;
use App\Models\CpiCategory;
use App\Models\CpiValue;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class InflationService implements InflationServiceInterface
{
    private const TOTAL_CATEGORY_CODE = 'TOTAL';

    private const DEFAULT_ANNUAL_INFLATION = 0.095;

    private const MIN_MONTHS_FOR_CATEGORY_RATE = 6;

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
     * Annual rate of a category over the last 12 published months, the same window
     * as getCurrentCpi(). Falls back to the overall rate when the category has too
     * few published months in that window to give a stable annual figure.
     */
    public function getCurrentCategoryCpi(string $categoryCode): float
    {
        $to = $this->latestPublishedPeriod();

        if ($categoryCode === self::TOTAL_CATEGORY_CODE || $to === null) {
            return $this->getCurrentCpi();
        }

        $values = CpiValue::where('category_code', $categoryCode)
            ->whereBetween('period', [$to->copy()->subMonths(11), $to])
            ->orderBy('period')
            ->pluck('value');

        if ($values->count() < self::MIN_MONTHS_FOR_CATEGORY_RATE) {
            return $this->getCurrentCpi();
        }

        return $this->annualizeRate($this->compoundIndex($values), $values->count());
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
     * Real = Nominal / compound_index. Months after the latest published CPI
     * are estimated, see monthlyIndicesForPeriod().
     */
    public function calculateRealValue(int $nominalAmount, Carbon $fromDate, Carbon $toDate): int
    {
        $values = $this->monthlyIndicesForPeriod($fromDate, $toDate);

        if ($values->isEmpty()) {
            return $nominalAmount;
        }

        $compoundIndex = $this->compoundIndex($values);

        return (int) round($nominalAmount / $compoundIndex);
    }

    public function latestPublishedPeriod(): ?Carbon
    {
        $period = CpiValue::where('category_code', self::TOTAL_CATEGORY_CODE)->max('period');

        return $period ? Carbon::parse($period)->startOfMonth() : null;
    }

    public function hasEstimatedMonths(Carbon $from, Carbon $to): bool
    {
        $latest = $this->latestPublishedPeriod();

        return $latest !== null && $to->copy()->startOfMonth()->gt($latest);
    }

    /**
     * Personal inflation rate based on user's spending structure.
     *
     * Formula: Σ(share_i × cpi_i) where share_i is the user's spending
     * share in category i over the selected period, and cpi_i is the annual
     * inflation of that category over the last 12 published months.
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
                $categoryInflation = $this->getCurrentCategoryCpi($cpiCategory->code);
                $weightedInflation += $share * $categoryInflation;
                $mappedShare += $share;
            }
        }

        if ($mappedShare < 0.01) {
            return $this->getCurrentCpi();
        }

        $unmappedShare = 1.0 - $mappedShare;

        if ($unmappedShare > 0) {
            $totalInflation = $this->getCurrentCpi();
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

        $values = $this->monthlyIndicesForPeriod($from, $to);

        if ($values->isEmpty()) {
            return 0;
        }

        $compoundIndex = $this->compoundIndex($values);

        return (int) round($savings * (1 - 1 / $compoundIndex));
    }

    /**
     * Monthly TOTAL indices for the months of a period. Months after the latest
     * published CPI (Rosstat publishes with a lag) are filled with the geometric
     * mean of the last 12 published months. Estimates are never stored.
     *
     * @return Collection<int, float>
     */
    private function monthlyIndicesForPeriod(Carbon $from, Carbon $to): Collection
    {
        $first = $from->copy()->startOfMonth();
        $last = $to->copy()->startOfMonth();

        $published = CpiValue::where('category_code', self::TOTAL_CATEGORY_CODE)
            ->whereBetween('period', [$first, $last])
            ->orderBy('period')
            ->get();

        $values = $published->map(fn (CpiValue $cpi) => (float) $cpi->value);
        $latest = $this->latestPublishedPeriod();

        if ($latest === null || $last->lte($latest)) {
            return $values;
        }

        $estimate = $this->estimatedMonthlyIndex();
        $firstEstimated = $latest->copy()->addMonth()->max($first);
        $estimatedMonths = (int) $firstEstimated->diffInMonths($last) + 1;

        return $values->concat(array_fill(0, $estimatedMonths, $estimate));
    }

    private function estimatedMonthlyIndex(): float
    {
        $recent = CpiValue::where('category_code', self::TOTAL_CATEGORY_CODE)
            ->orderByDesc('period')
            ->limit(12)
            ->pluck('value');

        return pow($this->compoundIndex($recent), 1 / $recent->count()) * 100.0;
    }

    /**
     * Multiply monthly indices (each like 100.84) into a compound multiplier.
     * E.g. 100.84 × 100.46 / 100^2 = 1.013...
     *
     * @param  Collection<int, float|string>|Collection<int, float>  $monthlyValues
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
