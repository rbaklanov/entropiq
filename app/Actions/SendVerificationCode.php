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

        if (! $this->isDemoPhone($phone)) {
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
        if ($this->isDemoPhone($phone)) {
            return $this->demoCode();
        }

        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function isDemoPhone(string $phone): bool
    {
        $demoPhone = config('services.sms.demo_phone');

        return is_string($demoPhone) && $demoPhone !== '' && $demoPhone === $phone;
    }

    private function demoCode(): string
    {
        $code = config('services.sms.demo_code');

        if (is_string($code) && preg_match('/^\d{4}$/', $code) === 1) {
            return $code;
        }

        return '1111';
    }

    private function rateLimiterKey(string $phone): string
    {
        return 'sms:'.$phone;
    }
}
