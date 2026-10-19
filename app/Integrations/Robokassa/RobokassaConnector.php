<?php

namespace App\Integrations\Robokassa;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\HasTimeout;

class RobokassaConnector extends Connector
{
    use HasTimeout;

    protected float $connectTimeout = 10;

    protected float $requestTimeout = 30;

    public function resolveBaseUrl(): string
    {
        return 'https://auth.robokassa.ru/Merchant';
    }
}
