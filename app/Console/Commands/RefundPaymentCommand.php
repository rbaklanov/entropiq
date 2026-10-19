<?php

namespace App\Console\Commands;

use App\Actions\RefundPayment;
use App\Exceptions\PaymentGatewayException;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Console\Command;
use InvalidArgumentException;

class RefundPaymentCommand extends Command
{
    protected $signature = 'payments:refund
                            {payment : Payment ID (the Robokassa invoice number)}
                            {--amount= : Amount in rubles, by default the amount due under the offer}
                            {--force : Skip the confirmation}';

    protected $description = 'Refund a payment through the payment provider and end Premium';

    public function handle(RefundPayment $refund): int
    {
        $payment = Payment::find($this->argument('payment'));

        if ($payment === null) {
            $this->error('Payment not found.');

            return self::FAILURE;
        }

        $amountOption = $this->option('amount');
        $amount = is_string($amountOption) && $amountOption !== ''
            ? (int) round((float) str_replace(',', '.', $amountOption) * 100)
            : $refund->refundableAmount($payment);

        $this->info("Payment #{$payment->id}: paid ".Money::amount($payment->amount).' ₽ on '.$payment->paid_at?->format('d.m.Y').', refund '.Money::amount($amount).' ₽.');

        if (! $this->option('force') && ! $this->confirm('Refund this amount and end Premium for the user?')) {
            $this->comment('Cancelled.');

            return self::SUCCESS;
        }

        try {
            $payment = $refund->execute($payment, $amount);
        } catch (InvalidArgumentException|PaymentGatewayException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->comment("Refund requested, provider request id {$payment->refund_request_id}. Cancel the receipt in Moy nalog if the provider does not do it.");

        return self::SUCCESS;
    }
}
