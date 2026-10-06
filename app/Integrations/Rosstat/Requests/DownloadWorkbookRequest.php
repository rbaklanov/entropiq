<?php

namespace App\Integrations\Rosstat\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DownloadWorkbookRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(public readonly string $path) {}

    public function resolveEndpoint(): string
    {
        return $this->path;
    }
}
