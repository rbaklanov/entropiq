<?php

namespace App\Contracts;

use App\Enums\SubscriptionPlan;
use App\Models\AiAdvice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;

interface SubscriptionServiceInterface
{
    public function isPremium(User $user): bool;

    public function canAddTransaction(User $user): bool;

    public function canCreateGoal(User $user): bool;

    public function canViewAdvice(User $user, AiAdvice $advice): bool;

    public function canViewPeriod(User $user, Carbon $periodStart): bool;

    public function transactionsRemaining(User $user): ?int;

    public function goalsRemaining(User $user): ?int;

    public function subscribe(User $user, SubscriptionPlan $plan): Subscription;

    public function cancel(User $user): void;
}
