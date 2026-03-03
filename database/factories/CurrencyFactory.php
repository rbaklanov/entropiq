<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Currency> */
class CurrencyFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->currencyCode(),
            'name' => fake()->word(),
            'symbol' => fake()->randomElement(['₽', '$', '€', '£', '¥']),
            'decimal_places' => 2,
        ];
    }

    public function rub(): static
    {
        return $this->state(fn () => [
            'code' => 'RUB',
            'name' => 'Российский рубль',
            'symbol' => '₽',
            'decimal_places' => 2,
        ]);
    }

    public function usd(): static
    {
        return $this->state(fn () => [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
        ]);
    }
}
