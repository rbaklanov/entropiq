<?php

namespace App\Rules\Advice;

use App\Contracts\AdviceRuleInterface;
use App\Dto\AdvicePayload;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;

class SavingsOptimizationRule implements AdviceRuleInterface
{
    private const LOOKBACK_MONTHS = 3;

    private const MIN_REDUCIBLE_SHARE = 0.15;

    private const SUGGESTED_CUT_PERCENT = 10;

    /**
     * Category names (ru) treated as discretionary / reducible spending.
     *
     * @var list<string>
     */
    private const DISCRETIONARY_CATEGORIES = [
        'Кафе и рестораны',
        'Развлечения',
        'Одежда и обувь',
        'Подписки',
    ];

    public function evaluate(User $user): ?AdvicePayload
    {
        $now = Carbon::now();
        $from = $now->copy()->subMonths(self::LOOKBACK_MONTHS)->startOfMonth();
        $to = $now->copy()->endOfMonth();

        $expensesByCategory = Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::Expense)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->get();

        $totalExpense = $expensesByCategory->sum('total');

        if ($totalExpense <= 0) {
            return null;
        }

        /** @var list<int> $discretionaryIds */
        $discretionaryIds = Category::where('is_system', true)
            ->where('type', TransactionType::Expense)
            ->get()
            ->filter(function (Category $cat) {
                $name = $cat->name['ru'] ?? '';

                return in_array($name, self::DISCRETIONARY_CATEGORIES, true);
            })
            ->pluck('id')
            ->all();

        $discretionaryTotal = $expensesByCategory
            ->filter(fn ($row) => in_array($row->category_id, $discretionaryIds, true))
            ->sum('total');

        $discretionaryShare = $discretionaryTotal / $totalExpense;

        if ($discretionaryShare < self::MIN_REDUCIBLE_SHARE) {
            return null;
        }

        $potentialSaving = (int) round($discretionaryTotal * self::SUGGESTED_CUT_PERCENT / 100);
        $monthlySaving = (int) round($potentialSaving / self::LOOKBACK_MONTHS);
        $discretionaryPercent = (int) round($discretionaryShare * 100);

        return new AdvicePayload(
            ruleKey: 'savings_optimization',
            title: 'Потенциальная экономия',
            body: "Необязательные расходы (кафе, развлечения, одежда, подписки) составляют {$discretionaryPercent}% от общих трат. Сокращение на 10% сэкономит ~{$monthlySaving} ₽/мес.",
            basisData: [
                'rule' => 'savings_optimization',
                'discretionary_total' => (int) $discretionaryTotal,
                'discretionary_share_percent' => $discretionaryPercent,
                'total_expense' => (int) $totalExpense,
                'potential_saving' => $potentialSaving,
                'monthly_saving' => $monthlySaving,
                'period_months' => self::LOOKBACK_MONTHS,
            ],
        );
    }
}
