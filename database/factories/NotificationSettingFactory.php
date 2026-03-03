<?php

namespace Database\Factories;

use App\Models\NotificationSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NotificationSetting> */
class NotificationSettingFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'email_weekly' => true,
            'push_goals' => true,
            'push_ai_advice' => true,
        ];
    }

    public function allDisabled(): static
    {
        return $this->state(fn () => [
            'email_weekly' => false,
            'push_goals' => false,
            'push_ai_advice' => false,
        ]);
    }
}
