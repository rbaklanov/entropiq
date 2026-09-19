<?php

namespace App\Actions;

use App\Contracts\SmsServiceInterface;
use App\Exceptions\SmsDeliveryException;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Random\RandomException;

readonly class SendVerificationCode
{
    public const DEFAULT_OTP_CODE = '1111';

    public function __construct(
        private SmsServiceInterface $smsService,
    ) {}

    /**
     * @throws RandomException
     * @throws ValidationException
     */
    public function execute(string $phone): VerificationCode
    {
        $this->ensureNotRateLimited($phone);

        VerificationCode::forPhone($phone)->active()->delete();

        $code = $this->codeFor($phone);

        $verificationCode = VerificationCode::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes(VerificationCode::EXPIRATION_MINUTES),
        ]);

        if (! $this->shouldSkipSms($phone)) {
            try {
                $this->smsService->sendVerificationCode($phone, $code);
            } catch (SmsDeliveryException) {
                $verificationCode->delete();

                throw ValidationException::withMessages([
                    'phone' => [__('auth.sms_send_failed')],
                ]);
            }
        }

        RateLimiter::hit($this->rateLimiterKey($phone), VerificationCode::RESEND_COOLDOWN_SECONDS);

        return $verificationCode;
    }

    /** @throws ValidationException */
    private function ensureNotRateLimited(string $phone): void
    {
        $key = $this->rateLimiterKey($phone);

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'phone' => [__('auth.too_many_attempts', ['seconds' => $seconds])],
            ]);
        }
    }

    private function codeFor(string $phone): string
    {
        if ($this->isFixedOtpEnabled() || $this->isDemoPhone($phone)) {
            return $this->fixedCode();
        }

        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function shouldSkipSms(string $phone): bool
    {
        return $this->isFixedOtpEnabled() || $this->isDemoPhone($phone);
    }

    private function isFixedOtpEnabled(): bool
    {
        return (bool) config('services.sms.fixed_code', true);
    }

    private function isDemoPhone(string $phone): bool
    {
        $demoPhone = config('services.sms.demo_phone');

        return is_string($demoPhone) && $demoPhone !== '' && $demoPhone === $phone;
    }

    private function fixedCode(): string
    {
        $code = config('services.sms.demo_code');

        if (is_string($code) && preg_match('/^\d{4}$/', $code) === 1) {
            return $code;
        }

        return self::DEFAULT_OTP_CODE;
    }

    private function rateLimiterKey(string $phone): string
    {
        return 'sms:'.$phone;
    }
}
