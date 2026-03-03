<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'subscription_id' => null,
            'amount' => fake()->randomElement([29900, 249900]),
            'currency_code' => 'RUB',
            'provider' => 'stripe',
            'provider_payment_id' => fake()->uuid(),
            'status' => PaymentStatus::Completed,
            'paid_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Failed,
            'paid_at' => null,
        ]);
    }
}
