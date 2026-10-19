<?php

namespace App\Services\Payments;

use App\Actions\CompletePayment;
use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class FakePaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly CompletePayment $completePayment) {}

    public function provider(): string
    {
        return 'fake';
    }

    public function checkoutUrl(Payment $payment): string
    {
        return URL::temporarySignedRoute('payments.fake.checkout', now()->addHour(), ['payment' => $payment->id]);
    }

    public function chargeRecurring(Payment $payment): void
    {
        $this->completePayment->execute($payment);
    }

    public function refund(Payment $payment, int $amount): string
    {
        return 'fake_'.Str::uuid()->toString();
    }
}
