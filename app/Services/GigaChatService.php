<?php

namespace App\Services;

use App\Contracts\LlmServiceInterface;
use App\Dto\AdvicePayload;
use App\Integrations\GigaChat\GigaChatConnector;
use App\Integrations\GigaChat\GigaChatOAuthConnector;
use App\Integrations\GigaChat\Requests\ChatCompletionRequest;
use App\Integrations\GigaChat\Requests\GetAccessTokenRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GigaChatService implements LlmServiceInterface
{
    private const CACHE_PREFIX = 'gigachat:token:';

    private const DEFAULT_TOKEN_TTL_SECONDS = 1500;

    private const EXPIRY_SAFETY_MARGIN_SECONDS = 120;

    public function __construct(
        private readonly FakeLlmService $fallbackService,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $scope = 'GIGACHAT_API_PERS',
        private readonly string $model = 'GigaChat',
        private readonly bool|string $verifySsl = false,
        private readonly float $timeout = 30.0,
        private ?GigaChatOAuthConnector $oauthConnector = null,
        private ?GigaChatConnector $gigachatConnector = null,
    ) {}

    /**
     * @return array{title: string, body: string}
     */
    public function generateAdviceText(AdvicePayload $payload): array
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            Log::debug('GigaChat credentials are not set, using FakeLlmService fallback');

            return $this->fallbackService->generateAdviceText($payload);
        }

        try {
            $accessToken = $this->getAccessToken();

            if (! $accessToken) {
                Log::warning('Unable to obtain GigaChat access token, falling back to FakeLlmService');

                return $this->fallbackService->generateAdviceText($payload);
            }

            $connector = $this->gigachatConnector ?? new GigaChatConnector(
                accessToken: $accessToken,
                verifySsl: $this->verifySsl,
                timeout: $this->timeout,
            );

            $messages = $this->buildMessages($payload);
            $request = new ChatCompletionRequest(
                messages: $messages,
                model: $this->model,
                temperature: 0.7,
                maxTokens: 1024,
            );

            $response = $connector->send($request);

            if (! $response->successful()) {
                Log::warning('GigaChat API error response, falling back to FakeLlmService', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->fallbackService->generateAdviceText($payload);
            }

            /** @var array{choices?: list<array{message?: array{content?: string}}>} $data */
            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            $parsed = $this->parseAdviceResponse($content);

            if ($parsed !== null) {
                return $parsed;
            }

            Log::warning('GigaChat response could not be parsed as advice JSON, falling back to FakeLlmService', [
                'raw_content' => $content,
            ]);

            return $this->fallbackService->generateAdviceText($payload);
        } catch (Throwable $e) {
            Log::warning('GigaChat advice generation failed with exception, falling back to FakeLlmService', [
                'rule' => $payload->ruleKey,
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackService->generateAdviceText($payload);
        }
    }

    private function getAccessToken(): ?string
    {
        $cacheKey = self::CACHE_PREFIX.md5($this->clientId.':'.$this->scope);

        /** @var ?string $cachedToken */
        $cachedToken = Cache::get($cacheKey);

        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $oauthConnector = $this->oauthConnector ?? new GigaChatOAuthConnector(
            clientId: $this->clientId,
            clientSecret: $this->clientSecret,
            verifySsl: $this->verifySsl,
            timeout: 15.0,
        );

        $response = $oauthConnector->send(new GetAccessTokenRequest($this->scope));

        if (! $response->successful()) {
            Log::warning('GigaChat OAuth failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        /** @var array{access_token?: string, expires_at?: int|float} $data */
        $data = $response->json();
        $token = $data['access_token'] ?? null;

        if (! is_string($token) || $token === '') {
            return null;
        }

        $ttlSeconds = self::DEFAULT_TOKEN_TTL_SECONDS;

        if (! empty($data['expires_at'])) {
            $expiresAtTimestamp = (int) ($data['expires_at'] / 1000);
            $secondsUntilExpiry = $expiresAtTimestamp - time();

            if ($secondsUntilExpiry > self::EXPIRY_SAFETY_MARGIN_SECONDS) {
                $ttlSeconds = $secondsUntilExpiry - self::EXPIRY_SAFETY_MARGIN_SECONDS;
            }
        }

        Cache::put($cacheKey, $token, $ttlSeconds);

        return $token;
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    private function buildMessages(AdvicePayload $payload): array
    {
        $systemPrompt = <<<'PROMPT'
Ты — персональный финансовый ассистент приложения Entropiq.
Твоя задача — сформулировать понятный, персонализированный и полезный финансовый совет на основе предоставленных метрик.

Правила:
1. Опирайся строго на переданные цифры и факты. Не придумывай суммы, проценты или события, которых нет во входных данных.
2. Не рекомендуй конкретные банки, инвестиционные продукты, кредиты или биржевые инструменты. Сосредоточься на контроле бюджета, привычках и оптимизации трат.
3. Тон: доброжелательный, поддерживающий, профессиональный и лаконичный.
4. Ответ ОБЯЗАТЕЛЬНО должен быть валидным JSON-объектом без постороннего текста и без markdown-разметки:
{
  "title": "Краткий емкий заголовок совета (до 60 символов)",
  "body": "Понятное объяснение ситуации с ключевыми цифрами и 1-2 конкретных действия (2-4 предложения)."
}
PROMPT;

        $contextData = json_encode($payload->basisData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $userPrompt = <<<PROMPT
Сформулируй совет для финансовой ситуации:
- Правило: {$payload->ruleKey}
- Исходный заголовок: {$payload->title}
- Исходное описание: {$payload->body}
- Данные метрик:
{$contextData}
PROMPT;

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];
    }

    /**
     * @return ?array{title: string, body: string}
     */
    private function parseAdviceResponse(string $rawContent): ?array
    {
        $clean = trim($rawContent);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $clean, $matches)) {
            $clean = trim($matches[1]);
        }

        /** @var mixed $decoded */
        $decoded = json_decode($clean, true);

        if (! is_array($decoded)) {
            if (preg_match('/\{[\s\S]*\}/', $clean, $jsonMatch)) {
                /** @var mixed $decoded */
                $decoded = json_decode($jsonMatch[0], true);
            }
        }

        if (
            is_array($decoded)
            && isset($decoded['title'], $decoded['body'])
            && is_string($decoded['title'])
            && is_string($decoded['body'])
            && trim($decoded['title']) !== ''
            && trim($decoded['body']) !== ''
        ) {
            return [
                'title' => trim($decoded['title']),
                'body' => trim($decoded['body']),
            ];
        }

        return null;
    }
}
