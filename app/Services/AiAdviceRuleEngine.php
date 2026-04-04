<?php

namespace App\Services;

use App\Contracts\AdviceRuleInterface;
use App\Dto\AdvicePayload;
use App\Models\User;
use App\Rules\Advice\CategorySpikeRule;
use App\Rules\Advice\GoalBehindScheduleRule;
use App\Rules\Advice\OverspendingRule;
use App\Rules\Advice\SavingsOptimizationRule;
use App\Rules\Advice\UnusualTransactionRule;

class AiAdviceRuleEngine
{
    /** @var list<AdviceRuleInterface> */
    private readonly array $rules;

    public function __construct(
        CategorySpikeRule $categorySpikeRule,
        OverspendingRule $overspendingRule,
        GoalBehindScheduleRule $goalBehindScheduleRule,
        UnusualTransactionRule $unusualTransactionRule,
        SavingsOptimizationRule $savingsOptimizationRule,
    ) {
        $this->rules = [
            $categorySpikeRule,
            $overspendingRule,
            $goalBehindScheduleRule,
            $unusualTransactionRule,
            $savingsOptimizationRule,
        ];
    }

    /**
     * @return list<AdvicePayload>
     */
    public function evaluate(User $user): array
    {
        $payloads = [];

        foreach ($this->rules as $rule) {
            $result = $rule->evaluate($user);

            if ($result !== null) {
                $payloads[] = $result;
            }
        }

        return $payloads;
    }
}
