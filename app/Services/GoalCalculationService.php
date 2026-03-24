<?php

namespace App\Services;

use App\Models\Goal;
use Illuminate\Support\Carbon;

class GoalCalculationService
{
    private const DEFAULT_ANNUAL_INFLATION = 0.095;

    private const SCENARIO_OPTIMISTIC_INFLATION = 0.05;

    private const SCENARIO_PESSIMISTIC_INFLATION = 0.15;

    private const MONTHS_HARD_LIMIT = 1200;

    /**
     * Required monthly payment without inflation.
     *
     * PMT = remaining / n
     */
    public function requiredMonthlyPayment(int $targetAmount, int $currentAmount, int $monthsLeft): int
    {
        $remaining = $targetAmount - $currentAmount;

        if ($remaining <= 0 || $monthsLeft <= 0) {
            return 0;
        }

        return (int) ceil($remaining / $monthsLeft);
    }

    /**
     * Required monthly payment with inflation adjustment.
     *
     * GOAL_nominal = remaining × (1 + i)^(n/12)
     * PMT = GOAL_nominal / n
     */
    public function requiredMonthlyPaymentWithInflation(
        int $targetAmount,
        int $currentAmount,
        int $monthsLeft,
        float $annualInflation = self::DEFAULT_ANNUAL_INFLATION,
    ): int {
        $remaining = $targetAmount - $currentAmount;

        if ($remaining <= 0 || $monthsLeft <= 0) {
            return 0;
        }

        $years = $monthsLeft / 12;
        $goalNominal = $remaining * pow(1 + $annualInflation, $years);

        return (int) ceil($goalNominal / $monthsLeft);
    }

    /**
     * Predict completion date given a fixed monthly payment.
     *
     * n = remaining / PMT (adjusted for inflation growth each month)
     */
    public function predictCompletionDate(
        int $targetAmount,
        int $currentAmount,
        int $monthlyPayment,
        float $annualInflation = self::DEFAULT_ANNUAL_INFLATION,
    ): ?Carbon {
        $months = $this->predictCompletionMonths($targetAmount, $currentAmount, $monthlyPayment, $annualInflation);

        if ($months === null) {
            return null;
        }

        return Carbon::now()->addMonths($months);
    }

    /**
     * Predict how many months until the goal is reached.
     */
    public function predictCompletionMonths(
        int $targetAmount,
        int $currentAmount,
        int $monthlyPayment,
        float $annualInflation = self::DEFAULT_ANNUAL_INFLATION,
    ): ?int {
        if ($monthlyPayment <= 0) {
            return null;
        }

        $remaining = $targetAmount - $currentAmount;

        if ($remaining <= 0) {
            return 0;
        }

        $monthlyInflation = $annualInflation / 12;
        $accumulated = 0;
        $months = 0;
        $inflatedTarget = (float) $remaining;

        while ($accumulated < $inflatedTarget && $months < self::MONTHS_HARD_LIMIT) {
            $months++;
            $accumulated += $monthlyPayment;
            $inflatedTarget = $remaining * pow(1 + $monthlyInflation, $months);
        }

        if ($months >= self::MONTHS_HARD_LIMIT) {
            return null;
        }

        return $months;
    }

    /**
     * Build three scenarios: optimistic (5%), baseline (current), pessimistic (15%).
     *
     * @return array{
     *     optimistic: array{inflation: float, monthly_payment: int, completion_date: ?string},
     *     baseline: array{inflation: float, monthly_payment: int, completion_date: ?string},
     *     pessimistic: array{inflation: float, monthly_payment: int, completion_date: ?string},
     * }
     */
    public function buildScenarios(Goal $goal): array
    {
        $monthsLeft = $this->getMonthsLeft($goal);
        $currentPayment = $this->estimateCurrentMonthlyPayment($goal);

        return [
            'optimistic' => $this->buildScenario(
                $goal,
                self::SCENARIO_OPTIMISTIC_INFLATION,
                $monthsLeft,
                $currentPayment,
            ),
            'baseline' => $this->buildScenario(
                $goal,
                self::DEFAULT_ANNUAL_INFLATION,
                $monthsLeft,
                $currentPayment,
            ),
            'pessimistic' => $this->buildScenario(
                $goal,
                self::SCENARIO_PESSIMISTIC_INFLATION,
                $monthsLeft,
                $currentPayment,
            ),
        ];
    }

    /**
     * "What if" recalculation: how adding extra monthly payment changes the outcome.
     *
     * @return array{
     *     current_monthly: int,
     *     new_monthly: int,
     *     current_completion: ?string,
     *     new_completion: ?string,
     *     days_saved: int,
     * }
     */
    public function whatIf(Goal $goal, int $additionalMonthly): array
    {
        $monthsLeft = $this->getMonthsLeft($goal);
        $currentPayment = $this->requiredMonthlyPayment(
            $goal->target_amount,
            $goal->current_amount,
            $monthsLeft,
        );
        $newPayment = $currentPayment + $additionalMonthly;
        $remaining = $goal->target_amount - $goal->current_amount;
        $currentCompletion = $goal->target_date?->toDateString();

        if ($remaining <= 0 || $newPayment <= 0) {
            return [
                'current_monthly' => $currentPayment,
                'new_monthly' => $newPayment,
                'current_completion' => $currentCompletion,
                'new_completion' => $currentCompletion,
                'days_saved' => 0,
            ];
        }

        $exactNewMonths = $remaining / $newPayment;
        $daysSaved = max(0, (int) round(($monthsLeft - $exactNewMonths) * 30.44));

        $newCompletion = $goal->target_date
            ? $goal->target_date->copy()->subDays($daysSaved)->toDateString()
            : Carbon::now()->addDays((int) round($exactNewMonths * 30.44))->toDateString();

        return [
            'current_monthly' => $currentPayment,
            'new_monthly' => $newPayment,
            'current_completion' => $currentCompletion,
            'new_completion' => $newCompletion,
            'days_saved' => $daysSaved,
        ];
    }

    public function getMonthsLeft(Goal $goal): int
    {
        if (! $goal->target_date) {
            return 12;
        }

        $months = (int) Carbon::now()->diffInMonths($goal->target_date, false);

        return max(1, $months);
    }

    /**
     * Estimate current monthly payment based on contribution history.
     * Falls back to equal division of remaining amount over months left.
     */
    public function estimateCurrentMonthlyPayment(Goal $goal): int
    {
        $monthsActive = max(1, (int) Carbon::parse($goal->started_at)->diffInMonths(Carbon::now()));

        if ($goal->current_amount > 0) {
            return (int) ceil($goal->current_amount / $monthsActive);
        }

        return $this->requiredMonthlyPayment(
            $goal->target_amount,
            $goal->current_amount,
            $this->getMonthsLeft($goal),
        );
    }

    /**
     * @return array{inflation: float, monthly_payment: int, completion_date: ?string}
     */
    private function buildScenario(
        Goal $goal,
        float $inflation,
        int $monthsLeft,
        int $currentPayment,
    ): array {
        $requiredPayment = $this->requiredMonthlyPaymentWithInflation(
            $goal->target_amount,
            $goal->current_amount,
            $monthsLeft,
            $inflation,
        );

        $completionDate = $this->predictCompletionDate(
            $goal->target_amount,
            $goal->current_amount,
            $currentPayment,
            $inflation,
        );

        return [
            'inflation' => $inflation,
            'monthly_payment' => $requiredPayment,
            'completion_date' => $completionDate?->toDateString(),
        ];
    }
}
