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
});

it('sends a verification code through the SMS contract', function () {
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

it('hides provider errors from the user and does not leak the OTP', function () {
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
