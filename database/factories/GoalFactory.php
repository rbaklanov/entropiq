<?php

namespace Database\Factories;

use App\Enums\GoalStatus;
use App\Enums\GoalType;
use App\Models\Goal;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Goal> */
class GoalFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'type' => fake()->randomElement(GoalType::cases()),
            'status' => GoalStatus::Active,
            'icon' => null,
            'target_amount' => fake()->numberBetween(100_000, 10_000_000),
            'current_amount' => 0,
            'currency_code' => 'RUB',
            'started_at' => now(),
            'target_date' => fake()->optional(0.7)->dateTimeBetween('+1 month', '+2 years'),
        ];
    }

    public function achieved(): static
    {
        return $this->state(fn () => [
            'status' => GoalStatus::Achieved,
            'current_amount' => fn (array $attrs) => $attrs['target_amount'],
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => GoalStatus::Cancelled,
        ]);
    }
}
