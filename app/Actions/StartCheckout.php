<?php

namespace App\Actions;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\ConsentType;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Payment;
use App\Models\User;
use InvalidArgumentException;

class StartCheckout
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly RecordConsent $recordConsent,
    ) {}

    /**
     * Creates a pending payment and returns the URL of the payment page.
     */
    public function execute(User $user, SubscriptionPlan $plan, bool $autoRenew, ?string $ipAddress, ?string $userAgent): string
    {
        if ($plan === SubscriptionPlan::Free) {
            throw new InvalidArgumentException('The free plan has nothing to pay for.');
        }

        if ($autoRenew) {
            $this->recordConsent->execute($user, ConsentType::RecurringPayments, $ipAddress, $userAgent);
        }

        $payment = Payment::create([
            'user_id' => $user->id,
            'plan' => $plan,
            'is_recurring' => $autoRenew,
            'amount' => $plan->price(),
            'currency_code' => 'RUB',
            'provider' => $this->gateway->provider(),
            'status' => PaymentStatus::Pending,
        ]);

        return $this->gateway->checkoutUrl($payment);
    }
}
