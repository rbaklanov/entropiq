<?php

use App\Actions\SendVerificationCode;
use App\Contracts\SmsServiceInterface;
use App\Exceptions\SmsDeliveryException;
use App\Models\VerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('sms:79001234567');
    RateLimiter::clear('sms:79610893196');
    RateLimiter::clear('sms:79990000000');
});

it('stores a fixed code 1111 for any phone and does not send SMS by default', function () {
    $sms = Mockery::mock(SmsServiceInterface::class);
    $sms->shouldNotReceive('sendVerificationCode');

    $verificationCode = (new SendVerificationCode($sms))->execute('79610893196');

    expect($verificationCode)->toBeInstanceOf(VerificationCode::class)
        ->and($verificationCode->phone)->toBe('79610893196')
        ->and($verificationCode->code)->toBe('1111');
});

it('sends a verification code through the SMS contract when fixed code is disabled', function () {
    config(['services.sms.fixed_code' => false]);

    $sms = Mockery::mock(SmsServiceInterface::class);
    $sms->shouldReceive('sendVerificationCode')
        ->once()
        ->withArgs(function (string $phone, string $code) {
            expect($phone)->toBe('79001234567')
                ->and($code)->toMatch('/^\d{4}$/');

            return true;
        });

    $verificationCode = (new SendVerificationCode($sms))->execute('79001234567');

    expect($verificationCode)->toBeInstanceOf(VerificationCode::class)
        ->and($verificationCode->phone)->toBe('79001234567');
});

it('hides provider errors from the user and does not leak the OTP when SMS is enabled', function () {
    config(['services.sms.fixed_code' => false]);

    $sms = Mockery::mock(SmsServiceInterface::class);
    $sms->shouldReceive('sendVerificationCode')
        ->once()
        ->andThrow(new SmsDeliveryException);

    expect(fn () => (new SendVerificationCode($sms))->execute('79001234567'))
        ->toThrow(function (ValidationException $exception) {
            $messages = implode(' ', $exception->errors()['phone'] ?? []);

            expect($messages)->toBe(__('auth.sms_send_failed'))
                ->and($messages)->not->toContain('SMS delivery failed.');
        });

    $this->assertDatabaseMissing('verification_codes', [
        'phone' => '79001234567',
    ]);
});

it('stores a fixed code for the demo phone and does not send SMS even if fixed code is disabled', function () {
    config([
        'services.sms.fixed_code' => false,
        'services.sms.demo_phone' => '79990000000',
        'services.sms.demo_code' => '1111',
    ]);

    $sms = Mockery::mock(SmsServiceInterface::class);
    $sms->shouldNotReceive('sendVerificationCode');

    $verificationCode = (new SendVerificationCode($sms))->execute('79990000000');

    expect($verificationCode->phone)->toBe('79990000000')
        ->and($verificationCode->code)->toBe('1111');
});
