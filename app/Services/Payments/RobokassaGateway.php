<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Exceptions\PaymentGatewayException;
use App\Integrations\Robokassa\Requests\CreateRefundRequest;
use App\Integrations\Robokassa\Requests\OperationStateRequest;
use App\Integrations\Robokassa\Requests\RecurringPaymentRequest;
use App\Integrations\Robokassa\RobokassaConnector;
use App\Integrations\Robokassa\RobokassaCredentials;
use App\Integrations\Robokassa\RobokassaRefundConnector;
use App\Integrations\Robokassa\RobokassaSignature;
use App\Models\Payment;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use SimpleXMLElement;

class RobokassaGateway implements PaymentGatewayInterface
{
    public const PAYMENT_URL = 'https://auth.robokassa.ru/Merchant/Index.aspx';

    private readonly RobokassaSignature $signature;

    public function __construct(
        private readonly RobokassaCredentials $credentials,
        private readonly RobokassaConnector $connector,
        private readonly RobokassaRefundConnector $refundConnector,
    ) {
        $this->signature = new RobokassaSignature($credentials->hashAlgorithm);
    }

    public function provider(): string
    {
        return 'robokassa';
    }

    public function checkoutUrl(Payment $payment): string
    {
        $outSum = RobokassaSignature::amount($payment->amount);
        $receipt = $this->receipt($payment);

        $query = array_filter([
            'MerchantLogin' => $this->credentials->merchantLogin,
            'OutSum' => $outSum,
            'InvId' => (string) $payment->id,
            'Description' => $this->description($payment),
            'SignatureValue' => $this->paymentSignature($outSum, $payment->id, $receipt, $this->credentials->paymentPassword()),
            'Culture' => app()->getLocale() === 'en' ? 'en' : 'ru',
            'Email' => $payment->user->hasVerifiedEmail() ? $payment->user->email : null,
            'Recurring' => $payment->is_recurring ? 'true' : null,
            'Receipt' => $receipt,
            'IsTest' => $this->credentials->testMode ? '1' : null,
        ], fn (?string $value) => $value !== null);

        return self::PAYMENT_URL.'?'.http_build_query($query);
    }

    /**
     * Robokassa runs recurring charges in live mode only, so the live password #1 signs them.
     */
    public function chargeRecurring(Payment $payment): void
    {
        $parent = $payment->parent ?? throw new PaymentGatewayException("Payment #{$payment->id} has no parent payment.");

        $outSum = RobokassaSignature::amount($payment->amount);
        $receipt = $this->receipt($payment);

        $request = new RecurringPaymentRequest(
            merchantLogin: $this->credentials->merchantLogin,
            invoiceId: $payment->id,
            previousInvoiceId: $parent->id,
            outSum: $outSum,
            description: $this->description($payment),
            signature: $this->paymentSignature($outSum, $payment->id, $receipt, $this->credentials->password1),
            receipt: $receipt,
        );

        $body = trim($this->send($this->connector, $request));

        if ($body !== "OK{$payment->id}") {
            throw new PaymentGatewayException("Robokassa rejected recurring payment #{$payment->id}: {$body}");
        }
    }

    public function refund(Payment $payment, int $amount): string
    {
        $payload = [
            'OpKey' => $payment->provider_payment_id ?: $this->operationKey($payment),
            'RefundSum' => round($amount / 100, 2),
        ];

        $response = json_decode($this->send($this->refundConnector, new CreateRefundRequest($this->jwt($payload))), true);

        if (! is_array($response)) {
            throw new PaymentGatewayException("Robokassa refund for payment #{$payment->id} returned an invalid response.");
        }

        if (($response['success'] ?? false) !== true || ! is_string($response['requestId'] ?? null)) {
            $message = is_string($response['message'] ?? null) ? $response['message'] : 'unknown error';

            throw new PaymentGatewayException("Robokassa refund for payment #{$payment->id} failed: {$message}");
        }

        return $response['requestId'];
    }

    /**
     * Refunds need the operation key, which only the status interface returns.
     */
    public function operationKey(Payment $payment): string
    {
        $request = new OperationStateRequest(
            merchantLogin: $this->credentials->merchantLogin,
            invoiceId: $payment->id,
            signature: $this->signature->make($this->credentials->merchantLogin, (string) $payment->id, $this->credentials->password2),
        );

        $xml = @simplexml_load_string($this->send($this->connector, $request));

        if (! $xml instanceof SimpleXMLElement) {
            throw new PaymentGatewayException("Robokassa returned invalid XML for payment #{$payment->id}.");
        }

        $resultCode = (string) $xml->Result->Code;
        $opKey = (string) $xml->Info->OpKey;

        if ($resultCode !== '0') {
            throw new PaymentGatewayException("Robokassa status request for payment #{$payment->id} failed with result code {$resultCode}.");
        }

        if ($opKey === '') {
            throw new PaymentGatewayException("Robokassa returned no operation key for payment #{$payment->id}.");
        }

        $payment->update(['provider_payment_id' => $opKey]);

        return $opKey;
    }

    private function paymentSignature(string $outSum, int $invoiceId, ?string $receipt, string $password): string
    {
        $parts = array_filter(
            [$this->credentials->merchantLogin, $outSum, (string) $invoiceId, $receipt, $password],
            fn (?string $part) => $part !== null,
        );

        return $this->signature->make(...$parts);
    }

    private function description(Payment $payment): string
    {
        $period = $payment->plan->value ?? 'monthly';

        return __('payments.description', ['period' => __("payments.period_{$period}")]);
    }

    /**
     * The receipt goes URL encoded into both the request and the signature, as the official Robokassa plugin does.
     */
    private function receipt(Payment $payment): ?string
    {
        if (! $this->credentials->sendReceipt) {
            return null;
        }

        return urlencode((string) json_encode([
            'items' => [[
                'name' => $this->description($payment),
                'quantity' => 1,
                'sum' => round($payment->amount / 100, 2),
                'payment_method' => 'full_payment',
                'payment_object' => 'service',
                'tax' => 'none',
            ]],
        ], JSON_UNESCAPED_UNICODE));
    }

    /** @param array<string, mixed> $payload */
    private function jwt(array $payload): string
    {
        $encode = fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');

        $header = $encode((string) json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $body = $encode((string) json_encode($payload));
        $signature = $encode(hash_hmac('sha256', "{$header}.{$body}", $this->credentials->password3, true));

        return "{$header}.{$body}.{$signature}";
    }

    private function send(Connector $connector, Request $request): string
    {
        try {
            return $connector->send($request)->body();
        } catch (FatalRequestException|RequestException $exception) {
            throw new PaymentGatewayException("Robokassa request failed: {$exception->getMessage()}", previous: $exception);
        }
    }
}
