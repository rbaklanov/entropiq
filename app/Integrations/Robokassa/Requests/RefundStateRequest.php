<?php

namespace App\Integrations\Robokassa\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class RefundStateRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(public readonly string $requestId) {}

    public function resolveEndpoint(): string
    {
        return '/Refund/GetState';
    }

    /** @return array<string, string> */
    protected function defaultQuery(): array
    {
        return ['id' => $this->requestId];
    }
}
