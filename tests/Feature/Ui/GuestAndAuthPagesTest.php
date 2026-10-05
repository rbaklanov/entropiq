<?php

use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\VerifyPage;
use App\Models\Goal;
use App\Models\User;
use App\Support\PhoneFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('login heading', function () {
    it('matches the UX spec in both locales', function () {
        app()->setLocale('ru');
        $this->get('/login')->assertSee('Войти или создать аккаунт');

        app()->setLocale('en');
        $this->get('/login')->assertSee('Sign in or create an account');
    });
});

describe('validation messages follow the locale', function () {
    it('shows the OTP size error in Russian', function () {
        app()->setLocale('ru');
        session(['phone' => '79001234567']);

        Livewire::test(VerifyPage::class)
            ->set('code', '12')
            ->call('verify')
            ->assertSee('Код состоит из 4 цифр.');
    });

    it('shows the phone format error in Russian', function () {
        app()->setLocale('ru');

        Livewire::test(LoginPage::class)
            ->set('phone', '7900')
            ->call('sendCode')
            ->assertSee('Введите номер телефона полностью.');
    });

    it('keeps English messages for the English locale', function () {
        app()->setLocale('en');
        session(['phone' => '79001234567']);

        Livewire::test(VerifyPage::class)
            ->set('code', '12')
            ->call('verify')
            ->assertDontSee('Код состоит');
    });
});

describe('phone mask', function () {
    it('formats a canonical phone', function () {
        expect(PhoneFormatter::mask('79001234567'))->toBe('+7 (900) 123-45-67');
    });

    it('leaves unexpected values untouched', function () {
        expect(PhoneFormatter::mask('12345'))->toBe('12345')
            ->and(PhoneFormatter::mask(''))->toBe('');
    });

    it('shows the masked phone on the verify page', function () {
        session(['phone' => '79001234567']);

        Livewire::test(VerifyPage::class)
            ->assertSee('+7 (900) 123-45-67')
            ->assertDontSee('79001234567');
    });
});

describe('FAQ page and footer links', function () {
    it('serves the FAQ page', function () {
        app()->setLocale('ru');

        $this->get('/faq')
            ->assertOk()
            ->assertSee(__('landing.faq_title'))
            ->assertSee(__('faq.sections.0.title'))
            ->assertSee(__('faq.sections.0.items.0.q'));
    });

    it('links the footer FAQ to the FAQ page instead of a stub', function () {
        $html = $this->get('/terms')->assertOk()->getContent();

        expect($html)->toContain('href="'.route('faq').'"');
    });

    it('anchors header links to the landing page from other guest pages', function () {
        foreach (['/privacy', '/terms', '/faq'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            expect($html)->toContain('href="'.route('landing').'#features"')
                ->and($html)->toContain('href="'.route('landing').'#pricing"');
        }
    });
});

describe('goal scenarios for the Free plan', function () {
    beforeEach(function () {
        app()->setLocale('ru');
    });

    function goalFor(User $user): Goal
    {
        return Goal::factory()->for($user)->create([
            'target_amount' => 12_000_000,
            'current_amount' => 0,
            'target_date' => now()->addMonths(12),
        ]);
    }

    it('does not render scenario or what-if data for a Free user', function () {
        $user = User::factory()->onboarded()->create(['phone_verified_at' => now()]);
        $goal = goalFor($user);

        $this->actingAs($user)
            ->get(route('goals.show', $goal))
            ->assertOk()
            ->assertDontSee(__('goals.scenario_optimistic'))
            ->assertDontSee(__('goals.scenario_pessimistic'))
            ->assertDontSee(__('goals.what_if_extra'));
    });

    it('renders real scenarios for a Premium user', function () {
        $user = User::factory()->premium()->onboarded()->create(['phone_verified_at' => now()]);
        $goal = goalFor($user);

        $this->actingAs($user)
            ->get(route('goals.show', $goal))
            ->assertOk()
            ->assertSee(__('goals.scenario_optimistic'))
            ->assertSee(__('goals.what_if_extra'));
    });

    it('hides the blurred content from assistive technologies', function () {
        $user = User::factory()->onboarded()->create(['phone_verified_at' => now()]);
        $goal = goalFor($user);

        $this->actingAs($user)
            ->get(route('goals.show', $goal))
            ->assertSeeHtml('aria-hidden="true" inert');
    });
});
