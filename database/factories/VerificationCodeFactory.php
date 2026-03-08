<?php

namespace Database\Factories;

use App\Models\VerificationCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VerificationCode> */
class VerificationCodeFactory extends Factory
{
    protected $model = VerificationCode::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'phone' => '7'.$this->faker->numerify('#########'),
            'code' => (string) $this->faker->numberBetween(1000, 9999),
            'expires_at' => now()->addMinutes(VerificationCode::EXPIRATION_MINUTES),
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
            'attempts' => VerificationCode::MAX_ATTEMPTS,
        ]);
    }
}
