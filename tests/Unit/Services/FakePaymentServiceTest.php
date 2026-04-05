<?php

use App\Contracts\PaymentServiceInterface;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(PaymentServiceInterface::class);
});

describe('charge', function () {
    it('creates completed payment for monthly plan', function () {
        $user = User::factory()->create();

        $payment = $this->service->charge($user, SubscriptionPlan::Monthly);

        expect($payment->status)->toBe(PaymentStatus::Completed);
        expect($payment->amount)->toBe(9900);
        expect($payment->provider)->toBe('fake');
        expect($payment->currency_code)->toBe('RUB');
        expect($payment->paid_at)->not->toBeNull();
        expect($payment->provider_payment_id)->toStartWith('fake_');
    });

    it('creates completed payment for yearly plan', function () {
        $user = User::factory()->create();

        $payment = $this->service->charge($user, SubscriptionPlan::Yearly);

        expect($payment->amount)->toBe(59000);
    });

    it('links payment to active subscription', function () {
        $user = User::factory()->create();

        $subscriptionService = app(\App\Contracts\SubscriptionServiceInterface::class);
        $subscription = $subscriptionService->subscribe($user, SubscriptionPlan::Monthly);

        $payment = $this->service->charge($user, SubscriptionPlan::Monthly);

        expect($payment->subscription_id)->toBe($subscription->id);
    });
});

describe('refund', function () {
    it('changes payment status to refunded', function () {
        $user = User::factory()->create();

        $payment = $this->service->charge($user, SubscriptionPlan::Monthly);
        expect($payment->status)->toBe(PaymentStatus::Completed);

        $refunded = $this->service->refund($payment);

        expect($refunded->status)->toBe(PaymentStatus::Refunded);
        expect($refunded->id)->toBe($payment->id);
    });
});
