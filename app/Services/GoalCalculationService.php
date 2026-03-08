<?php

namespace App\Services;

use App\Contracts\GoalCalculationServiceInterface;
use App\Models\Goal;

class GoalCalculationService implements GoalCalculationServiceInterface
{
    public function requiredMonthlyContribution(Goal $goal, bool $withInflation = true): int
    {
        return 0;
    }

    public function forecastDate(Goal $goal): ?\DateTimeInterface
    {
        return null;
    }

    /** @return array{optimistic: array<string, mixed>, base: array<string, mixed>, pessimistic: array<string, mixed>} */
    public function scenarios(Goal $goal): array
    {
        return [
            'optimistic' => [],
            'base' => [],
            'pessimistic' => [],
        ];
    }
}
