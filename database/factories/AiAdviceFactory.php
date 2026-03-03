<?php

namespace Database\Factories;

use App\Models\AiAdvice;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AiAdvice> */
class AiAdviceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'body' => fake()->paragraphs(2, true),
            'basis_data' => ['period' => now()->subMonth()->format('Y-m'), 'category' => 'food'],
            'rating' => null,
            'is_read' => false,
            'generated_at' => fake()->dateTimeBetween('-1 month'),
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => [
            'is_read' => true,
        ]);
    }

    public function rated(int $rating = 5): static
    {
        return $this->state(fn () => [
            'rating' => $rating,
        ]);
    }
}
