<?php

namespace App\Services;

use App\Contracts\AdminAlertServiceInterface;
use App\Mail\SmsBudgetAlertMail;
use App\Support\Seller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Limits paid code deliveries: per phone and per IP against brute force and pumping,
 * and a daily budget for the whole service against draining the SMS Aero balance.
 * The 60 second resend cooldown per phone lives in {@see \App\Actions\SendVerificationCode}.
 */
class VerificationSendLimiter
{
    public const PHONE_DAILY_LIMIT = 5;

    public const IP_SHORT_LIMIT = 3;

    public const IP_SHORT_WINDOW_SECONDS = 600;

    public const IP_DAILY_LIMIT = 10;

    private const DAY_SECONDS = 86400;

    private const BUDGET_WARNING_SHARE = 0.8;

    public function __construct(
        private AdminAlertServiceInterface $adminAlerts,
    ) {}

    /** @throws ValidationException */
    public function ensureCanSend(string $phone, ?string $ip): void
    {
        if (RateLimiter::tooManyAttempts($this->phoneDailyKey($phone), self::PHONE_DAILY_LIMIT)) {
            $this->fail(__('auth.too_many_codes_phone'));
        }

        if ($ip !== null) {
            $this->ensureIpNotLimited($this->ipShortKey($ip), self::IP_SHORT_LIMIT);
            $this->ensureIpNotLimited($this->ipDailyKey($ip), self::IP_DAILY_LIMIT);
        }

        if ($this->sentToday() >= $this->dailyBudget()) {
            $this->alert(exhausted: true);

            $this->fail(__('auth.sms_budget_exhausted', ['email' => Seller::email()]));
        }
    }

    public function recordSent(string $phone, ?string $ip): void
    {
        RateLimiter::hit($this->phoneDailyKey($phone), self::DAY_SECONDS);

        if ($ip !== null) {
            RateLimiter::hit($this->ipShortKey($ip), self::IP_SHORT_WINDOW_SECONDS);
            RateLimiter::hit($this->ipDailyKey($ip), self::DAY_SECONDS);
        }

        Cache::add($this->budgetKey(), 0, now()->addDays(2));
        $sent = (int) Cache::increment($this->budgetKey());

        if ($sent >= (int) ceil($this->dailyBudget() * self::BUDGET_WARNING_SHARE)) {
            $this->alert(exhausted: false);
        }
    }

    public function sentToday(): int
    {
        $sent = Cache::get($this->budgetKey(), 0);

        return is_numeric($sent) ? (int) $sent : 0;
    }

    public function dailyBudget(): int
    {
        $budget = config('services.sms.daily_limit', 300);

        return is_numeric($budget) ? max(0, (int) $budget) : 300;
    }

    /** @throws ValidationException */
    private function ensureIpNotLimited(string $key, int $maxAttempts): void
    {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $this->fail(__('auth.too_many_attempts', ['seconds' => RateLimiter::availableIn($key)]));
        }
    }

    /** @throws ValidationException */
    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['phone' => [$message]]);
    }

    private function alert(bool $exhausted): void
    {
        $level = $exhausted ? 'exhausted' : 'warning';

        $this->adminAlerts->send(
            new SmsBudgetAlertMail($this->sentToday(), $this->dailyBudget(), $exhausted),
            "sms-budget:{$level}:".now()->toDateString(),
            24,
        );
    }

    private function phoneDailyKey(string $phone): string
    {
        return "sms-daily:{$phone}";
    }

    private function ipShortKey(string $ip): string
    {
        return "sms-ip:{$ip}";
    }

    private function ipDailyKey(string $ip): string
    {
        return "sms-ip-daily:{$ip}";
    }

    private function budgetKey(): string
    {
        return 'sms-budget:'.now()->toDateString();
    }
}
