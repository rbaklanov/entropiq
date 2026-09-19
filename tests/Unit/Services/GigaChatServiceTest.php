<?php

use App\Contracts\LlmServiceInterface;
use App\Dto\AdvicePayload;
use App\Integrations\GigaChat\Requests\ChatCompletionRequest;
use App\Integrations\GigaChat\Requests\GetAccessTokenRequest;
use App\Services\FakeLlmService;
use App\Services\GigaChatService;
use Illuminate\Support\Facades\Cache;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    Cache::flush();
    MockClient::destroyGlobal();

    $this->fallbackService = new FakeLlmService;

    $this->payload = new AdvicePayload(
        ruleKey: 'category_spike',
        title: 'Рост расходов в категории',
        body: 'Расходы выросли',
        basisData: [
            'category_name' => 'Кафе',
            'current_total' => 15000,
            'avg_monthly' => 10000,
            'growth_percent' => 50,
        ],
    );

    $this->mockOAuthResponse = MockResponse::make([
        'access_token' => 'sample-test-access-token',
        'expires_at' => (time() + 3600) * 1000,
    ], 200);
});

afterEach(function () {
    MockClient::destroyGlobal();
});

describe('GigaChatService', function () {
    it('generates advice text using GigaChat when API returns valid json', function () {
        $completionResponse = MockResponse::make([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'title' => 'Оптимизируйте расходы в «Кафе»',
                            'body' => 'В этом месяце вы потратили 15 000 ₽ на кафе. Попробуйте готовить дома.',
                        ]),
                    ],
                ],
            ],
        ], 200);

        $mockClient = MockClient::global([
            GetAccessTokenRequest::class => $this->mockOAuthResponse,
            ChatCompletionRequest::class => $completionResponse,
        ]);

        $service = new GigaChatService(
            fallbackService: $this->fallbackService,
            clientId: 'client-id-123',
            clientSecret: 'client-secret-456',
        );

        $result = $service->generateAdviceText($this->payload);

        expect($result['title'])->toBe('Оптимизируйте расходы в «Кафе»')
            ->and($result['body'])->toBe('В этом месяце вы потратили 15 000 ₽ на кафе. Попробуйте готовить дома.');

        $mockClient->assertSent(GetAccessTokenRequest::class);
        $mockClient->assertSent(ChatCompletionRequest::class);
    });

    it('parses json wrapped in markdown code blocks', function () {
        $markdownContent = "```json\n".json_encode([
            'title' => 'Заголовок из markdown',
            'body' => 'Текст совета из блока markdown кода.',
        ], JSON_UNESCAPED_UNICODE)."\n```";

        MockClient::global([
            GetAccessTokenRequest::class => $this->mockOAuthResponse,
            ChatCompletionRequest::class => MockResponse::make([
                'choices' => [
                    ['message' => ['content' => $markdownContent]],
                ],
            ], 200),
        ]);

        $service = new GigaChatService(
            fallbackService: $this->fallbackService,
            clientId: 'client-id-123',
            clientSecret: 'client-secret-456',
        );

        $result = $service->generateAdviceText($this->payload);

        expect($result['title'])->toBe('Заголовок из markdown')
            ->and($result['body'])->toBe('Текст совета из блока markdown кода.');
    });

    it('extracts embedded json from chat response', function () {
        $rawText = 'Вот ваш персональный совет: {"title": "Внимание на кафе", "body": "Траты выросли на 50%."} Надеемся, это поможет.';

        MockClient::global([
            GetAccessTokenRequest::class => $this->mockOAuthResponse,
            ChatCompletionRequest::class => MockResponse::make([
                'choices' => [
                    ['message' => ['content' => $rawText]],
                ],
            ], 200),
        ]);

        $service = new GigaChatService(
            fallbackService: $this->fallbackService,
            clientId: 'client-id-123',
            clientSecret: 'client-secret-456',
        );

        $result = $service->generateAdviceText($this->payload);

        expect($result['title'])->toBe('Внимание на кафе')
            ->and($result['body'])->toBe('Траты выросли на 50%.');
    });

    it('caches access token and reuses it for subsequent requests', function () {
        $mockClient = MockClient::global([
            GetAccessTokenRequest::class => $this->mockOAuthResponse,
            ChatCompletionRequest::class => MockResponse::make([
                'choices' => [
                    ['message' => ['content' => json_encode(['title' => 'T1', 'body' => 'B1'])]],
                ],
            ], 200),
        ]);

        $service = new GigaChatService(
            fallbackService: $this->fallbackService,
            clientId: 'client-id-123',
            clientSecret: 'client-secret-456',
        );

        $service->generateAdviceText($this->payload);
        $service->generateAdviceText($this->payload);

        $mockClient->assertSentCount(1, GetAccessTokenRequest::class);
        $mockClient->assertSentCount(2, ChatCompletionRequest::class);
    });

    it('falls back to FakeLlmService when credentials are missing', function () {
        $service = new GigaChatService(
            fallbackService: $this->fallbackService,
            clientId: '',
            clientSecret: '',
        );

        $result = $service->generateAdviceText($this->payload);

        expect($result['title'])->toBe('Расходы в категории «Кафе» растут')
            ->and($result['body'])->toContain('15000 ₽ в категории «Кафе»');
    });

    it('falls back to FakeLlmService when OAuth request fails', function () {
        MockClient::global([
            GetAccessTokenRequest::class => MockResponse::make(['error' => 'invalid_client'], 401),
        ]);

        $service = new GigaChatService(
            fallbackService: $this->fallbackService,
            clientId: 'client-id-123',
            clientSecret: 'bad-secret',
        );

        $result = $service->generateAdviceText($this->payload);

        expect($result['title'])->toBe('Расходы в категории «Кафе» растут');
    });

    it('falls back to FakeLlmService when chat completion fails with server error', function () {
        MockClient::global([
            GetAccessTokenRequest::class => $this->mockOAuthResponse,
            ChatCompletionRequest::class => MockResponse::make(['error' => 'server_error'], 500),
        ]);

        $service = new GigaChatService(
            fallbackService: $this->fallbackService,
            clientId: 'client-id-123',
            clientSecret: 'client-secret-456',
        );

        $result = $service->generateAdviceText($this->payload);

        expect($result['title'])->toBe('Расходы в категории «Кафе» растут');
    });

    it('falls back to FakeLlmService when response cannot be parsed as advice json', function () {
        MockClient::global([
            GetAccessTokenRequest::class => $this->mockOAuthResponse,
            ChatCompletionRequest::class => MockResponse::make([
                'choices' => [
                    ['message' => ['content' => 'Извините, произошла внутренняя ошибка нейросети.']],
                ],
            ], 200),
        ]);

        $service = new GigaChatService(
            fallbackService: $this->fallbackService,
            clientId: 'client-id-123',
            clientSecret: 'client-secret-456',
        );

        $result = $service->generateAdviceText($this->payload);

        expect($result['title'])->toBe('Расходы в категории «Кафе» растут');
    });

    it('resolves GigaChatService or FakeLlmService from container based on config', function () {
        config(['services.llm.driver' => 'gigachat']);
        $service = app(LlmServiceInterface::class);
        expect($service)->toBeInstanceOf(GigaChatService::class);

        config(['services.llm.driver' => 'fake']);
        $service = app(LlmServiceInterface::class);
        expect($service)->toBeInstanceOf(FakeLlmService::class);
    });
});
