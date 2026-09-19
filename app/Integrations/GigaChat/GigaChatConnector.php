<?php

namespace App\Integrations\GigaChat;

use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\HasTimeout;

class GigaChatConnector extends Connector
{
    use AcceptsJson;
    use HasTimeout;

    protected float $connectTimeout = 10.0;

    protected float $requestTimeout = 30.0;

    public function __construct(
        public readonly string $accessToken,
        public readonly bool|string $verifySsl = false,
        ?float $timeout = null,
    ) {
        if ($timeout !== null) {
            $this->requestTimeout = $timeout;
        }
    }

    public function resolveBaseUrl(): string
    {
        return 'https://gigachat.devices.sberbank.ru/api/v1';
    }

    protected function defaultAuth(): ?Authenticator
    {
        return new TokenAuthenticator($this->accessToken);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultConfig(): array
    {
        return [
            'verify' => $this->verifySsl,
        ];
    }
}
