<?php

use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('sms:79001234567');
    RateLimiter::clear('verify:79001234567');
});

describe('POST /api/v1/auth/send-code', function () {
    it('sends verification code and returns success', function () {
        $this->postJson('/api/v1/auth/send-code', ['phone' => '79001234567'])
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('verification_codes', [
            'phone' => '79001234567',
        ]);
    });

    it('rejects invalid phone with 422', function () {
        $this->postJson('/api/v1/auth/send-code', ['phone' => '123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    });

    it('rate limits code sending', function () {
        $this->postJson('/api/v1/auth/send-code', ['phone' => '79001234567'])
            ->assertOk();

        $this->postJson('/api/v1/auth/send-code', ['phone' => '79001234567'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    });
});

describe('POST /api/v1/auth/verify-code', function () {
    it('returns sanctum token on valid code', function () {
        VerificationCode::factory()->create([
            'phone' => '79001234567',
            'code' => '1234',
        ]);

        $this->postJson('/api/v1/auth/verify-code', [
            'phone' => '79001234567',
            'code' => '1234',
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'phone', 'name']]);
    });

    it('creates user on first verification', function () {
        VerificationCode::factory()->create([
            'phone' => '79009999999',
            'code' => '5678',
        ]);

        $this->postJson('/api/v1/auth/verify-code', [
            'phone' => '79009999999',
            'code' => '5678',
        ])->assertOk();

        expect(User::where('phone', '79009999999')->exists())->toBeTrue();
    });

    it('rejects invalid code with 422', function () {
        VerificationCode::factory()->create([
            'phone' => '79001234567',
            'code' => '1234',
        ]);

        $this->postJson('/api/v1/auth/verify-code', [
            'phone' => '79001234567',
            'code' => '9999',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    });

    it('rejects expired code', function () {
        VerificationCode::factory()->expired()->create([
            'phone' => '79001234567',
            'code' => '1234',
        ]);

        $this->postJson('/api/v1/auth/verify-code', [
            'phone' => '79001234567',
            'code' => '1234',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    });

    it('rejects code after max attempts', function () {
        VerificationCode::factory()->maxAttempts()->create([
            'phone' => '79001234567',
            'code' => '1234',
        ]);

        $this->postJson('/api/v1/auth/verify-code', [
            'phone' => '79001234567',
            'code' => '1234',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    });
});

describe('POST /api/v1/auth/logout', function () {
    it('revokes current token', function () {
        $user = User::factory()->create(['phone_verified_at' => now()]);
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJson(['message' => __('auth.logout')]);

        expect($user->tokens()->count())->toBe(0);
    });
});
