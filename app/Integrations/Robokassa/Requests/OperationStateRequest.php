<?php

namespace App\Integrations\Robokassa\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class OperationStateRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        public readonly string $merchantLogin,
        public readonly int $invoiceId,
        public readonly string $signature,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/WebService/Service.asmx/OpStateExt';
    }

    /** @return array<string, string> */
    protected function defaultQuery(): array
    {
        return [
            'MerchantLogin' => $this->merchantLogin,
            'InvoiceID' => (string) $this->invoiceId,
            'Signature' => $this->signature,
        ];
    }
}
