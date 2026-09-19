<?php

use App\Integrations\GigaChat\GigaChatConnector;
use App\Integrations\GigaChat\GigaChatOAuthConnector;
use App\Integrations\GigaChat\Requests\ChatCompletionRequest;
use App\Integrations\GigaChat\Requests\GetAccessTokenRequest;
use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\Http\Auth\TokenAuthenticator;

describe('GigaChatOAuthConnector', function () {
    it('configures base url, basic auth and ssl verify', function () {
        $connector = new GigaChatOAuthConnector(
            clientId: 'test-client-id',
            clientSecret: 'test-client-secret',
            verifySsl: false,
            timeout: 12.0,
        );

        expect($connector->resolveBaseUrl())->toBe('https://ngw.devices.sberbank.ru:9443/api/v2')
            ->and($connector->config()->all())->toBe(['verify' => false]);

        $reflection = new ReflectionMethod($connector, 'defaultAuth');
        $auth = $reflection->invoke($connector);

        expect($auth)->toBeInstanceOf(BasicAuthenticator::class);
    });

    it('builds GetAccessTokenRequest with RqUID and form body', function () {
        $request = new GetAccessTokenRequest(
            scope: 'GIGACHAT_API_PERS',
            rqUid: 'custom-uuid-1234',
        );

        expect($request->resolveEndpoint())->toBe('/oauth')
            ->and($request->headers()->get('RqUID'))->toBe('custom-uuid-1234')
            ->and($request->body()->all())->toBe(['scope' => 'GIGACHAT_API_PERS']);
    });
});

describe('GigaChatConnector', function () {
    it('configures base url, bearer auth and ssl verify', function () {
        $connector = new GigaChatConnector(
            accessToken: 'sample-access-token',
            verifySsl: false,
            timeout: 25.0,
        );

        expect($connector->resolveBaseUrl())->toBe('https://gigachat.devices.sberbank.ru/api/v1')
            ->and($connector->config()->all())->toBe(['verify' => false]);

        $reflection = new ReflectionMethod($connector, 'defaultAuth');
        $auth = $reflection->invoke($connector);

        expect($auth)->toBeInstanceOf(TokenAuthenticator::class);
    });

    it('builds ChatCompletionRequest with json body', function () {
        $messages = [
            ['role' => 'system', 'content' => 'System prompt'],
            ['role' => 'user', 'content' => 'User prompt'],
        ];

        $request = new ChatCompletionRequest(
            messages: $messages,
            model: 'GigaChat-Pro',
            temperature: 0.5,
            maxTokens: 512,
        );

        expect($request->resolveEndpoint())->toBe('/chat/completions')
            ->and($request->body()->all())->toBe([
                'model' => 'GigaChat-Pro',
                'messages' => $messages,
                'temperature' => 0.5,
                'max_tokens' => 512,
                'stream' => false,
            ]);
    });
});
