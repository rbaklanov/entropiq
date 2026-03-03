<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => ['ru' => fake()->word(), 'en' => fake()->word()],
            'type' => fake()->randomElement(TransactionType::cases()),
            'icon' => fake()->randomElement(['cart', 'home', 'car', 'food', 'health', 'gift']),
            'color' => fake()->hexColor(),
            'is_system' => false,
            'user_id' => null,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function system(): static
    {
        return $this->state(fn () => [
            'is_system' => true,
            'user_id' => null,
        ]);
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
}
