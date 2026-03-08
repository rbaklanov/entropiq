<?php

namespace App\Actions;

use App\Contracts\SmsServiceInterface;
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

        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $verificationCode = VerificationCode::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes(VerificationCode::EXPIRATION_MINUTES),
        ]);

        $this->smsService->sendVerificationCode($phone, $code);

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

    private function rateLimiterKey(string $phone): string
    {
        return 'sms:'.$phone;
    }
}
