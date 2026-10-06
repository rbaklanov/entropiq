<?php

namespace App\Integrations\Rosstat;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\HasTimeout;

class RosstatConnector extends Connector
{
    use HasTimeout;

    public ?int $tries = 3;

    public ?int $retryInterval = 2000;

    public ?bool $useExponentialBackoff = true;

    protected float $connectTimeout = 10.0;

    protected float $requestTimeout = 60.0;

    public function __construct(
        private readonly string $baseUrl = 'https://rosstat.gov.ru',
        ?float $timeout = null,
        private readonly ?string $caBundle = null,
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

    /** @return array<string, mixed> */
    protected function defaultConfig(): array
    {
        return $this->caBundle !== null && is_file($this->caBundle)
            ? ['verify' => $this->caBundle]
            : [];
    }
}
