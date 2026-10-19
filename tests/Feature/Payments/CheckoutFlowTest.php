<?php

use App\Enums\ConsentType;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Livewire\Settings\SubscriptionPage;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.payments.driver', 'robokassa');
    config()->set('services.robokassa', [
        'merchant_login' => 'entropiq',
        'password1' => 'live-pass-1',
        'password2' => 'live-pass-2',
        'password3' => 'refund-pass-3',
        'test_password1' => 'test-pass-1',
        'test_password2' => 'test-pass-2',
        'test_mode' => false,
        'hash_algorithm' => 'md5',
        'send_receipt' => false,
    ]);
});

function resultSignature(string $outSum, int $invoiceId, string $password = 'live-pass-2'): string
{
    return strtoupper(md5("{$outSum}:{$invoiceId}:{$password}"));
}

function pendingPayment(array $attributes = []): Payment
{
    return Payment::factory()->for(User::factory()->withVerifiedEmail()->create(['phone_verified_at' => now()]))->pending()->create([
        'amount' => 9900,
        'plan' => SubscriptionPlan::Monthly,
        'provider' => 'robokassa',
        'provider_payment_id' => null,
        ...$attributes,
    ]);
}

describe('starting a payment', function () {
    it('creates a pending payment and sends the user to Robokassa', function () {
        $user = User::factory()->create(['phone_verified_at' => now()]);

        $component = Livewire::actingAs($user)
            ->test(SubscriptionPage::class)
            ->call('selectPlan', 'yearly')
            ->call('subscribe');

        $payment = Payment::sole();

        expect($payment->status)->toBe(PaymentStatus::Pending)
            ->and($payment->plan)->toBe(SubscriptionPlan::Yearly)
            ->and($payment->amount)->toBe(59000)
            ->and($payment->is_recurring)->toBeFalse()
            ->and($user->fresh()->subscription_plan)->toBe(SubscriptionPlan::Free);

        $component->assertRedirectContains('https://auth.robokassa.ru/Merchant/Index.aspx?');
        $component->assertRedirectContains("InvId={$payment->id}");
    });

    it('records the separate consent to recurring charges when auto renewal is chosen', function () {
        $user = User::factory()->create(['phone_verified_at' => now()]);

        Livewire::actingAs($user)
            ->test(SubscriptionPage::class)
            ->set('autoRenew', true)
            ->call('subscribe');

        expect(Payment::sole()->is_recurring)->toBeTrue()
            ->and($user->consents()->where('type', ConsentType::RecurringPayments)->exists())->toBeTrue();
    });

    it('shows the unticked auto renewal checkbox and a link to the offer', function () {
        Livewire::actingAs(User::factory()->create(['phone_verified_at' => now()]))
            ->test(SubscriptionPage::class)
            ->assertSet('autoRenew', false)
            ->assertSeeHtml('wire:model="autoRenew"')
            ->assertSeeHtml(route('offer'));
    });
});

describe('Robokassa ResultURL', function () {
    it('confirms a paid invoice, grants Premium and answers OK with the invoice number', function () {
        $payment = pendingPayment(['is_recurring' => true]);

        $this->post('/payments/robokassa/result', [
            'OutSum' => '99.000000',
            'InvId' => $payment->id,
            'SignatureValue' => resultSignature('99.000000', $payment->id),
        ])->assertOk()->assertSeeText("OK{$payment->id}");

        $payment->refresh();
        $subscription = $payment->subscription;

        expect($payment->status)->toBe(PaymentStatus::Completed)
            ->and($payment->paid_at)->not->toBeNull()
            ->and($subscription->status)->toBe(SubscriptionStatus::Active)
            ->and($subscription->auto_renew)->toBeTrue()
            ->and($payment->user->fresh()->subscription_plan)->toBe(SubscriptionPlan::Monthly);
    });

    it('accepts a GET notification and a lower case signature', function () {
        $payment = pendingPayment();

        $this->get('/payments/robokassa/result?'.http_build_query([
            'OutSum' => '99.00',
            'InvId' => $payment->id,
            'SignatureValue' => strtolower(resultSignature('99.00', $payment->id)),
        ]))->assertOk()->assertSeeText("OK{$payment->id}");

        expect($payment->fresh()->status)->toBe(PaymentStatus::Completed);
    });

    it('handles a repeated notification once', function () {
        $payment = pendingPayment();
        $data = ['OutSum' => '99.00', 'InvId' => $payment->id, 'SignatureValue' => resultSignature('99.00', $payment->id)];

        $this->post('/payments/robokassa/result', $data)->assertOk();
        $this->post('/payments/robokassa/result', $data)->assertOk()->assertSeeText("OK{$payment->id}");

        expect(Subscription::count())->toBe(1);
    });

    it('accepts the test password only in test mode', function () {
        $payment = pendingPayment();
        $data = ['OutSum' => '99.00', 'InvId' => $payment->id, 'SignatureValue' => resultSignature('99.00', $payment->id, 'test-pass-2')];

        $this->post('/payments/robokassa/result', $data)->assertStatus(400);

        config()->set('services.robokassa.test_mode', true);

        $this->post('/payments/robokassa/result', $data)->assertOk();
    });

    it('rejects a wrong signature without touching the payment', function () {
        $payment = pendingPayment();

        $this->post('/payments/robokassa/result', [
            'OutSum' => '99.00',
            'InvId' => $payment->id,
            'SignatureValue' => resultSignature('99.00', $payment->id, 'guess'),
        ])->assertStatus(400)->assertSeeText('bad sign');

        expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
    });

    it('rejects an amount that differs from the payment', function () {
        $payment = pendingPayment();

        $this->post('/payments/robokassa/result', [
            'OutSum' => '1.00',
            'InvId' => $payment->id,
            'SignatureValue' => resultSignature('1.00', $payment->id),
        ])->assertStatus(400)->assertSeeText('bad amount');

        expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
    });

    it('answers 404 for an unknown invoice and 400 for missing fields', function () {
        $this->post('/payments/robokassa/result', [
            'OutSum' => '99.00',
            'InvId' => 999,
            'SignatureValue' => resultSignature('99.00', 999),
        ])->assertNotFound();

        $this->post('/payments/robokassa/result', ['OutSum' => '99.00'])->assertStatus(400);
    });

    it('extends the subscription when a renewal charge is confirmed', function () {
        $this->travelTo(now()->setDateTime(2026, 10, 19, 12, 0));

        $user = User::factory()->create(['subscription_plan' => SubscriptionPlan::Monthly]);
        $subscription = Subscription::factory()->for($user)->create(['auto_renew' => true, 'ends_at' => now(), 'renewal_reminder_sent_at' => now()->subDays(3)]);
        $parent = Payment::factory()->for($user)->create(['is_recurring' => true, 'subscription_id' => $subscription->id, 'provider' => 'robokassa']);
        $child = Payment::factory()->for($user)->pending()->create([
            'amount' => 9900,
            'plan' => SubscriptionPlan::Monthly,
            'provider' => 'robokassa',
            'parent_payment_id' => $parent->id,
            'subscription_id' => $subscription->id,
        ]);

        $this->post('/payments/robokassa/result', [
            'OutSum' => '99.00',
            'InvId' => $child->id,
            'SignatureValue' => resultSignature('99.00', $child->id),
        ])->assertOk();

        $subscription->refresh();

        expect(Subscription::count())->toBe(1)
            ->and($subscription->ends_at->toDateString())->toBe('2026-11-19')
            ->and($subscription->renewal_reminder_sent_at)->toBeNull()
            ->and($child->fresh()->status)->toBe(PaymentStatus::Completed);
    });
});

