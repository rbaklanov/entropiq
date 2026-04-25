<?php

namespace App\Rules\Advice;

use App\Contracts\AdviceRuleInterface;
use App\Dto\AdvicePayload;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;

class CategorySpikeRule implements AdviceRuleInterface
{
    private const SPIKE_THRESHOLD = 0.20;

    private const LOOKBACK_MONTHS = 3;

    public function evaluate(User $user): ?AdvicePayload
    {
        $now = Carbon::now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $avgStart = $currentMonthStart->copy()->subMonths(self::LOOKBACK_MONTHS);
        $avgEnd = $currentMonthStart->copy()->subDay();

        $currentByCategory = $this->expensesByCategory($user->id, $currentMonthStart, $currentMonthEnd);
        $historicalByCategory = $this->expensesByCategory($user->id, $avgStart, $avgEnd);

        $biggestSpike = null;
        $biggestSpikeRatio = 0.0;

        foreach ($currentByCategory as $categoryId => $currentTotal) {
            $historicalTotal = $historicalByCategory[$categoryId] ?? 0;
            $avgMonthly = $historicalTotal / self::LOOKBACK_MONTHS;

            if ($avgMonthly <= 0) {
                continue;
            }

            $growthRatio = ($currentTotal - $avgMonthly) / $avgMonthly;

            if ($growthRatio > self::SPIKE_THRESHOLD && $growthRatio > $biggestSpikeRatio) {
                $biggestSpikeRatio = $growthRatio;
                $biggestSpike = [
                    'category_id' => $categoryId,
                    'current_total' => $currentTotal,
                    'avg_monthly' => (int) round($avgMonthly),
                    'growth_percent' => (int) round($growthRatio * 100),
                ];
            }
        }

        if (! $biggestSpike) {
            return null;
        }

        $category = \App\Models\Category::find($biggestSpike['category_id']);
        $categoryName = $category ? $category->localizedName() : '—';

        return new AdvicePayload(
            ruleKey: 'category_spike',
            title: "Рост расходов в категории «{$categoryName}»",
            body: "Расходы в категории «{$categoryName}» выросли на {$biggestSpike['growth_percent']}% по сравнению со средним за 3 месяца. Средний расход: {$biggestSpike['avg_monthly']} ₽/мес, в этом месяце: {$biggestSpike['current_total']} ₽.",
            basisData: [
                'rule' => 'category_spike',
                'category_id' => $biggestSpike['category_id'],
                'category_name' => $categoryName,
                'current_total' => $biggestSpike['current_total'],
                'avg_monthly' => $biggestSpike['avg_monthly'],
                'growth_percent' => $biggestSpike['growth_percent'],
            ],
        );
    }

    /**
     * @return array<int, int>
     */
    private function expensesByCategory(int $userId, Carbon $from, Carbon $to): array
    {
        return Transaction::where('user_id', $userId)
            ->where('type', TransactionType::Expense)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id')
            ->map(fn ($val) => (int) $val)
            ->all();
    }
}
