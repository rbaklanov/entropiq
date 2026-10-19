<?php

use App\Actions\RefundPayment;
use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Exceptions\PaymentGatewayException;
use App\Mail\RenewalReminderMail;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionRenewalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.payments.driver', 'fake');
    $this->travelTo(now()->setDateTime(2026, 10, 19, 12, 0));
});

/** @return array{User, Subscription, Payment} */
function paidSubscription(bool $autoRenew = true, string $endsAt = '2026-10-19 11:00', ?User $user = null): array
{
    $user ??= User::factory()->create(['subscription_plan' => SubscriptionPlan::Monthly]);
    $subscription = Subscription::factory()->for($user)->create([
        'auto_renew' => $autoRenew,
        'starts_at' => '2026-09-19 11:00',
        'ends_at' => $endsAt,
    ]);
    $payment = Payment::factory()->for($user)->create([
        'subscription_id' => $subscription->id,
        'plan' => SubscriptionPlan::Monthly,
        'amount' => 9900,
        'is_recurring' => $autoRenew,
        'provider' => 'fake',
        'paid_at' => '2026-09-19 11:00',
    ]);

    return [$user, $subscription, $payment];
}

function failingGateway(): PaymentGatewayInterface
{
    return new class implements PaymentGatewayInterface
    {
        public function provider(): string
        {
            return 'robokassa';
        }

        public function checkoutUrl(Payment $payment): string
        {
            return 'https://example.com';
        }

        public function chargeRecurring(Payment $payment): void
        {
            throw new PaymentGatewayException('card declined');
        }

        public function refund(Payment $payment, int $amount): string
        {
            throw new PaymentGatewayException('refund failed');
        }
    };
}

describe('renewal of ended subscriptions', function () {
    it('expires a subscription without auto renewal and moves the user to Free', function () {
        [$user, $subscription] = paidSubscription(autoRenew: false);

        $this->artisan('subscriptions:renew')->assertSuccessful();

        expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Expired)
            ->and($user->fresh()->subscription_plan)->toBe(SubscriptionPlan::Free)
            ->and(Payment::count())->toBe(1);
    });

    it('charges a renewal referring to the first recurring payment and extends the period', function () {
        [$user, $subscription, $parent] = paidSubscription();

        $this->artisan('subscriptions:renew')->assertSuccessful();

        $renewal = Payment::where('parent_payment_id', $parent->id)->sole();

        expect($renewal->status)->toBe(PaymentStatus::Completed)
            ->and($renewal->amount)->toBe(9900)
            ->and($renewal->subscription_id)->toBe($subscription->id)
            ->and($subscription->fresh()->ends_at->toDateString())->toBe('2026-11-19')
            ->and($user->fresh()->isPremium())->toBeTrue();
    });

    it('leaves active subscriptions that have not ended yet', function () {
        [, $subscription] = paidSubscription(endsAt: '2026-10-25 12:00');

        $this->artisan('subscriptions:renew')->assertSuccessful();

        expect(Payment::count())->toBe(1)
            ->and($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
    });

    it('expires the subscription when the charge is declined', function () {
        $this->app->instance(PaymentGatewayInterface::class, failingGateway());
        [$user, $subscription] = paidSubscription();

        $this->artisan('subscriptions:renew')->assertSuccessful();

        expect(Payment::whereNotNull('parent_payment_id')->sole()->status)->toBe(PaymentStatus::Failed)
            ->and($subscription->fresh()->status)->toBe(SubscriptionStatus::Expired)
            ->and($user->fresh()->subscription_plan)->toBe(SubscriptionPlan::Free);
    });

    it('waits for the payment notification and gives up after the grace period', function () {
        [$user, $subscription, $parent] = paidSubscription();
        $pending = Payment::factory()->for($user)->pending()->create([
            'subscription_id' => $subscription->id,
            'parent_payment_id' => $parent->id,
            'amount' => 9900,
            'created_at' => now()->subHour(),
        ]);

        expect(app(SubscriptionRenewalService::class)->processDue())->toBe(['charged' => 0, 'expired' => 0, 'waiting' => 1]);

        $this->travel(SubscriptionRenewalService::PENDING_GRACE_HOURS)->hours();

        expect(app(SubscriptionRenewalService::class)->processDue())->toBe(['charged' => 0, 'expired' => 1, 'waiting' => 0])
            ->and($pending->fresh()->status)->toBe(PaymentStatus::Failed)
            ->and($user->fresh()->subscription_plan)->toBe(SubscriptionPlan::Free);
    });

    it('expires when there is no payment with consent to recurring charges', function () {
        [$user, $subscription, $payment] = paidSubscription();
        $payment->update(['is_recurring' => false]);

        $this->artisan('subscriptions:renew')->assertSuccessful();

        expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Expired)
            ->and(Payment::count())->toBe(1);
    });
});

