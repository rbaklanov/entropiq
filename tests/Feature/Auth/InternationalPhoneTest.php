<?php

use App\Livewire\Auth\LoginPage;
use App\Models\VerificationCode;
use App\Support\PhoneNumbers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['79001234567', '358504761410'] as $phone) {
        RateLimiter::clear("sms:{$phone}");
    }
});

describe('PhoneNumbers::normalize', function () {
    it('normalizes numbers to E.164 digits', function (string $input, ?string $region, ?string $expected) {
        expect(PhoneNumbers::normalize($input, $region))->toBe($expected);
    })->with([
        'russian, international digits' => ['79001234567', null, '79001234567'],
        'russian, with plus' => ['+7 (900) 123-45-67', null, '79001234567'],
        'russian, national with 8' => ['89001234567', 'RU', '79001234567'],
        'russian, ten digits' => ['9001234567', 'RU', '79001234567'],
        'finnish, international' => ['+358504761410', null, '358504761410'],
        'finnish, digits without plus' => ['358504761410', null, '358504761410'],
        'finnish, national with 0' => ['050 476 1410', 'FI', '358504761410'],
        'plus overrides selected country' => ['+358504761410', 'RU', '358504761410'],
        'kazakh mobile' => ['+7 701 123 4567', null, '77011234567'],
        'russian landline' => ['74951234567', null, null],
        'russian toll free' => ['88005553535', 'RU', null],
        'too short' => ['7900', null, null],
        'empty' => ['', 'RU', null],
    ]);

    it('rejects countries outside the configured list', function () {
        config(['services.sms.allowed_countries' => 'RU,KZ']);

        expect(PhoneNumbers::normalize('+358504761410'))->toBeNull()
            ->and(PhoneNumbers::normalize('+79001234567'))->toBe('79001234567');
    });
});

describe('PhoneNumbers::format', function () {
    it('formats a foreign number internationally', function () {
        expect(PhoneNumbers::format('358504761410'))->toBe('+358 50 4761410');
    });
});

describe('PhoneNumbers::countries', function () {
    it('puts Russia first and lists every supported country', function () {
        $countries = PhoneNumbers::countries('ru');

        expect($countries[0])->toMatchArray(['code' => 'RU', 'dial' => 7])
            ->and(count($countries))->toBeGreaterThan(200)
            ->and(collect($countries)->firstWhere('code', 'FI'))->toMatchArray(['dial' => 358, 'name' => 'Финляндия']);
    });

    it('lists only the configured countries', function () {
        config(['services.sms.allowed_countries' => 'fi, ru']);

        expect(collect(PhoneNumbers::countries('en'))->pluck('code')->all())->toBe(['RU', 'FI']);
    });
});

describe('login page', function () {
    it('selects Russia by default', function () {
        Livewire::test(LoginPage::class)
            ->assertSet('country', 'RU')
            ->assertSeeHtml('<option value="FI">');
    });

    it('sends a code to a finnish number', function () {
        Livewire::test(LoginPage::class)
            ->set('country', 'FI')
            ->set('phone', '050 476 1410')
            ->set('consent', true)
            ->call('sendCode')
            ->assertRedirect(route('auth.verify'));

        expect(session('phone'))->toBe('358504761410');
        $this->assertDatabaseHas('verification_codes', ['phone' => '358504761410']);
    });

    it('keeps russian ten digit input working', function () {
        Livewire::test(LoginPage::class)
            ->set('phone', '9001234567')
            ->set('consent', true)
            ->call('sendCode')
            ->assertRedirect(route('auth.verify'));

        expect(session('phone'))->toBe('79001234567');
    });

    it('rejects a number that does not belong to the selected country', function () {
        Livewire::test(LoginPage::class)
            ->set('country', 'FI')
            ->set('phone', '9001234567')
            ->set('consent', true)
            ->call('sendCode')
            ->assertHasErrors('phone')
            ->assertNoRedirect();

        expect(VerificationCode::count())->toBe(0);
    });

    it('rejects a country outside the configured list', function () {
        config(['services.sms.allowed_countries' => 'RU']);

        Livewire::test(LoginPage::class)
            ->set('country', 'FI')
            ->set('phone', '050 476 1410')
            ->set('consent', true)
            ->call('sendCode')
            ->assertHasErrors('country');

        expect(VerificationCode::count())->toBe(0);
    });

    it('shows a foreign number on the verify page', function () {
        session(['phone' => '358504761410']);

        $this->get(route('auth.verify'))->assertSee('+358 50 4761410');
    });
});

describe('API', function () {
    it('accepts a foreign number with a plus and stores it normalized', function () {
        $this->postJson('/api/v1/auth/send-code', ['phone' => '+358 50 476 1410', 'consent' => true])
            ->assertOk();

        $this->assertDatabaseHas('verification_codes', ['phone' => '358504761410']);
    });

    it('still accepts russian digits without a plus', function () {
        $this->postJson('/api/v1/auth/send-code', ['phone' => '79001234567', 'consent' => true])
            ->assertOk();

        $this->assertDatabaseHas('verification_codes', ['phone' => '79001234567']);
    });

    it('rejects a landline', function () {
        $this->postJson('/api/v1/auth/send-code', ['phone' => '74951234567', 'consent' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    });
});
