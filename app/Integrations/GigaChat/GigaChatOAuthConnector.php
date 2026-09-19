<?php

namespace App\Integrations\GigaChat;

use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\HasTimeout;

class GigaChatOAuthConnector extends Connector
{
    use AcceptsJson;
    use HasTimeout;

    protected float $connectTimeout = 10.0;

    protected float $requestTimeout = 15.0;

    public function __construct(
        public readonly string $clientId,
        public readonly string $clientSecret,
        public readonly bool|string $verifySsl = false,
        ?float $timeout = null,
    ) {
        if ($timeout !== null) {
            $this->requestTimeout = $timeout;
        }
    }

    public function resolveBaseUrl(): string
    {
        return 'https://ngw.devices.sberbank.ru:9443/api/v2';
    }

    protected function defaultAuth(): ?Authenticator
    {
        return new BasicAuthenticator($this->clientId, $this->clientSecret);
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
