<?php

namespace App\Contracts;

use App\Enums\SubscriptionPlan;
use App\Models\Payment;
use App\Models\User;

interface PaymentServiceInterface
{
    public function charge(User $user, SubscriptionPlan $plan): Payment;

    public function refund(Payment $payment): Payment;
}
