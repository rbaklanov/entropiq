<?php

namespace App\Services;

use App\Contracts\PaymentServiceInterface;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Str;

class FakePaymentService implements PaymentServiceInterface
{
    private const PRICES = [
        'monthly' => 9900,
        'yearly' => 59000,
    ];

    public function charge(User $user, SubscriptionPlan $plan): Payment
    {
        $activeSubscription = $user->subscriptions()
            ->where('status', 'active')
            ->latest()
            ->first();

        return Payment::create([
            'user_id' => $user->id,
            'subscription_id' => $activeSubscription?->id,
            'amount' => self::PRICES[$plan->value] ?? 0,
            'currency_code' => $user->currency_code,
            'provider' => 'fake',
            'provider_payment_id' => 'fake_'.Str::uuid()->toString(),
            'status' => PaymentStatus::Completed,
            'paid_at' => now(),
        ]);
    }

    public function refund(Payment $payment): Payment
    {
        $payment->update([
            'status' => PaymentStatus::Refunded,
        ]);

        return $payment;
    }
}
