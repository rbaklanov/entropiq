<?php

namespace Database\Factories;

use App\Models\CpiValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CpiValue> */
class CpiValueFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'period' => fake()->dateTimeBetween('-2 years'),
            'category_code' => 'CPI_TOTAL',
            'value' => fake()->randomFloat(2, 95, 115),
            'source' => 'emiss',
        ];
    }
}
