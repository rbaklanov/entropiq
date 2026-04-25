<?php

use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function notifUser(): array
{
    $user = User::factory()->create(['phone_verified_at' => now()]);
    $token = $user->createToken('test')->plainTextToken;

    return [$user, $token];
}

function notifHeaders(string $token): array
{
    return [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ];
}

describe('GET /api/v1/user/notification-settings', function () {
    it('returns default settings when none exist', function () {
        [, $token] = notifUser();

        $this->withHeaders(notifHeaders($token))
            ->getJson('/api/v1/user/notification-settings')
            ->assertOk()
            ->assertJson(['data' => [
                'email_weekly' => true,
                'push_goals' => true,
                'push_ai_advice' => true,
            ]]);
    });

    it('returns existing settings', function () {
        [$user, $token] = notifUser();
        NotificationSetting::factory()->for($user)->allDisabled()->create();

        $this->withHeaders(notifHeaders($token))
            ->getJson('/api/v1/user/notification-settings')
            ->assertOk()
            ->assertJson(['data' => [
                'email_weekly' => false,
                'push_goals' => false,
                'push_ai_advice' => false,
            ]]);
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/user/notification-settings')
            ->assertUnauthorized();
    });
});

describe('PUT /api/v1/user/notification-settings', function () {
    it('updates a single setting', function () {
        [$user, $token] = notifUser();

        $this->withHeaders(notifHeaders($token))
            ->putJson('/api/v1/user/notification-settings', ['email_weekly' => false])
            ->assertOk()
            ->assertJson(['data' => [
                'email_weekly' => false,
                'push_goals' => true,
                'push_ai_advice' => true,
            ]]);
    });

    it('updates multiple settings', function () {
        [$user, $token] = notifUser();

        $this->withHeaders(notifHeaders($token))
            ->putJson('/api/v1/user/notification-settings', [
                'email_weekly' => false,
                'push_goals' => false,
                'push_ai_advice' => false,
            ])
            ->assertOk()
            ->assertJson(['data' => [
                'email_weekly' => false,
                'push_goals' => false,
                'push_ai_advice' => false,
            ]]);

        expect($user->notificationSetting->fresh()->email_weekly)->toBeFalse();
        expect($user->notificationSetting->fresh()->push_goals)->toBeFalse();
    });

    it('creates settings if none exist and applies updates', function () {
        [$user, $token] = notifUser();
        expect($user->notificationSetting)->toBeNull();

        $this->withHeaders(notifHeaders($token))
            ->putJson('/api/v1/user/notification-settings', ['push_goals' => false])
            ->assertOk();

        expect($user->fresh()->notificationSetting)->not->toBeNull();
        expect($user->fresh()->notificationSetting->push_goals)->toBeFalse();
        expect($user->fresh()->notificationSetting->email_weekly)->toBeTrue();
    });

    it('rejects invalid boolean values', function () {
        [, $token] = notifUser();

        $this->withHeaders(notifHeaders($token))
            ->putJson('/api/v1/user/notification-settings', ['email_weekly' => 'not_a_bool'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email_weekly');
    });
});