describe('return pages', function () {
    it('says the payment is processing until the notification arrives', function () {
        $payment = pendingPayment();

        $this->actingAs($payment->user)
            ->get("/payments/success?InvId={$payment->id}&OutSum=99.00")
            ->assertRedirect(route('settings.subscription'))
            ->assertSessionHas('success', __('payments.processing'));

        expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
    });

    it('reports a failed payment', function () {
        $this->actingAs(User::factory()->create())
            ->post('/payments/fail', ['InvId' => 1])
            ->assertRedirect(route('settings.subscription'))
            ->assertSessionHas('error', __('payments.failed'));
    });
});

describe('fake gateway', function () {
    it('completes the payment on the signed local checkout page', function () {
        config()->set('services.payments.driver', 'fake');
        $user = User::factory()->create(['phone_verified_at' => now()]);

        $component = Livewire::actingAs($user)->test(SubscriptionPage::class)->call('subscribe');
        $payment = Payment::sole();

        expect($payment->provider)->toBe('fake');

        $redirect = $component->effects['redirect'];

        $this->actingAs($user)->get($redirect)->assertRedirect(route('payments.success', ['InvId' => $payment->id]));

        expect($payment->fresh()->status)->toBe(PaymentStatus::Completed)
            ->and($user->fresh()->isPremium())->toBeTrue();
    });

    it('rejects an unsigned checkout link', function () {
        config()->set('services.payments.driver', 'fake');
        $payment = pendingPayment(['provider' => 'fake']);

        $this->actingAs($payment->user)->get("/payments/fake/{$payment->id}")->assertForbidden();
    });
});

describe('managing auto renewal', function () {
    it('turns off auto renewal and keeps Premium until the end of the period', function () {
        $user = User::factory()->create(['subscription_plan' => SubscriptionPlan::Monthly, 'phone_verified_at' => now()]);
        $subscription = Subscription::factory()->for($user)->create(['auto_renew' => true, 'ends_at' => now()->addDays(20)]);

        Livewire::actingAs($user)
            ->test(SubscriptionPage::class)
            ->assertSee(__('payments.disable_auto_renew'))
            ->call('disableAutoRenew');

        expect($subscription->fresh()->auto_renew)->toBeFalse()
            ->and($subscription->fresh()->status)->toBe(SubscriptionStatus::Active)
            ->and($user->fresh()->isPremium())->toBeTrue();
    });

    it('warns about an automatic charge within three days', function () {
        app()->setLocale('ru');
        $user = User::factory()->create(['subscription_plan' => SubscriptionPlan::Monthly, 'phone_verified_at' => now()]);
        Subscription::factory()->for($user)->create(['auto_renew' => true, 'ends_at' => now()->addDays(2)]);

        Livewire::actingAs($user)
            ->test(SubscriptionPage::class)
            ->assertSee('Через несколько дней подписка продлится автоматически')
            ->assertSee('99 ₽');
    });

    it('does not warn when auto renewal is off', function () {
        app()->setLocale('ru');
        $user = User::factory()->create(['subscription_plan' => SubscriptionPlan::Monthly, 'phone_verified_at' => now()]);
        Subscription::factory()->for($user)->create(['auto_renew' => false, 'ends_at' => now()->addDays(2)]);

        Livewire::actingAs($user)
            ->test(SubscriptionPage::class)
            ->assertDontSee('Через несколько дней подписка продлится автоматически')
            ->assertSee('Автопродление выключено');
    });
});
