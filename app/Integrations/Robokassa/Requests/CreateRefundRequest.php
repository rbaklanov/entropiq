<?php

namespace App\Integrations\Robokassa\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasStringBody;

class CreateRefundRequest extends Request implements HasBody
{
    use HasStringBody;

    protected Method $method = Method::POST;

    public function __construct(public readonly string $jwt) {}

    public function resolveEndpoint(): string
    {
        return '/Refund/Create';
    }

    /** @return array<string, string> */
    protected function defaultHeaders(): array
    {
        return ['Content-Type' => 'application/json'];
    }

    /**
     * The API takes the JWT as a JSON string literal, not as a JSON object.
     */
    protected function defaultBody(): string
    {
        return (string) json_encode($this->jwt);
    }
}
