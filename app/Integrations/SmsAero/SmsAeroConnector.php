<?php

namespace App\Integrations\SmsAero;

use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\HasTimeout;

class SmsAeroConnector extends Connector
{
    use AcceptsJson;
    use HasTimeout;

    protected float $connectTimeout = 10;

    protected float $requestTimeout = 15;

    public function __construct(
        public readonly string $email,
        public readonly string $apiKey,
    ) {}

    public function resolveBaseUrl(): string
    {
        return 'https://gate.smsaero.ru/v2';
    }

    protected function defaultAuth(): ?Authenticator
    {
        return new BasicAuthenticator($this->email, $this->apiKey);
    }
}
