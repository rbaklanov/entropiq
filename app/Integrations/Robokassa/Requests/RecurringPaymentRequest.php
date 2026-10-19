<?php

namespace App\Integrations\Robokassa\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;

class RecurringPaymentRequest extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly string $merchantLogin,
        public readonly int $invoiceId,
        public readonly int $previousInvoiceId,
        public readonly string $outSum,
        public readonly string $description,
        public readonly string $signature,
        public readonly ?string $receipt = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/Recurring';
    }

    /** @return array<string, string> */
    protected function defaultBody(): array
    {
        return array_filter([
            'MerchantLogin' => $this->merchantLogin,
            'InvoiceID' => (string) $this->invoiceId,
            'PreviousInvoiceID' => (string) $this->previousInvoiceId,
            'OutSum' => $this->outSum,
            'Description' => $this->description,
            'SignatureValue' => $this->signature,
            'Receipt' => $this->receipt,
        ], fn (?string $value) => $value !== null);
    }
}
