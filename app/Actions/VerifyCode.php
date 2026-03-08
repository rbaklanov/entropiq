<?php

namespace App\Actions;

use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class VerifyCode
{
    /**
     * @throws ValidationException
     */
    public function execute(string $phone, string $code): User
    {
        $this->ensureNotRateLimited($phone);

        RateLimiter::hit($this->rateLimiterKey($phone));

        $verificationCode = VerificationCode::forPhone($phone)
            ->active()
            ->latest()
            ->first();

        if (! $verificationCode) {
            throw ValidationException::withMessages([
                'code' => [__('auth.code_expired')],
            ]);
        }

        $verificationCode->incrementAttempts();

        if ($verificationCode->code !== $code) {
            $remaining = VerificationCode::MAX_ATTEMPTS - $verificationCode->attempts;

            throw ValidationException::withMessages([
                'code' => [$remaining > 0
                    ? __('auth.invalid_code')
                    : __('auth.code_expired')],
            ]);
        }

        $verificationCode->markAsVerified();

        RateLimiter::clear($this->rateLimiterKey($phone));

        return $this->findOrCreateUser($phone);
    }

    private function findOrCreateUser(string $phone): User
    {
        return User::firstOrCreate(
            ['phone' => $phone],
            ['phone_verified_at' => now()],
        );
    }

    /** @throws ValidationException */
    private function ensureNotRateLimited(string $phone): void
    {
        $key = $this->rateLimiterKey($phone);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'code' => [__('auth.too_many_attempts', ['seconds' => $seconds])],
            ]);
        }
    }

    private function rateLimiterKey(string $phone): string
    {
        return 'verify:'.$phone;
    }
}
