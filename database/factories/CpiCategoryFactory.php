<?php

namespace Database\Factories;

use App\Models\CpiCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CpiCategory> */
class CpiCategoryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('CPI_##??'),
            'name' => fake()->word(),
            'parent_code' => null,
            'mapping_to_app_category_id' => null,
        ];
    }
}
