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

describe('GET /login', function () {
    it('shows login page for guests', function () {
        $this->get('/login')
            ->assertOk()
            ->assertSee(__('auth.login_title'));
    });

    it('redirects authenticated users away from login', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect();
    });
});

describe('POST /login (send code)', function () {
    it('sends verification code for valid phone', function () {
        $this->post('/login', ['phone' => '79001234567'])
            ->assertRedirect(route('auth.verify'));

        $this->assertDatabaseHas('verification_codes', [
            'phone' => '79001234567',
            'verified_at' => null,
        ]);
    });

    it('rejects invalid phone format', function () {
        $this->post('/login', ['phone' => '12345'])
            ->assertSessionHasErrors('phone');
    });

    it('rejects empty phone', function () {
        $this->post('/login', ['phone' => ''])
            ->assertSessionHasErrors('phone');
    });

    it('rate limits code sending', function () {
        $this->post('/login', ['phone' => '79001234567'])
            ->assertRedirect(route('auth.verify'));

        $this->post('/login', ['phone' => '79001234567'])
            ->assertSessionHasErrors('phone');
    });

    it('deletes previous active codes on resend', function () {
        VerificationCode::factory()->create(['phone' => '79001234567']);

        RateLimiter::clear('sms:79001234567');

        $this->post('/login', ['phone' => '79001234567']);

        expect(VerificationCode::where('phone', '79001234567')->count())->toBe(1);
    });
});

describe('POST /verify (verify code)', function () {
    it('verifies correct code and authenticates user', function () {
        $vc = VerificationCode::factory()->create([
            'phone' => '79001234567',
            'code' => '1234',
        ]);

        $this->post('/verify', ['phone' => '79001234567', 'code' => '1234'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', ['phone' => '79001234567']);

        expect($vc->fresh()->verified_at)->not->toBeNull();
    });

    it('creates new user on first verification', function () {
        VerificationCode::factory()->create([
            'phone' => '79009999999',
            'code' => '5678',
        ]);

        $this->post('/verify', ['phone' => '79009999999', 'code' => '5678']);

        $user = User::where('phone', '79009999999')->first();

        expect($user)->not->toBeNull();
        expect($user->phone_verified_at)->not->toBeNull();
    });

    it('logs in existing user on verification', function () {
        $user = User::factory()->create(['phone' => '79001234567']);

        VerificationCode::factory()->create([
            'phone' => '79001234567',
            'code' => '1234',
        ]);

        $this->post('/verify', ['phone' => '79001234567', 'code' => '1234']);

        $this->assertAuthenticatedAs($user);
    });

    it('rejects invalid code', function () {
        VerificationCode::factory()->create([
            'phone' => '79001234567',
            'code' => '1234',
        ]);

        $this->post('/verify', ['phone' => '79001234567', 'code' => '9999'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    });

    it('rejects expired code', function () {
        VerificationCode::factory()->expired()->create([
            'phone' => '79001234567',
            'code' => '1234',
        ]);

        $this->post('/verify', ['phone' => '79001234567', 'code' => '1234'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    });

    it('rejects code after max attempts', function () {
        VerificationCode::factory()->maxAttempts()->create([
            'phone' => '79001234567',
            'code' => '1234',
        ]);

        $this->post('/verify', ['phone' => '79001234567', 'code' => '1234'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    });

    it('increments attempts on wrong code', function () {
        $vc = VerificationCode::factory()->create([
            'phone' => '79001234567',
            'code' => '1234',
            'attempts' => 0,
        ]);

        $this->post('/verify', ['phone' => '79001234567', 'code' => '0000']);

        expect($vc->fresh()->attempts)->toBe(1);
    });

    it('rejects missing code field', function () {
        $this->post('/verify', ['phone' => '79001234567'])
            ->assertSessionHasErrors('code');
    });

    it('rejects missing phone field', function () {
        $this->post('/verify', ['code' => '1234'])
            ->assertSessionHasErrors('phone');
    });
});

describe('POST /logout', function () {
    it('logs out authenticated user', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('landing'));

        $this->assertGuest();
    });
});
