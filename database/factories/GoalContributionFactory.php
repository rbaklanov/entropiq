<?php

namespace Database\Factories;

use App\Models\GoalContribution;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GoalContribution> */
class GoalContributionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'goal_id' => 1,
            'transaction_id' => null,
            'amount' => fake()->numberBetween(1000, 100_000),
            'date' => fake()->dateTimeBetween('-6 months'),
        ];
    }
}
