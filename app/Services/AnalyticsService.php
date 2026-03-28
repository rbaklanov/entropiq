<?php

namespace App\Services;

use App\Contracts\AnalyticsServiceInterface;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\CpiCategory;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

readonly class AnalyticsService implements AnalyticsServiceInterface
{
    public function __construct(
        private InflationService $inflationService,
    ) {}

    /** {@inheritDoc} */
    public function getExpensesByCategory(int $userId, Carbon $from, Carbon $to): array
    {
        $rows = Transaction::where('user_id', $userId)
            ->expense()
            ->forPeriod($from, $to)
            ->selectRaw('category_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        $totalExpense = $rows->sum('total');

        if ($totalExpense <= 0) {
            return [];
        }

        $categoryIds = $rows->pluck('category_id')->all();
        $categories = Category::whereIn('id', $categoryIds)->get()->keyBy('id');

        return $rows->map(function (Transaction $row) use ($categories, $totalExpense) {
            $category = $categories->get($row->category_id);
            $total = (int) $row->getAttribute('total');

            return [
                'category_id' => (int) $row->category_id,
                'category_name' => $category->name,
                'category_icon' => $category->icon,
                'category_color' => $category->color,
                'total' => $total,
                'count' => (int) $row->getAttribute('count'),
                'share' => round($total / $totalExpense, 4),
            ];
        })->all();
    }

    /** {@inheritDoc} */
    public function getBalanceDynamics(int $userId, Carbon $from, Carbon $to, string $granularity = 'day'): array
    {
        $truncExpression = $granularity === 'month'
            ? "date_trunc('month', date)::date"
            : 'date';

        $dateFormat = $granularity === 'month' ? 'Y-m-01' : 'Y-m-d';

        $rows = Transaction::where('user_id', $userId)
            ->forPeriod($from, $to)
            ->selectRaw("{$truncExpression} as period_date")
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")
            ->groupByRaw($truncExpression)
            ->orderByRaw("{$truncExpression}")
            ->get();

        $cumulativeBalance = 0;
        $latestDate = $to->copy();

        return $rows->map(function ($row) use (&$cumulativeBalance, $latestDate, $dateFormat) {
            $income = (int) $row->getAttribute('income');
            $expense = (int) $row->getAttribute('expense');
            $balance = $income - $expense;
            $cumulativeBalance += $balance;

            $periodDate = Carbon::parse($row->getAttribute('period_date'));

            $realCumulativeBalance = $this->inflationService->calculateRealValue(
                $cumulativeBalance,
                $periodDate,
                $latestDate,
            );

            return [
                'date' => $periodDate->format($dateFormat),
                'income' => $income,
                'expense' => $expense,
                'balance' => $balance,
                'cumulative_balance' => $cumulativeBalance,
                'real_cumulative_balance' => $realCumulativeBalance,
            ];
        })->all();
    }

    /** {@inheritDoc} */
    public function getPersonalInflationBreakdown(int $userId, Carbon $from, Carbon $to): array
    {
        $expenses = Transaction::where('user_id', $userId)
            ->where('type', TransactionType::Expense)
            ->forPeriod($from, $to)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        $totalExpense = $expenses->sum('total');

        $personalRate = $this->inflationService->calculatePersonalInflation($userId, $from, $to);
        $officialRate = $this->inflationService->getCurrentCpi();

        if ($totalExpense <= 0) {
            return [
                'personal_rate' => $personalRate,
                'official_rate' => $officialRate,
                'breakdown' => [],
            ];
        }

        $categoryIds = $expenses->pluck('category_id')->all();
        $categories = Category::whereIn('id', $categoryIds)->get()->keyBy('id');

        $categoryMappings = CpiCategory::whereNotNull('mapping_to_app_category_id')
            ->get()
            ->keyBy('mapping_to_app_category_id');

        $breakdown = $expenses->map(function (Transaction $expense) use ($categories, $categoryMappings, $totalExpense, $from, $to, $officialRate) {
            $category = $categories->get($expense->category_id);
            $share = (float) $expense->getAttribute('total') / $totalExpense;

            $cpiCategory = $categoryMappings->get($expense->category_id);
            $categoryCpi = $cpiCategory
                ? $this->inflationService->getCpiForPeriod($from, $to, $cpiCategory->code)
                : $officialRate;

            return [
                'category_id' => (int) $expense->category_id,
                'category_name' => $category->name,
                'category_icon' => $category->icon,
                'share' => round($share, 4),
                'category_cpi' => round($categoryCpi, 4),
                'contribution' => round($share * $categoryCpi, 4),
            ];
        })->all();

        return [
            'personal_rate' => $personalRate,
            'official_rate' => $officialRate,
            'breakdown' => $breakdown,
        ];
    }

    /** {@inheritDoc} */
    public function getTrends(int $userId, Carbon $from, Carbon $to): array
    {
        $periodLengthDays = $from->diffInDays($to);
        $previousFrom = $from->copy()->subDays($periodLengthDays);
        $previousTo = $from->copy()->subDay();

        $currentExpenses = $this->getGroupedExpenses($userId, $from, $to);
        $previousExpenses = $this->getGroupedExpenses($userId, $previousFrom, $previousTo);

        $allCategoryIds = $currentExpenses->keys()
            ->merge($previousExpenses->keys())
            ->unique()
            ->all();

        $categories = Category::whereIn('id', $allCategoryIds)->get()->keyBy('id');

        $trends = [];

        foreach ($allCategoryIds as $categoryId) {
            $currentTotal = (int) ($currentExpenses->get($categoryId) ?? 0);
            $previousTotal = (int) ($previousExpenses->get($categoryId) ?? 0);
            $category = $categories->get($categoryId);

            $changePercent = null;
            $direction = 'stable';

            if ($previousTotal > 0) {
                $changePercent = round(($currentTotal - $previousTotal) / $previousTotal * 100, 1);

                if ($changePercent > 0) {
                    $direction = 'up';
                } elseif ($changePercent < 0) {
                    $direction = 'down';
                }
            } elseif ($currentTotal > 0) {
                $direction = 'new';
            }

            $trends[] = [
                'category_id' => (int) $categoryId,
                'category_name' => $category->name,
                'category_icon' => $category->icon,
                'current_total' => $currentTotal,
                'previous_total' => $previousTotal,
                'change_percent' => $changePercent,
                'direction' => $direction,
            ];
        }

        usort($trends, fn (array $a, array $b) => $b['current_total'] <=> $a['current_total']);

        return $trends;
    }

    /**
     * @return Collection<int, int>
     */
    private function getGroupedExpenses(int $userId, Carbon $from, Carbon $to): Collection
    {
        return Transaction::where('user_id', $userId)
            ->expense()
            ->forPeriod($from, $to)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');
    }
}
