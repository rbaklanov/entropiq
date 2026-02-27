<?php

namespace Database\Factories;

use App\Enums\Locale;
use App\Enums\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'phone' => '+7'.fake()->unique()->numerify('9#########'),
            'name' => fake()->name(),
            'locale' => Locale::Ru,
            'currency_code' => 'RUB',
            'subscription_plan' => SubscriptionPlan::Free,
            'onboarding_completed_at' => null,
        ];
    }

    public function premium(): static
    {
        return $this->state(fn () => [
            'subscription_plan' => SubscriptionPlan::Yearly,
        ]);
    }

    public function onboarded(): static
    {
        return $this->state(fn () => [
            'onboarding_completed_at' => now(),
        ]);
    }
}
