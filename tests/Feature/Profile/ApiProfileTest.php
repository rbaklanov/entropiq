<?php

use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function profileUser(): array
{
    $user = User::factory()->create(['phone_verified_at' => now()]);
    $token = $user->createToken('test')->plainTextToken;

    return [$user, $token];
}

function profileHeaders(string $token): array
{
    return [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ];
}

describe('GET /api/v1/user', function () {
    it('returns current user profile', function () {
        [$user, $token] = profileUser();

        $this->withHeaders(profileHeaders($token))
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'phone', 'name', 'locale', 'currency_code', 'subscription_plan'],
            ])
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.phone', $user->phone);
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/user')
            ->assertUnauthorized();
    });
});

describe('PUT /api/v1/user', function () {
    it('updates user name', function () {
        [$user, $token] = profileUser();

        $this->withHeaders(profileHeaders($token))
            ->putJson('/api/v1/user', ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        expect($user->fresh()->name)->toBe('New Name');
    });

    it('updates user locale', function () {
        [$user, $token] = profileUser();

        $this->withHeaders(profileHeaders($token))
            ->putJson('/api/v1/user', ['locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.locale', 'en');

        expect($user->fresh()->locale->value)->toBe('en');
    });

    it('updates user currency', function () {
        [$user, $token] = profileUser();
        Currency::factory()->create(['code' => 'USD']);

        $this->withHeaders(profileHeaders($token))
            ->putJson('/api/v1/user', ['currency_code' => 'USD'])
            ->assertOk()
            ->assertJsonPath('data.currency_code', 'USD');

        expect($user->fresh()->currency_code)->toBe('USD');
    });

    it('rejects invalid locale', function () {
        [, $token] = profileUser();

        $this->withHeaders(profileHeaders($token))
            ->putJson('/api/v1/user', ['locale' => 'xx'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('locale');
    });

    it('rejects non-existent currency', function () {
        [, $token] = profileUser();

        $this->withHeaders(profileHeaders($token))
            ->putJson('/api/v1/user', ['currency_code' => 'ZZZ'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency_code');
    });

    it('updates multiple fields at once', function () {
        [$user, $token] = profileUser();
        Currency::factory()->create(['code' => 'EUR']);

        $this->withHeaders(profileHeaders($token))
            ->putJson('/api/v1/user', [
                'name' => 'Updated',
                'locale' => 'en',
                'currency_code' => 'EUR',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated')
            ->assertJsonPath('data.locale', 'en')
            ->assertJsonPath('data.currency_code', 'EUR');
    });
});

describe('DELETE /api/v1/user', function () {
    it('soft deletes user and anonymizes data', function () {
        [$user, $token] = profileUser();
        $userId = $user->id;

        $this->withHeaders(profileHeaders($token))
            ->deleteJson('/api/v1/user')
            ->assertOk()
            ->assertJson(['message' => __('profile.account_deleted')]);

        $deletedUser = User::withTrashed()->find($userId);
        expect($deletedUser->deleted_at)->not->toBeNull();
        expect($deletedUser->name)->toBeNull();
        expect($deletedUser->phone)->toBe("deleted_{$userId}");
    });

    it('revokes all tokens on deletion', function () {
        [$user, $token] = profileUser();

        $this->withHeaders(profileHeaders($token))
            ->deleteJson('/api/v1/user')
            ->assertOk();

        expect($user->tokens()->count())->toBe(0);
    });
});
