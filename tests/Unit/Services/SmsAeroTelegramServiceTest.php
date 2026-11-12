<?php

use App\Contracts\VerificationCodeSenderInterface;
use App\Contracts\VerificationDeliveryCheckerInterface;
use App\Enums\VerificationDeliveryStatus;
use App\Exceptions\SmsDeliveryException;
use App\Integrations\SmsAero\Requests\GetTelegramCodeStatusRequest;
use App\Integrations\SmsAero\Requests\SendTelegramCodeRequest;
use App\Integrations\SmsAero\SmsAeroConnector;
use App\Services\LogSmsService;
use App\Services\SmsAeroTelegramService;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

beforeEach(function () {
    $this->connector = new SmsAeroConnector(
        email: 'dev@example.com',
        apiKey: 'test-api-key',
    );

    $this->mockTelegram = function (MockResponse $response): MockClient {
        $mockClient = new MockClient([
            SendTelegramCodeRequest::class => $response,
        ]);

        $this->connector->withMockClient($mockClient);

        return $mockClient;
    };

    $this->queuedResponse = MockResponse::make([
        'success' => true,
        'data' => [
            'id' => 1,
            'number' => '79001234567',
            'telegramCode' => '4821',
            'smsText' => null,
            'smsFrom' => null,
            'idSms' => null,
            'status' => 0,
            'extendStatus' => 'queue',
            'cost' => '1.00',
            'dateCreate' => 1732796285,
        ],
        'message' => null,
    ]);
});

describe('SmsAeroTelegramService', function () {
    it('sends the code to Telegram without an SMS fallback', function () {
        $mockClient = ($this->mockTelegram)($this->queuedResponse);

        (new SmsAeroTelegramService($this->connector))->sendVerificationCode('79001234567', '4821');

        $mockClient->assertSent(function (SendTelegramCodeRequest $request, $response) {
            $pending = $response->getPendingRequest();

            return $pending instanceof PendingRequest
                && $pending->getMethod() === Method::GET
                && $request->resolveEndpoint() === '/telegram/send'
                && $pending->query()->all() === [
                    'number' => '79001234567',
                    'code' => '4821',
                ];
        });
    });

    it('throws a generic error and keeps the code out of the log when the API rejects the request', function () {
        Event::fake([MessageLogged::class]);

        ($this->mockTelegram)(MockResponse::make([
            'success' => false,
            'data' => null,
            'message' => 'Not enough money',
        ], 402));

        expect(fn () => (new SmsAeroTelegramService($this->connector))->sendVerificationCode('79001234567', '4821'))
            ->toThrow(SmsDeliveryException::class);

        Event::assertDispatched(MessageLogged::class, function (MessageLogged $event) {
            expect($event->message)->toBe('SMS Aero Telegram code send failed')
                ->and($event->context['http_status'])->toBe(402)
                ->and($event->context['provider_message'])->toBe('Not enough money')
                ->and(json_encode($event->context))->not->toContain('4821');

            return true;
        });
    });

    it('throws a generic error when the response is not JSON', function () {
        ($this->mockTelegram)(MockResponse::make('Bad Gateway', 502));

        expect(fn () => (new SmsAeroTelegramService($this->connector))->sendVerificationCode('79001234567', '4821'))
            ->toThrow(SmsDeliveryException::class);
    });

    it('throws a generic error when the HTTP client fails', function () {
        $connector = Mockery::mock(SmsAeroConnector::class);
        $connector->shouldReceive('send')
            ->once()
            ->andThrow(new RuntimeException('cURL error 28: Connection timed out'));

        expect(fn () => (new SmsAeroTelegramService($connector))->sendVerificationCode('79001234567', '4821'))
            ->toThrow(SmsDeliveryException::class);
    });
});

