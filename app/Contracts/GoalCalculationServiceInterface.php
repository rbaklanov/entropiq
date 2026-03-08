<?php

namespace App\Contracts;

use App\Models\Goal;

interface GoalCalculationServiceInterface
{
    public function requiredMonthlyContribution(Goal $goal, bool $withInflation = true): int;

    public function forecastDate(Goal $goal): ?\DateTimeInterface;

    /** @return array{optimistic: array<string, mixed>, base: array<string, mixed>, pessimistic: array<string, mixed>} */
    public function scenarios(Goal $goal): array;
}
