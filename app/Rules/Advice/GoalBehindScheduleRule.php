<?php

namespace App\Rules\Advice;

use App\Contracts\AdviceRuleInterface;
use App\Dto\AdvicePayload;
use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Support\Carbon;

class GoalBehindScheduleRule implements AdviceRuleInterface
{
    public function evaluate(User $user): ?AdvicePayload
    {
        $goals = Goal::where('user_id', $user->id)
            ->where('status', GoalStatus::Active)
            ->whereNotNull('target_date')
            ->get();

        $worstGoal = null;
        $worstLag = 0.0;

        foreach ($goals as $goal) {
            $lag = $this->calculateLag($goal);

            if ($lag !== null && $lag > $worstLag) {
                $worstLag = $lag;
                $worstGoal = $goal;
            }
        }

        if (! $worstGoal || $worstLag < 0.10) {
            return null;
        }

        $expectedPercent = (int) round($this->expectedProgress($worstGoal) * 100);
        $actualPercent = (int) round($worstGoal->progressPercent());
        $lagPercent = (int) round($worstLag * 100);

        return new AdvicePayload(
            ruleKey: 'goal_behind_schedule',
            title: "Цель «{$worstGoal->name}» отстаёт от плана",
            body: "Ожидаемый прогресс: {$expectedPercent}%, фактический: {$actualPercent}% (отставание {$lagPercent}%). Рассмотрите увеличение ежемесячных взносов.",
            basisData: [
                'rule' => 'goal_behind_schedule',
                'goal_id' => $worstGoal->id,
                'goal_name' => $worstGoal->name,
                'expected_percent' => $expectedPercent,
                'actual_percent' => $actualPercent,
                'lag_percent' => $lagPercent,
            ],
        );
    }

    private function calculateLag(Goal $goal): ?float
    {
        $expected = $this->expectedProgress($goal);

        if ($expected <= 0) {
            return null;
        }

        $actual = $goal->target_amount > 0
            ? $goal->current_amount / $goal->target_amount
            : 0;

        $lag = $expected - $actual;

        return $lag > 0 ? $lag : null;
    }

    private function expectedProgress(Goal $goal): float
    {
        $totalDays = $goal->started_at->diffInDays($goal->target_date);

        if ($totalDays <= 0) {
            return 1.0;
        }

        $elapsedDays = $goal->started_at->diffInDays(Carbon::now());

        return min(1.0, $elapsedDays / $totalDays);
    }
}
