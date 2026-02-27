<?php

namespace Database\Factories;

use App\Models\VerificationCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VerificationCode> */
class VerificationCodeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'phone' => '+7'.fake()->unique()->numerify('9#########'),
            'code' => (string) fake()->numberBetween(1000, 9999),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'verified_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'verified_at' => now(),
        ]);
    }

    public function maxAttempts(): static
    {
        return $this->state(fn () => [
            'attempts' => 3,
        ]);
    }
}
