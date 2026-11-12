<?php

namespace App\Integrations\SmsAero\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetTelegramCodeStatusRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        public readonly int $id,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/telegram/status';
    }

    /** @return array<string, int> */
    protected function defaultQuery(): array
    {
        return [
            'id' => $this->id,
        ];
    }
}
