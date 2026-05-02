<?php

use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\VerifyPage;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

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

describe('Livewire LoginPage (send code)', function () {
    it('sends verification code for valid phone', function () {
        Livewire::test(LoginPage::class)
            ->set('phone', '79001234567')
            ->call('sendCode')
            ->assertRedirect(route('auth.verify'));

        $this->assertDatabaseHas('verification_codes', [
            'phone' => '79001234567',
            'verified_at' => null,
        ]);
    });

    it('rejects invalid phone format', function () {
        Livewire::test(LoginPage::class)
            ->set('phone', '12345')
            ->call('sendCode')
            ->assertHasErrors(['phone']);
    });

    it('rejects empty phone', function () {
        Livewire::test(LoginPage::class)
            ->call('sendCode')
            ->assertHasErrors(['phone']);
    });

    it('rate limits code sending', function () {
        Livewire::test(LoginPage::class)
            ->set('phone', '79001234567')
            ->call('sendCode')
            ->assertRedirect(route('auth.verify'));

        Livewire::test(LoginPage::class)
            ->set('phone', '79001234567')
            ->call('sendCode')
            ->assertHasErrors(['phone']);
    });

    it('deletes previous active codes on resend', function () {
        VerificationCode::factory()->create(['phone' => '79001234567']);

        RateLimiter::clear('sms:79001234567');

        Livewire::test(LoginPage::class)
            ->set('phone', '79001234567')
            ->call('sendCode');

        expect(VerificationCode::where('phone', '79001234567')->count())->toBe(1);
    });
});

describe('Livewire VerifyPage', function () {
    it('redirects to login if no phone in session', function () {
        Livewire::test(VerifyPage::class)
            ->assertRedirect(route('auth.login'));
    });

    it('verifies correct code and authenticates user', function () {
        $vc = VerificationCode::factory()->create([
            'phone' => '79001234567',
            'code' => '1234',
        ]);

        session()->put('phone', '79001234567');

        Livewire::test(VerifyPage::class)
            ->set('code', '1234')
            ->call('verify')
            ->assertRedirect(route('onboarding.step', 1));

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', ['phone' => '79001234567']);

        expect($vc->fresh()->verified_at)->not->toBeNull();
    });

    it('creates new user on first verification', function () {
        VerificationCode::factory()->create([
            'phone' => '79009999999',
            'code' => '5678',
        ]);

        session()->put('phone', '79009999999');

        Livewire::test(VerifyPage::class)
            ->set('code', '5678')
            ->call('verify');

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

        session()->put('phone', '79001234567');

        Livewire::test(VerifyPage::class)
            ->set('code', '1234')
            ->call('verify');

        $this->assertAuthenticatedAs($user);
    });

    it('rejects invalid code', function () {
        VerificationCode::factory()->create([
            'phone' => '79001234567',
            'code' => '1234',
        ]);

        session()->put('phone', '79001234567');

        Livewire::test(VerifyPage::class)
            ->set('code', '9999')
            ->call('verify')
            ->assertHasErrors(['code']);

        $this->assertGuest();
    });

    it('rejects expired code', function () {
        VerificationCode::factory()->expired()->create([
            'phone' => '79001234567',
            'code' => '1234',
        ]);

        session()->put('phone', '79001234567');

        Livewire::test(VerifyPage::class)
            ->set('code', '1234')
            ->call('verify')
            ->assertHasErrors(['code']);

        $this->assertGuest();
    });

    it('rejects code after max attempts', function () {
        VerificationCode::factory()->maxAttempts()->create([
            'phone' => '79001234567',
            'code' => '1234',
        ]);

        session()->put('phone', '79001234567');

        Livewire::test(VerifyPage::class)
            ->set('code', '1234')
            ->call('verify')
            ->assertHasErrors(['code']);

        $this->assertGuest();
    });

    it('increments attempts on wrong code', function () {
        $vc = VerificationCode::factory()->create([
            'phone' => '79001234567',
            'code' => '1234',
            'attempts' => 0,
        ]);

        session()->put('phone', '79001234567');

        Livewire::test(VerifyPage::class)
            ->set('code', '0000')
            ->call('verify');

        expect($vc->fresh()->attempts)->toBe(1);
    });

    it('rejects empty code', function () {
        session()->put('phone', '79001234567');

        Livewire::test(VerifyPage::class)
            ->call('verify')
            ->assertHasErrors(['code']);
    });

    it('allows resending code after cooldown', function () {
        VerificationCode::factory()->create(['phone' => '79001234567']);

        RateLimiter::clear('sms:79001234567');

        session()->put('phone', '79001234567');

        Livewire::test(VerifyPage::class)
            ->call('resendCode')
            ->assertDispatched('timer-reset');

        expect(VerificationCode::where('phone', '79001234567')->count())->toBe(1);
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
