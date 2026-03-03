<?php

namespace Database\Factories;

use App\Enums\RecurringInterval;
use App\Enums\TransactionType;
use App\Models\RecurringRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RecurringRule> */
class RecurringRuleFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(TransactionType::cases()),
            'amount' => fake()->numberBetween(1000, 500_000),
            'category_id' => 1,
            'currency_code' => 'RUB',
            'comment' => fake()->optional(0.5)->sentence(),
            'interval' => fake()->randomElement(RecurringInterval::cases()),
            'next_run_at' => fake()->dateTimeBetween('now', '+1 month'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn () => [
            'interval' => RecurringInterval::Monthly,
        ]);
    }
}
