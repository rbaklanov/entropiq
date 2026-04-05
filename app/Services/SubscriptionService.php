<?php

namespace App\Services;

use App\Contracts\SubscriptionServiceInterface;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\AiAdvice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;

class SubscriptionService implements SubscriptionServiceInterface
{
    private const FREE_MONTHLY_TRANSACTION_LIMIT = 50;

    private const FREE_GOAL_LIMIT = 1;

    private const FREE_PERIOD_MONTHS = 1;

    public function isPremium(User $user): bool
    {
        return $user->isPremium();
    }

    public function canAddTransaction(User $user): bool
    {
        if ($user->isPremium()) {
            return true;
        }

        return $this->transactionsRemaining($user) > 0;
    }

    public function canCreateGoal(User $user): bool
    {
        if ($user->isPremium()) {
            return true;
        }

        return $this->goalsRemaining($user) > 0;
    }

    public function canViewAdvice(User $user, AiAdvice $advice): bool
    {
        if ($user->isPremium()) {
            return true;
        }

        $weekStart = Carbon::now()->startOfWeek();

        $firstViewedThisWeek = AiAdvice::where('user_id', $user->id)
            ->where('is_read', true)
            ->where('updated_at', '>=', $weekStart)
            ->orderBy('updated_at')
            ->first();

        if (! $firstViewedThisWeek) {
            return true;
        }

        return $firstViewedThisWeek->id === $advice->id;
    }

    public function canViewPeriod(User $user, Carbon $periodStart): bool
    {
        if ($user->isPremium()) {
            return true;
        }

        $allowedStart = Carbon::now()->subMonths(self::FREE_PERIOD_MONTHS)->startOfMonth();

        return $periodStart->gte($allowedStart);
    }

    public function transactionsRemaining(User $user): ?int
    {
        if ($user->isPremium()) {
            return null;
        }

        $count = $user->transactions()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();

        return max(0, self::FREE_MONTHLY_TRANSACTION_LIMIT - $count);
    }

    public function goalsRemaining(User $user): ?int
    {
        if ($user->isPremium()) {
            return null;
        }

        $count = $user->goals()->active()->count();

        return max(0, self::FREE_GOAL_LIMIT - $count);
    }

    public function subscribe(User $user, SubscriptionPlan $plan): Subscription
    {
        $this->cancelActiveSubscriptions($user);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan' => $plan,
            'status' => SubscriptionStatus::Active,
            'starts_at' => Carbon::now(),
            'ends_at' => $plan === SubscriptionPlan::Yearly
                ? Carbon::now()->addYear()
                : Carbon::now()->addMonth(),
        ]);

        $user->update(['subscription_plan' => $plan]);

        return $subscription;
    }

    public function cancel(User $user): void
    {
        $this->cancelActiveSubscriptions($user);

        $user->update(['subscription_plan' => SubscriptionPlan::Free]);
    }

    private function cancelActiveSubscriptions(User $user): void
    {
        $user->subscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->each(function (Subscription $subscription): void {
                $subscription->update(['status' => SubscriptionStatus::Cancelled]);
            });
    }
}
