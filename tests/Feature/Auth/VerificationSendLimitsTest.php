<?php

use App\Actions\SendVerificationCode;
use App\Contracts\VerificationCodeSenderInterface;
use App\Exceptions\SmsDeliveryException;
use App\Mail\SmsBudgetAlertMail;
use App\Services\VerificationSendLimiter;
use App\Support\Seller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.sms.fixed_code' => false,
        'services.sms.demo_phone' => '79990000001',
        'services.sms.daily_limit' => 300,
        'services.admin.email' => 'admin@example.test',
    ]);

    Mail::fake();

    $this->sender = Mockery::mock(VerificationCodeSenderInterface::class);
    $this->sender->shouldReceive('sendVerificationCode')->byDefault();
    app()->instance(VerificationCodeSenderInterface::class, $this->sender);
});

function sendCodeTo(string $phone, ?string $ip = '10.0.0.1'): void
{
    RateLimiter::clear("sms:{$phone}");

    app(SendVerificationCode::class)->execute($phone, $ip);
}

function phoneErrorOf(Closure $send): string
{
    try {
        $send();
    } catch (ValidationException $exception) {
        return implode(' ', $exception->errors()['phone'] ?? []);
    }

    return '';
}

describe('per phone', function () {
    it('allows five codes a day to one number', function () {
        foreach (range(1, VerificationSendLimiter::PHONE_DAILY_LIMIT) as $attempt) {
            sendCodeTo('79001234567', "10.0.0.{$attempt}");
        }

        expect(phoneErrorOf(fn () => sendCodeTo('79001234567', '10.0.1.1')))
            ->toBe(__('auth.too_many_codes_phone'));
    });

    it('keeps the 60 second cooldown between codes', function () {
        app(SendVerificationCode::class)->execute('79001234567', '10.0.0.1');

        expect(phoneErrorOf(fn () => app(SendVerificationCode::class)->execute('79001234567', '10.0.0.1')))
            ->toContain('60');
    });
});

describe('per IP', function () {
    it('allows three codes in ten minutes from one address', function () {
        foreach (['79001234561', '79001234562', '79001234563'] as $phone) {
            sendCodeTo($phone);
        }

        expect(phoneErrorOf(fn () => sendCodeTo('79001234564')))
            ->toBe(__('auth.too_many_attempts', ['seconds' => VerificationSendLimiter::IP_SHORT_WINDOW_SECONDS]));
    });

    it('allows ten codes a day from one address', function () {
        foreach (range(10, 19) as $suffix) {
            RateLimiter::clear('sms-ip:10.0.0.1');
            sendCodeTo("790012345{$suffix}");
        }

        RateLimiter::clear('sms-ip:10.0.0.1');

        $error = phoneErrorOf(fn () => sendCodeTo('79001234520'));

        expect($error)->toBe(__('auth.too_many_attempts', ['seconds' => RateLimiter::availableIn('sms-ip-daily:10.0.0.1')]))
            ->and(app(VerificationSendLimiter::class)->sentToday())->toBe(VerificationSendLimiter::IP_DAILY_LIMIT);
    });

    it('does not count addresses against each other', function () {
        foreach (['79001234561', '79001234562', '79001234563'] as $phone) {
            sendCodeTo($phone, '10.0.0.1');
        }

        sendCodeTo('79001234564', '10.0.0.2');

        expect(app(VerificationSendLimiter::class)->sentToday())->toBe(4);
    });

    it('limits the API by the client address', function () {
        foreach (['79001234561', '79001234562', '79001234563'] as $phone) {
            $this->postJson('/api/v1/auth/send-code', ['phone' => $phone, 'consent' => true])->assertOk();
        }

        $this->postJson('/api/v1/auth/send-code', ['phone' => '79001234564', 'consent' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    });
});

describe('daily budget', function () {
    it('mails the admin once at 80 percent', function () {
        config(['services.sms.daily_limit' => 5]);

        foreach (['79001234561', '79001234562', '79001234563'] as $phone) {
            sendCodeTo($phone, null);
        }

        Mail::assertNothingSent();

        sendCodeTo('79001234564', null);
        sendCodeTo('79001234565', null);

        Mail::assertSent(SmsBudgetAlertMail::class, 1);
        Mail::assertSent(SmsBudgetAlertMail::class, fn (SmsBudgetAlertMail $mail) => ! $mail->exhausted && $mail->limit === 5);
    });

    it('stops sending when the budget is spent and tells the admin', function () {
        config(['services.sms.daily_limit' => 2]);

        sendCodeTo('79001234561', null);
        sendCodeTo('79001234562', null);

        $this->sender->shouldNotReceive('sendVerificationCode');

        expect(phoneErrorOf(fn () => sendCodeTo('79001234563', null)))
            ->toBe(__('auth.sms_budget_exhausted', ['email' => Seller::email()]));

        Mail::assertSent(SmsBudgetAlertMail::class, fn (SmsBudgetAlertMail $mail) => $mail->exhausted);
        $this->assertDatabaseMissing('verification_codes', ['phone' => '79001234563']);
    });

    it('does not count a failed delivery', function () {
        $this->sender->shouldReceive('sendVerificationCode')->andThrow(new SmsDeliveryException);

        phoneErrorOf(fn () => sendCodeTo('79001234561'));

        expect(app(VerificationSendLimiter::class)->sentToday())->toBe(0);
    });
});

describe('free deliveries', function () {
    it('does not limit the demo phone', function () {
        $this->sender->shouldNotReceive('sendVerificationCode');

        foreach (range(1, 12) as $attempt) {
            sendCodeTo('79990000001');
        }

        expect(app(VerificationSendLimiter::class)->sentToday())->toBe(0);
    });
});
