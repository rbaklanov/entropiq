<?php

namespace App\Rules\Advice;

use App\Contracts\AdviceRuleInterface;
use App\Dto\AdvicePayload;
use App\Models\User;
use App\Services\TransactionService;
use App\Support\Money;
use Illuminate\Support\Carbon;

class OverspendingRule implements AdviceRuleInterface
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    public function evaluate(User $user): ?AdvicePayload
    {
        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        $summary = $this->transactionService->getSummary($user->id, $from, $to);

        if ($summary['income'] <= 0 || $summary['expense'] <= $summary['income']) {
            return null;
        }

        $overspend = abs($summary['balance']);
        $overspendPercent = (int) round($overspend / $summary['income'] * 100);
        $overspendText = Money::amount($overspend);
        $incomeText = Money::amount($summary['income']);
        $expenseText = Money::amount($summary['expense']);

        return new AdvicePayload(
            ruleKey: 'overspending',
            title: 'Расходы превышают доходы',
            body: "В этом месяце расходы превысили доходы на {$overspendText} ₽ ({$overspendPercent}%). Доход: {$incomeText} ₽, расход: {$expenseText} ₽. Рекомендуем пересмотреть текущие траты.",
            basisData: [
                'rule' => 'overspending',
                'income' => $summary['income'],
                'expense' => $summary['expense'],
                'overspend' => $overspend,
                'overspend_percent' => $overspendPercent,
            ],
        );
    }
}
