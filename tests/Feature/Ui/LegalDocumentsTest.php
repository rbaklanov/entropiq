<?php

use App\Enums\ConsentType;
use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\VerifyPage;
use App\Models\Consent;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function configureSeller(?string $inn = '123456789012'): void
{
    config()->set('app.seller', [
        'name' => 'Бакланова Елизавета Романовна',
        'inn' => $inn,
        'phone' => null,
        'email' => 'support@entropiq.ru',
    ]);
}

describe('public offer', function () {
    it('names the seller, prices, renewal and refund terms', function () {
        configureSeller();
        app()->setLocale('ru');

        $this->get('/offer')->assertOk()
            ->assertSee('Бакланова Елизавета Романовна, ИНН 123456789012, плательщик НПД')
            ->assertSee('99 ₽ за один месяц')
            ->assertSee('590 ₽ за один год')
            ->assertSee('Автоматическое продление')
            ->assertSee('Отказ от автоматического продления')
            ->assertSee('В течение 7 календарных дней')
            ->assertSee('неиспользованных полных дней')
            ->assertSee('support@entropiq.ru')
            ->assertDontSee(':seller')
            ->assertDontSee(':email')
            ->assertDontSee(':monthly_price');
    });

    it('renders in English with the same structure', function () {
        configureSeller();
        app()->setLocale('en');

        $this->get('/offer')->assertOk()->assertSee('Public offer')->assertSee('99 ₽ for one month');

        $ru = trans('offer.sections', [], 'ru');
        $en = trans('offer.sections', [], 'en');

        expect($ru)->toHaveCount(count($en));

        foreach ($ru as $index => $section) {
            expect($section['items'])->toHaveCount(count($en[$index]['items']));
        }
    });

    it('says details are pending while the seller name is not configured', function () {
        config()->set('app.seller.name', null);
        app()->setLocale('ru');

        $this->get('/offer')->assertOk()->assertSee('реквизиты будут опубликованы до начала приёма платежей');
    });

    it('marks the INN as pending while only the name is configured', function () {
        configureSeller(inn: null);
        app()->setLocale('ru');

        $this->get('/offer')->assertOk()->assertSee('Бакланова Елизавета Романовна, ИНН будет указан до начала приёма платежей');
    });
});

describe('personal data consent document', function () {
    it('names the operator, data, purposes, recipients and withdrawal', function () {
        configureSeller();
        app()->setLocale('ru');

        $this->get('/consent')->assertOk()
            ->assertSee('Согласие на обработку персональных данных')
            ->assertSee('Бакланова Елизавета Романовна, ИНН 123456789012')
            ->assertSee('Номер телефона.')
            ->assertSee('SMS Aero')
            ->assertSee('Робокасса')
            ->assertSee('Я могу отозвать согласие')
            ->assertSee('19.10.2026');
    });

    it('has the same structure in Russian and English', function () {
        $ru = trans('consent.sections', [], 'ru');
        $en = trans('consent.sections', [], 'en');

        expect($ru)->toHaveCount(count($en));

        foreach ($ru as $index => $section) {
            expect($section['items'])->toHaveCount(count($en[$index]['items']));
        }
    });

    it('is linked from the footer together with the offer', function () {
        $this->get('/')->assertOk()
            ->assertSee(route('offer'), false)
            ->assertSee(route('consent'), false);
    });
});

describe('privacy policy and terms', function () {
    it('names the seller as operator and the payment service', function () {
        configureSeller();
        app()->setLocale('ru');

        $this->get('/privacy')->assertOk()
            ->assertSee('Оператор персональных данных: Бакланова Елизавета Романовна')
            ->assertSee('Робокасса')
            ->assertSee('Отозвать согласие')
            ->assertDontSee('Используя Сервис, вы соглашаетесь с условиями настоящей Политики');
    });

    it('points terms to the offer for payment and refunds', function () {
        app()->setLocale('ru');

        $this->get('/terms')->assertOk()
            ->assertSee('определяются Публичной офертой')
            ->assertSee('позже за неиспользованные дни');
    });
});

describe('consent on the web sign in', function () {
    it('does not send a code without consent', function () {
        Livewire::test(LoginPage::class)
            ->set('phone', '79001234567')
            ->call('sendCode')
            ->assertHasErrors(['consent' => 'accepted']);

        expect(VerificationCode::where('phone', '79001234567')->exists())->toBeFalse();
    });

    it('shows the consent checkbox with a link to the document', function () {
        $this->get('/login')->assertOk()
            ->assertSee('wire:model="consent"', false)
            ->assertSee(route('consent'), false);
    });

    it('records the consent with version and time after the code is verified', function () {
        $this->travelTo(now()->setDateTime(2026, 10, 19, 12, 0));

        Livewire::test(LoginPage::class)
            ->set('phone', '79001234567')
            ->set('consent', true)
            ->call('sendCode');

        VerificationCode::where('phone', '79001234567')->update(['code' => '1234']);

        $this->travel(2)->minutes();

        Livewire::test(VerifyPage::class)
            ->set('code', '1234')
            ->call('verify');

        $consent = Consent::sole();

        expect($consent->user->phone)->toBe('79001234567')
            ->and($consent->type)->toBe(ConsentType::PersonalData)
            ->and($consent->version)->toBe(ConsentType::PersonalData->currentVersion())
            ->and($consent->accepted_at->format('Y-m-d H:i'))->toBe('2026-10-19 12:00')
            ->and($consent->ip_address)->not->toBeNull()
            ->and(session()->has('consent'))->toBeFalse();
    });

    it('does not duplicate the consent for the same version on the next sign in', function () {
        $user = User::factory()->create(['phone' => '79001234567', 'phone_verified_at' => now()]);

        foreach ([1, 2] as $attempt) {
            session()->put('phone', '79001234567');
            session()->put('consent', ['accepted_at' => now()->toIso8601String(), 'ip_address' => '127.0.0.1', 'user_agent' => 'test']);
            VerificationCode::factory()->create(['phone' => '79001234567', 'code' => '1234']);

            Livewire::test(VerifyPage::class)->set('code', '1234')->call('verify');

            auth()->logout();
        }

        expect($user->consents()->count())->toBe(1);
    });
});

describe('consent in the API sign in', function () {
    it('rejects send-code and verify-code without consent', function () {
        $this->postJson('/api/v1/auth/send-code', ['phone' => '79001234567'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('consent');

        $this->postJson('/api/v1/auth/verify-code', ['phone' => '79001234567', 'code' => '1234'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('consent');
    });

    it('records the consent on successful verification', function () {
        VerificationCode::factory()->create(['phone' => '79001234567', 'code' => '1234']);

        $this->postJson('/api/v1/auth/verify-code', ['phone' => '79001234567', 'code' => '1234', 'consent' => true], ['User-Agent' => 'EntropiqApp/1.0'])
            ->assertOk();

        $consent = Consent::sole();

        expect($consent->type)->toBe(ConsentType::PersonalData)
            ->and($consent->user_agent)->toBe('EntropiqApp/1.0');
    });
});
