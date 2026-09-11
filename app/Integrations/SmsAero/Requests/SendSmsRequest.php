<?php

namespace App\Integrations\SmsAero\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;

class SendSmsRequest extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly string $phone,
        public readonly string $text,
        public readonly string $sign,
        public readonly bool $testMode = false,
    ) {}

    public function resolveEndpoint(): string
    {
        if ($this->testMode) {
            return '/sms/testsend';
        }

        return '/sms/send';
    }

    /** @return array<string, string> */
    protected function defaultBody(): array
    {
        return [
            'number' => $this->phone,
            'text' => $this->text,
            'sign' => $this->sign,
        ];
    }
}
