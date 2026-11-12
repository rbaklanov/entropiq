<?php

namespace App\Integrations\SmsAero\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Sends a verification code to the Telegram account of the phone number.
 * SMS Aero has no test endpoint for it, and charges only for delivered codes.
 */
class SendTelegramCodeRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        public readonly string $phone,
        public readonly string $code,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/telegram/send';
    }

    /** @return array<string, string> */
    protected function defaultQuery(): array
    {
        return [
            'number' => $this->phone,
            'code' => $this->code,
        ];
    }
}
