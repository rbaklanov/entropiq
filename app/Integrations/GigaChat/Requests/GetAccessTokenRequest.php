<?php

namespace App\Integrations\GigaChat\Requests;

use Illuminate\Support\Str;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;

class GetAccessTokenRequest extends Request implements HasBody
{
    use HasFormBody;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly string $scope = 'GIGACHAT_API_PERS',
        public readonly ?string $rqUid = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/oauth';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'RqUID' => $this->rqUid ?? (string) Str::uuid(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return [
            'scope' => $this->scope,
        ];
    }
}
