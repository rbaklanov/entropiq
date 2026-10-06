<?php

namespace App\Integrations\Rosstat\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetPricesPageRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/statistics/price';
    }
}
