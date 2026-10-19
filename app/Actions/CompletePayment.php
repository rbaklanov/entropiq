<?php

namespace App\Actions;

use App\Contracts\SubscriptionServiceInterface;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class CompletePayment
{
    public function __construct(private readonly SubscriptionServiceInterface $subscriptions) {}

    /**
     * Marks a confirmed payment as paid and grants or extends Premium. Safe to call again for the same payment,
     * because the provider may repeat its notification.
     */
    public function execute(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment): Payment {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status === PaymentStatus::Completed) {
                return $payment;
            }

            $subscription = $payment->parent_payment_id !== null && $payment->subscription !== null
                ? $this->subscriptions->renew($payment->subscription)
                : $this->subscriptions->subscribe($payment->user, $payment->plan ?? SubscriptionPlan::Monthly, $payment->is_recurring);

            $payment->update([
                'status' => PaymentStatus::Completed,
                'paid_at' => now(),
                'subscription_id' => $subscription->id,
            ]);

            return $payment;
        });
    }
}