describe('renewal reminders', function () {
    it('emails a confirmed address three days before the charge, once', function () {
        Mail::fake();
        $user = User::factory()->withVerifiedEmail('buyer@example.com')->create(['subscription_plan' => SubscriptionPlan::Monthly]);
        [, $subscription] = paidSubscription(endsAt: '2026-10-21 12:00', user: $user);

        $this->artisan('subscriptions:remind-renewals')->assertSuccessful();
        $this->artisan('subscriptions:remind-renewals')->assertSuccessful();

        Mail::assertSent(RenewalReminderMail::class, 1);
        Mail::assertSent(RenewalReminderMail::class, fn (RenewalReminderMail $mail) => $mail->hasTo('buyer@example.com'));

        expect($subscription->fresh()->renewal_reminder_sent_at)->not->toBeNull();
    });

    it('does not remind when auto renewal is off, the end is far or the email is not confirmed', function () {
        Mail::fake();
        paidSubscription(autoRenew: false, endsAt: '2026-10-21 12:00', user: User::factory()->withVerifiedEmail('a@example.com')->create());
        paidSubscription(endsAt: '2026-11-10 12:00', user: User::factory()->withVerifiedEmail('b@example.com')->create());
        paidSubscription(endsAt: '2026-10-21 12:00', user: User::factory()->create(['email' => 'c@example.com', 'email_verified_at' => null]));

        $this->artisan('subscriptions:remind-renewals')->assertSuccessful();

        Mail::assertNothingSent();
    });

    it('renders the amount and the date in the reminder', function () {
        app()->setLocale('ru');
        [, $subscription] = paidSubscription(endsAt: '2026-10-21 12:00');

        $html = (new RenewalReminderMail($subscription))->render();

        expect($html)->toContain('99 ₽')->toContain('21 октября 2026');
    });
});

describe('refund amount under the offer', function () {
    it('refunds the whole payment within 7 days', function () {
        [, , $payment] = paidSubscription(endsAt: '2026-11-15 12:00');
        $payment->update(['paid_at' => now()->subDays(6)]);

        expect(app(RefundPayment::class)->refundableAmount($payment))->toBe(9900);
    });

    it('refunds unused full days after 7 days', function () {
        [, , $payment] = paidSubscription(endsAt: '2026-11-04 12:00');
        $payment->update(['paid_at' => '2026-10-04 12:00']);

        $amount = app(RefundPayment::class)->refundableAmount($payment);

        expect($amount)->toBe(intdiv(9900 * 16, 31));
    });

    it('refunds nothing for an ended period or an older payment', function () {
        [$user, $subscription, $old] = paidSubscription(endsAt: '2026-11-04 12:00');
        $old->update(['paid_at' => '2026-09-04 12:00']);
        Payment::factory()->for($user)->create(['subscription_id' => $subscription->id, 'amount' => 9900, 'paid_at' => '2026-10-04 12:00']);

        expect(app(RefundPayment::class)->refundableAmount($old))->toBe(0);

        [, $ended, $payment] = paidSubscription(endsAt: '2026-10-10 12:00');
        $payment->update(['paid_at' => '2026-09-10 12:00']);

        expect(app(RefundPayment::class)->refundableAmount($payment))->toBe(0);
    });
});

describe('refunding a payment', function () {
    it('refunds through the gateway, marks the payment and ends Premium', function () {
        [$user, $subscription, $payment] = paidSubscription(endsAt: '2026-11-15 12:00');
        $payment->update(['paid_at' => now()->subDay()]);

        $this->artisan('payments:refund', ['payment' => $payment->id, '--force' => true])->assertSuccessful();

        $payment->refresh();

        expect($payment->status)->toBe(PaymentStatus::Refunded)
            ->and($payment->refunded_amount)->toBe(9900)
            ->and($payment->refund_request_id)->toStartWith('fake_')
            ->and($subscription->fresh()->status)->toBe(SubscriptionStatus::Cancelled)
            ->and($user->fresh()->subscription_plan)->toBe(SubscriptionPlan::Free);
    });

    it('takes a manual amount in rubles', function () {
        [, , $payment] = paidSubscription(endsAt: '2026-11-15 12:00');

        $this->artisan('payments:refund', ['payment' => $payment->id, '--amount' => '50,50', '--force' => true])->assertSuccessful();

        expect($payment->fresh()->refunded_amount)->toBe(5050);
    });

    it('keeps the payment and Premium when the gateway fails', function () {
        $this->app->instance(PaymentGatewayInterface::class, failingGateway());
        [$user, , $payment] = paidSubscription(endsAt: '2026-11-15 12:00');
        $payment->update(['paid_at' => now()->subDay()]);

        $this->artisan('payments:refund', ['payment' => $payment->id, '--force' => true])->assertFailed();

        expect($payment->fresh()->status)->toBe(PaymentStatus::Completed)
            ->and($user->fresh()->isPremium())->toBeTrue();
    });

    it('refuses an amount above the payment', function () {
        [, , $payment] = paidSubscription(endsAt: '2026-11-15 12:00');

        $this->artisan('payments:refund', ['payment' => $payment->id, '--amount' => '500', '--force' => true])->assertFailed();

        expect($payment->fresh()->refunded_amount)->toBe(0);
    });
});
