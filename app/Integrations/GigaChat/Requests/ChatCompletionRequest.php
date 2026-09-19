<?php

namespace App\Integrations\GigaChat\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class ChatCompletionRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function __construct(
        public readonly array $messages,
        public readonly string $model = 'GigaChat',
        public readonly float $temperature = 0.7,
        public readonly int $maxTokens = 1024,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/chat/completions';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'model' => $this->model,
            'messages' => $this->messages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'stream' => false,
        ];
    }
}
