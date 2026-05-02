<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function subscriptionUser(bool $premium = false): array
{
    $user = $premium
        ? User::factory()->premium()->create(['phone_verified_at' => now()])
        : User::factory()->create(['phone_verified_at' => now()]);

    $token = $user->createToken('test')->plainTextToken;

    return [$user, $token];
}

function subHeaders(string $token): array
{
    return [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ];
}

describe('EnsurePremiumSubscription middleware', function () {
    it('blocks free user from premium API routes', function () {
        [, $token] = subscriptionUser(false);

        $this->withHeaders(subHeaders($token))
            ->getJson('/api/v1/analytics/export')
            ->assertForbidden()
            ->assertJsonPath('message', __('subscription.premium_required'));
    });

    it('allows premium user to access premium API routes', function () {
        [, $token] = subscriptionUser(true);

        $this->withHeaders(subHeaders($token))
            ->getJson('/api/v1/analytics/export')
            ->assertOk();
    });

    it('includes upgrade_url in forbidden response', function () {
        [, $token] = subscriptionUser(false);

        $this->withHeaders(subHeaders($token))
            ->getJson('/api/v1/analytics/export')
            ->assertForbidden()
            ->assertJsonStructure(['message', 'upgrade_url']);
    });

    it('redirects free web user to subscription page', function () {
        $user = User::factory()->onboarded()->create(['phone_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/analytics/export')
            ->assertRedirect(route('settings.subscription'));
    });
});
