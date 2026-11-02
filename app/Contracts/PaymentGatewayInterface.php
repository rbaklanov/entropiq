<?php

namespace App\Contracts;

use App\Enums\PaymentStatus;
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
     * Asks the provider how a started charge ended: Completed, Failed or still Pending.
     * Needed for renewals, because the provider notifies only about successful charges.
     *
     * @throws PaymentGatewayException
     */
    public function paymentState(Payment $payment): PaymentStatus;

    /**
     * @return string refund request id at the provider
     *
     * @throws PaymentGatewayException
     */
    public function refund(Payment $payment, int $amount): string;
}
