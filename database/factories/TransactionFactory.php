<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Transaction> */
class TransactionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $type = fake()->randomElement(TransactionType::cases());

        return [
            'category_id' => 1,
            'type' => $type,
            'amount' => fake()->numberBetween(100, 1_000_000),
            'currency_code' => 'RUB',
            'date' => fake()->dateTimeBetween('-1 year'),
            'comment' => fake()->optional(0.5)->sentence(),
            'is_recurring' => false,
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => [
            'type' => TransactionType::Income,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn () => [
            'type' => TransactionType::Expense,
        ]);
    }

    public function recurring(): static
    {
        return $this->state(fn () => [
            'is_recurring' => true,
        ]);
    }
}
