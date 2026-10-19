<?php

namespace App\Integrations\Robokassa;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\HasTimeout;

class RobokassaRefundConnector extends Connector
{
    use AcceptsJson;
    use HasTimeout;

    protected float $connectTimeout = 10;

    protected float $requestTimeout = 30;

    public function resolveBaseUrl(): string
    {
        return 'https://services.robokassa.ru/RefundService';
    }
}
