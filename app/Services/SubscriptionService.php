<?php

namespace App\Services;

use App\Contracts\SubscriptionServiceInterface;
use App\Models\User;

class SubscriptionService implements SubscriptionServiceInterface
{
    private const FREE_MONTHLY_TRANSACTION_LIMIT = 50;

    private const FREE_GOAL_LIMIT = 1;

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
}
