<?php

namespace App\Contracts;

use App\Models\User;

interface SubscriptionServiceInterface
{
    public function canAddTransaction(User $user): bool;

    public function canCreateGoal(User $user): bool;

    public function transactionsRemaining(User $user): ?int;

    public function goalsRemaining(User $user): ?int;
}
