<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\SubscriptionServiceInterface;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Exceptions\PaymentGatewayException;
use App\Mail\RenewalReminderMail;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubscriptionRenewalService
{
    public const REMINDER_DAYS = 3;

    /**
     * How long a started renewal charge may wait for the payment notification before the subscription ends.
     */
    public const PENDING_GRACE_HOURS = 48;

    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly SubscriptionServiceInterface $subscriptions,
    ) {}

    /** @return array{charged: int, expired: int, waiting: int} */
    public function processDue(): array
    {
        $summary = ['charged' => 0, 'expired' => 0, 'waiting' => 0];

        Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->where('ends_at', '<=', now())
            ->with('user')
            ->each(function (Subscription $subscription) use (&$summary): void {
                $summary[$this->processSubscription($subscription)]++;
            });

        return $summary;
    }

    public function sendReminders(): int
    {
        $sent = 0;

        Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->where('auto_renew', true)
            ->whereNull('renewal_reminder_sent_at')
            ->whereBetween('ends_at', [now(), now()->addDays(self::REMINDER_DAYS)])
            ->with('user')
            ->each(function (Subscription $subscription) use (&$sent): void {
                if ($subscription->user->hasVerifiedEmail()) {
                    Mail::to($subscription->user->email)->send(new RenewalReminderMail($subscription));
                    $sent++;
                }

                $subscription->update(['renewal_reminder_sent_at' => now()]);
            });

        return $sent;
    }

    /** @return 'charged'|'expired'|'waiting' */
    private function processSubscription(Subscription $subscription): string
    {
        if (! $subscription->auto_renew) {
            return $this->expire($subscription);
        }

        $renewal = $this->renewalForCurrentPeriod($subscription);

        if ($renewal?->status === PaymentStatus::Failed) {
            return $this->expire($subscription);
        }

        if ($renewal?->status === PaymentStatus::Pending) {
            if ($renewal->created_at?->gt(now()->subHours(self::PENDING_GRACE_HOURS))) {
                return 'waiting';
            }

            $renewal->update(['status' => PaymentStatus::Failed]);

            return $this->expire($subscription);
        }

        $parent = $this->parentPayment($subscription);

        if ($parent === null) {
            return $this->expire($subscription);
        }

        $payment = Payment::create([
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'plan' => $subscription->plan,
            'parent_payment_id' => $parent->id,
            'amount' => $subscription->plan->price(),
            'currency_code' => 'RUB',
            'provider' => $this->gateway->provider(),
            'status' => PaymentStatus::Pending,
        ]);

        try {
            $this->gateway->chargeRecurring($payment);
        } catch (PaymentGatewayException $exception) {
            Log::warning('Subscription renewal charge failed', [
                'subscription_id' => $subscription->id,
                'payment_id' => $payment->id,
                'error' => $exception->getMessage(),
            ]);

            $payment->update(['status' => PaymentStatus::Failed]);

            return $this->expire($subscription);
        }

        return 'charged';
    }

    private function renewalForCurrentPeriod(Subscription $subscription): ?Payment
    {
        return Payment::query()
            ->where('subscription_id', $subscription->id)
            ->whereNotNull('parent_payment_id')
            ->where('created_at', '>=', $subscription->ends_at?->copy()->subDay())
            ->latest('id')
            ->first();
    }

    /**
     * The first payment where the buyer agreed to recurring charges. Every renewal refers to it.
     */
    private function parentPayment(Subscription $subscription): ?Payment
    {
        return Payment::query()
            ->where('user_id', $subscription->user_id)
            ->where('is_recurring', true)
            ->whereNull('parent_payment_id')
            ->where('status', PaymentStatus::Completed)
            ->latest('id')
            ->first();
    }

    private function expire(Subscription $subscription): string
    {
        $this->subscriptions->expire($subscription);

        return 'expired';
    }
}
