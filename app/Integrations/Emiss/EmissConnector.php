<?php

namespace App\Integrations\Emiss;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\HasTimeout;

class EmissConnector extends Connector
{
    use AcceptsJson;
    use HasTimeout;

    public ?int $tries = 3;

    public ?int $retryInterval = 2000;

    public ?bool $useExponentialBackoff = true;

    protected float $connectTimeout = 10.0;

    protected float $requestTimeout = 30.0;

    public function __construct(
        private readonly string $baseUrl = 'https://www.fedstat.ru',
        ?float $timeout = null,
    ) {
        if ($timeout !== null) {
            $this->requestTimeout = $timeout;
        }
    }

    public function resolveBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /** @return array<string, string> */
    protected function defaultHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (compatible; Entropiq/1.0; +https://entropiq.ru)',
        ];
    }
}
