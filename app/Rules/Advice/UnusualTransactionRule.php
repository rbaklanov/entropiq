<?php

namespace App\Rules\Advice;

use App\Contracts\AdviceRuleInterface;
use App\Dto\AdvicePayload;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;

class UnusualTransactionRule implements AdviceRuleInterface
{
    private const LOOKBACK_MONTHS = 3;

    private const MULTIPLIER_THRESHOLD = 3.0;

    private const MIN_TRANSACTIONS_FOR_STATS = 5;

    public function evaluate(User $user): ?AdvicePayload
    {
        $now = Carbon::now();
        $weekAgo = $now->copy()->subWeek();
        $statsStart = $now->copy()->subMonths(self::LOOKBACK_MONTHS)->startOfMonth();

        $recentTransactions = Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::Expense)
            ->where('date', '>=', $weekAgo)
            ->orderByDesc('amount')
            ->limit(20)
            ->get();

        if ($recentTransactions->isEmpty()) {
            return null;
        }

        $historicalStats = Transaction::where('user_id', $user->id)
            ->where('type', TransactionType::Expense)
            ->where('date', '<', $weekAgo)
            ->where('date', '>=', $statsStart)
            ->selectRaw('category_id, AVG(amount) as avg_amount, STDDEV(amount) as stddev_amount, COUNT(*) as cnt')
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        $biggestAnomaly = null;
        $biggestMultiplier = 0.0;

        foreach ($recentTransactions as $transaction) {
            $stats = $historicalStats->get($transaction->category_id);

            if (! $stats || (int) $stats->getAttribute('cnt') < self::MIN_TRANSACTIONS_FOR_STATS) {
                continue;
            }

            $avg = (float) $stats->getAttribute('avg_amount');
            $stddev = (float) $stats->getAttribute('stddev_amount');
            $threshold = $avg + max($stddev, $avg) * self::MULTIPLIER_THRESHOLD;

            if ($transaction->amount <= $threshold) {
                continue;
            }

            $multiplier = $avg > 0 ? $transaction->amount / $avg : 0;

            if ($multiplier > $biggestMultiplier) {
                $biggestMultiplier = $multiplier;
                $biggestAnomaly = $transaction;
            }
        }

        if (! $biggestAnomaly) {
            return null;
        }

        $category = Category::find($biggestAnomaly->category_id);
        $categoryName = $category ? ($category->name['ru'] ?? $category->name['en'] ?? '—') : '—';
        $multiplierRounded = round($biggestMultiplier, 1);

        return new AdvicePayload(
            ruleKey: 'unusual_transaction',
            title: 'Крупная нетипичная транзакция',
            body: "Обнаружена транзакция на {$biggestAnomaly->amount} ₽ в категории «{$categoryName}». Это в {$multiplierRounded}x больше вашего среднего расхода в этой категории.",
            basisData: [
                'rule' => 'unusual_transaction',
                'transaction_id' => $biggestAnomaly->id,
                'amount' => $biggestAnomaly->amount,
                'category_id' => $biggestAnomaly->category_id,
                'category_name' => $categoryName,
                'multiplier' => $multiplierRounded,
                'date' => $biggestAnomaly->date->toDateString(),
            ],
        );
    }
}
