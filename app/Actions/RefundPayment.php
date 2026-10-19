<?php

namespace App\Actions;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\SubscriptionServiceInterface;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class RefundPayment
{
    public const FULL_REFUND_DAYS = 7;

    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly SubscriptionServiceInterface $subscriptions,
    ) {}

    /**
     * Refund rules from the public offer: the whole payment within 7 days, later the unused full days of the period.
     */
    public function refundableAmount(Payment $payment, ?Carbon $at = null): int
    {
        $at ??= now();

        if ($payment->status !== PaymentStatus::Completed || $payment->paid_at === null) {
            return 0;
        }

        $remaining = $payment->amount - $payment->refunded_amount;

        if ($payment->paid_at->copy()->addDays(self::FULL_REFUND_DAYS)->gte($at)) {
            return $remaining;
        }

        $periodEnd = $payment->subscription?->ends_at;

        if ($periodEnd === null || $periodEnd->lte($at) || ! $this->isLatestPayment($payment)) {
            return 0;
        }

        $periodStart = ($payment->plan ?? SubscriptionPlan::Monthly)->periodStart($periodEnd);
        $periodDays = max(1, (int) $periodStart->diffInDays($periodEnd));
        $unusedDays = (int) floor($at->diffInDays($periodEnd));

        return min($remaining, intdiv($payment->amount * $unusedDays, $periodDays));
    }

    public function execute(Payment $payment, ?int $amount = null): Payment
    {
        $amount ??= $this->refundableAmount($payment);

        if ($amount <= 0) {
            throw new InvalidArgumentException("Payment #{$payment->id} has nothing to refund.");
        }

        if ($amount > $payment->amount - $payment->refunded_amount) {
            throw new InvalidArgumentException("Refund for payment #{$payment->id} exceeds the paid amount.");
        }

        $requestId = $this->gateway->refund($payment, $amount);

        $payment->update([
            'refunded_amount' => $payment->refunded_amount + $amount,
            'refund_request_id' => $requestId,
            'refunded_at' => now(),
            'status' => PaymentStatus::Refunded,
        ]);

        $this->subscriptions->revoke($payment->user);

        return $payment;
    }

    private function isLatestPayment(Payment $payment): bool
    {
        return ! Payment::query()
            ->where('subscription_id', $payment->subscription_id)
            ->where('status', PaymentStatus::Completed)
            ->where('paid_at', '>', $payment->paid_at)
            ->exists();
    }
}
