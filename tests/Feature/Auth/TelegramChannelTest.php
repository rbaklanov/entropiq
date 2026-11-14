<?php

use App\Actions\SendVerificationCode;
use App\Contracts\VerificationCodeSenderInterface;
use App\Contracts\VerificationDeliveryCheckerInterface;
use App\Enums\VerificationDeliveryStatus;
use App\Exceptions\SmsDeliveryException;
use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\VerifyPage;
use App\Services\VerificationSendLimiter;
use App\Support\PhoneNumbers;
use App\Support\Seller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('sms:79001234567');
    config(['services.sms.driver' => 'sms_aero_telegram']);
});

describe('sign-in with codes in Telegram', function () {
    it('tells on the login page that the code arrives in Telegram', function () {
        Livewire::test(LoginPage::class)
            ->assertSee(__('auth.login_hint_telegram'));
    });

    it('hides the Telegram hint when codes go by SMS', function () {
        config(['services.sms.driver' => 'sms_aero']);

        Livewire::test(LoginPage::class)
            ->assertDontSee(__('auth.login_hint_telegram'));
    });

    it('asks for the code from Telegram on the verify page', function () {
        session()->put('phone', '79001234567');

        Livewire::test(VerifyPage::class)
            ->assertSee(__('auth.verify_title_telegram'))
            ->assertSee(__('auth.sms_code_telegram'))
            ->assertSeeHtml('<span class="block whitespace-nowrap">'.PhoneNumbers::format('79001234567').'</span>')
            ->assertDontSee(__('auth.verify_title'));
    });

    it('confirms the sent code with the phone on its own line', function () {
        session()->put('phone', '79001234567');
        session()->flash('code_sent', true);

        Livewire::test(VerifyPage::class)
            ->assertSee(__('auth.code_sent_to_telegram'))
            ->assertDontSee(__('auth.code_sent_to'));
    });

    it('confirms the sent code after a resend', function () {
        session()->put('phone', '79001234567');

        Livewire::test(VerifyPage::class)
            ->call('resendCode')
            ->assertSee(__('auth.code_sent_to_telegram'));
    });

    it('reports a Telegram delivery failure without mentioning SMS', function () {
        config(['services.sms.fixed_code' => false]);

        $sender = Mockery::mock(VerificationCodeSenderInterface::class);
        $sender->shouldReceive('sendVerificationCode')->once()->andThrow(new SmsDeliveryException);

        expect(fn () => (new SendVerificationCode($sender, app(VerificationSendLimiter::class)))->execute('79001234567'))
            ->toThrow(function (ValidationException $exception) {
                expect($exception->errors()['phone'])->toBe([__('auth.sms_send_failed_telegram')]);
            });
    });

    it('generates codes without a leading zero', function () {
        config(['services.sms.fixed_code' => false]);

        $codes = [];
        $sender = Mockery::mock(VerificationCodeSenderInterface::class);
        $sender->shouldReceive('sendVerificationCode')
            ->times(50)
            ->andReturnUsing(function (string $phone, string $code) use (&$codes) {
                $codes[] = $code;
            });

        foreach (range(1, 50) as $attempt) {
            RateLimiter::clear('sms:79001234567');
            RateLimiter::clear('sms-daily:79001234567');
            (new SendVerificationCode($sender, app(VerificationSendLimiter::class)))->execute('79001234567');
        }

        expect($codes)->each->toMatch('/^[1-9]\d{3}$/');
    });
});

function fakeDeliveryStatuses(VerificationDeliveryStatus ...$statuses): void
{
    $checker = Mockery::mock(VerificationDeliveryCheckerInterface::class);
    $checker->shouldReceive('deliveryStatus')->with('79001234567')->andReturn(...$statuses);

    app()->instance(VerificationDeliveryCheckerInterface::class, $checker);
}

describe('Telegram delivery check on the verify page', function () {
    beforeEach(function () {
        session()->put('phone', '79001234567');
    });

    it('polls while the code is on its way', function () {
        fakeDeliveryStatuses(VerificationDeliveryStatus::Pending);

        Livewire::test(VerifyPage::class)
            ->assertSet('watchDelivery', true)
            ->call('checkDelivery')
            ->assertSet('watchDelivery', true)
            ->assertSet('codeUndelivered', false)
            ->assertSeeHtml('wire:poll.5s="checkDelivery"')
            ->assertSeeHtml('wire:submit="verify"');
    });

    it('warns that the number has no Telegram and stops polling', function () {
        fakeDeliveryStatuses(VerificationDeliveryStatus::Pending, VerificationDeliveryStatus::Undelivered);

        Livewire::test(VerifyPage::class)
            ->call('checkDelivery')
            ->call('checkDelivery')
            ->assertSet('codeUndelivered', true)
            ->assertSet('watchDelivery', false)
            ->assertSee(__('auth.telegram_undelivered', ['email' => Seller::email()]))
            ->assertDontSeeHtml('wire:poll.5s="checkDelivery"')
            ->assertDontSeeHtml('wire:submit="verify"')
            ->assertDontSee(__('auth.resend_code'))
            ->assertSee(__('auth.change_number'));
    });

    it('stops polling once the code is delivered', function () {
        fakeDeliveryStatuses(VerificationDeliveryStatus::Delivered);

        Livewire::test(VerifyPage::class)
            ->call('checkDelivery')
            ->assertSet('watchDelivery', false)
            ->assertSet('codeUndelivered', false);
    });

    it('gives up after the maximum number of checks', function () {
        fakeDeliveryStatuses(VerificationDeliveryStatus::Pending);

        $component = Livewire::test(VerifyPage::class);

        foreach (range(1, VerifyPage::MAX_DELIVERY_CHECKS) as $check) {
            $component->call('checkDelivery');
        }

        $component->assertSet('watchDelivery', false)
            ->assertSet('codeUndelivered', false);
    });

    it('clears the warning and polls again after a resend', function () {
        fakeDeliveryStatuses(VerificationDeliveryStatus::Undelivered);

        Livewire::test(VerifyPage::class)
            ->call('checkDelivery')
            ->assertSet('codeUndelivered', true)
            ->call('resendCode')
            ->assertSet('codeUndelivered', false)
            ->assertSet('watchDelivery', true);
    });

    it('does not poll when codes go by SMS', function () {
        config(['services.sms.driver' => 'sms_aero']);

        Livewire::test(VerifyPage::class)
            ->assertSet('watchDelivery', false)
            ->assertDontSeeHtml('wire:poll.5s="checkDelivery"');
    });
});
