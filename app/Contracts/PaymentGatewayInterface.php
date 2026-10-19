<?php

namespace App\Contracts;

use App\Exceptions\PaymentGatewayException;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    public function provider(): string;

    public function checkoutUrl(Payment $payment): string;

    /**
     * Starts a charge without the buyer for a renewal payment that has a parent payment.
     * The result arrives later through the payment notification.
     *
     * @throws PaymentGatewayException
     */
    public function chargeRecurring(Payment $payment): void;

    /**
     * @return string refund request id at the provider
     *
     * @throws PaymentGatewayException
     */
    public function refund(Payment $payment, int $amount): string;
}
