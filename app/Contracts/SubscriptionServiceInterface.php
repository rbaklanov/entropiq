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

    /**
     * The only advice a Free user may read this week once the first one was opened,
     * null when nothing is restricted (Premium, or no advice opened yet this week).
     */
    public function openAdviceId(User $user): ?int;

    public function canViewAdvice(User $user, AiAdvice $advice): bool;

    public function canViewPeriod(User $user, Carbon $periodStart): bool;

    public function transactionsRemaining(User $user): ?int;

    public function goalsRemaining(User $user): ?int;

    public function subscribe(User $user, SubscriptionPlan $plan, bool $autoRenew = false): Subscription;

    /**
     * Extends a paid subscription by one more period after a successful renewal charge.
     */
    public function renew(Subscription $subscription): Subscription;

    /**
     * Stops future charges. Premium stays until the end of the paid period.
     */
    public function disableAutoRenew(User $user): void;

    /**
     * Ends a subscription whose paid period is over.
     */
    public function expire(Subscription $subscription): void;

    /**
     * Ends Premium at once, for example after a refund.
     */
    public function revoke(User $user): void;
}