function telegramStatusResponse(int $status, string $extendStatus, string $cost = '0.00'): MockResponse
{
    return MockResponse::make([
        'success' => true,
        'data' => [
            'id' => 1,
            'number' => 79001234567,
            'telegramCode' => '4821',
            'status' => $status,
            'extendStatus' => $extendStatus,
            'cost' => $cost,
        ],
        'message' => null,
    ]);
}

describe('SmsAeroTelegramService delivery status', function () {
    it('is unknown when no code was sent to Telegram', function () {
        expect((new SmsAeroTelegramService($this->connector))->deliveryStatus('79001234567'))
            ->toBe(VerificationDeliveryStatus::Unknown);
    });

    it('reads the status of the last sent code', function (int $status, string $extendStatus, VerificationDeliveryStatus $expected) {
        $mockClient = new MockClient([
            SendTelegramCodeRequest::class => $this->queuedResponse,
            GetTelegramCodeStatusRequest::class => telegramStatusResponse($status, $extendStatus),
        ]);
        $this->connector->withMockClient($mockClient);
        $service = new SmsAeroTelegramService($this->connector);

        $service->sendVerificationCode('79001234567', '4821');

        expect($service->deliveryStatus('79001234567'))->toBe($expected);

        $mockClient->assertSent(fn ($request, $response) => $request instanceof GetTelegramCodeStatusRequest
            && $response->getPendingRequest()->query()->all() === ['id' => 1]);
    })->with([
        'queued' => [0, 'queue', VerificationDeliveryStatus::Pending],
        'delivered' => [1, 'delivery', VerificationDeliveryStatus::Delivered],
        'no Telegram account' => [2, 'undelivered', VerificationDeliveryStatus::Undelivered],
        'rejected' => [6, 'rejected', VerificationDeliveryStatus::Undelivered],
    ]);

    it('reads the id back when the cache store returns it as a string', function () {
        Cache::put('sms-aero:telegram-code:79001234567', '1');
        $this->connector->withMockClient(new MockClient([
            GetTelegramCodeStatusRequest::class => telegramStatusResponse(2, 'undelivered'),
        ]));

        expect((new SmsAeroTelegramService($this->connector))->deliveryStatus('79001234567'))
            ->toBe(VerificationDeliveryStatus::Undelivered);
    });

    it('stays pending when the status request fails', function () {
        Cache::put('sms-aero:telegram-code:79001234567', 1);

        $this->connector->withMockClient(new MockClient([
            GetTelegramCodeStatusRequest::class => MockResponse::make('Bad Gateway', 502),
        ]));

        expect((new SmsAeroTelegramService($this->connector))->deliveryStatus('79001234567'))
            ->toBe(VerificationDeliveryStatus::Pending);
    });

    it('forgets the previous code when a new send fails', function () {
        Cache::put('sms-aero:telegram-code:79001234567', 1);
        ($this->mockTelegram)(MockResponse::make(['success' => false, 'data' => null, 'message' => 'error'], 400));
        $service = new SmsAeroTelegramService($this->connector);

        expect(fn () => $service->sendVerificationCode('79001234567', '4821'))->toThrow(SmsDeliveryException::class)
            ->and($service->deliveryStatus('79001234567'))->toBe(VerificationDeliveryStatus::Unknown);
    });

    it('is bound as the delivery checker', function () {
        expect(app(VerificationDeliveryCheckerInterface::class))->toBeInstanceOf(SmsAeroTelegramService::class);
    });
});

describe('verification code sender binding', function () {
    it('sends codes to Telegram when the driver is sms_aero_telegram', function () {
        config([
            'services.sms.driver' => 'sms_aero_telegram',
            'services.sms_aero.email' => 'dev@example.com',
            'services.sms_aero.api_key' => 'test-api-key',
        ]);

        expect(app(VerificationCodeSenderInterface::class))->toBeInstanceOf(SmsAeroTelegramService::class);
    });

    it('falls back to the SMS driver otherwise', function () {
        config(['services.sms.driver' => 'log']);

        expect(app(VerificationCodeSenderInterface::class))->toBeInstanceOf(LogSmsService::class);
    });
});
