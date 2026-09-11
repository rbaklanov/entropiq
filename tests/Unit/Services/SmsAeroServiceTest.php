<?php

use App\Contracts\SmsServiceInterface;
use App\Exceptions\SmsDeliveryException;
use App\Integrations\SmsAero\Requests\SendSmsRequest;
use App\Integrations\SmsAero\SmsAeroConnector;
use App\Services\LogSmsService;
use App\Services\SmsAeroService;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

beforeEach(function () {
    $this->connector = new SmsAeroConnector(
        email: 'dev@example.com',
        apiKey: 'test-api-key',
    );

    $this->mockSmsAero = function (MockResponse $response): MockClient {
        $mockClient = new MockClient([
            SendSmsRequest::class => $response,
        ]);

        $this->connector->withMockClient($mockClient);

        return $mockClient;
    };

    $this->successfulSmsAeroResponse = MockResponse::make([
        'success' => true,
        'data' => [
            'id' => 1,
            'from' => 'Entropiq',
            'number' => '79001234567',
            'text' => 'hidden',
            'status' => 0,
        ],
        'message' => null,
    ]);
});

describe('SmsAeroService send', function () {
    it('sends an SMS through SMS Aero', function () {
        $mockClient = ($this->mockSmsAero)($this->successfulSmsAeroResponse);
        $service = new SmsAeroService($this->connector, 'Entropiq', true);

        $service->send('79001234567', 'Hello from Entropiq');

        $mockClient->assertSent(function (Request $request) {
            return $request instanceof SendSmsRequest
                && $request->resolveEndpoint() === '/sms/testsend'
                && $request->body()->all() === [
                    'number' => '79001234567',
                    'text' => 'Hello from Entropiq',
                    'sign' => 'Entropiq',
                ];
        });
    });

    it('uses the live endpoint when test mode is off', function () {
        $mockClient = ($this->mockSmsAero)($this->successfulSmsAeroResponse);
        $service = new SmsAeroService($this->connector, 'Entropiq', false);

        $service->send('79001234567', 'Hello from Entropiq');

        $mockClient->assertSent(function (Request $request) {
            return $request instanceof SendSmsRequest
                && $request->resolveEndpoint() === '/sms/send';
        });
    });

    it('throws a generic error when the API rejects the message', function () {
        Event::fake([MessageLogged::class]);

        ($this->mockSmsAero)(MockResponse::make([
            'success' => false,
            'data' => null,
            'message' => 'not enough money',
        ]));

        $service = new SmsAeroService($this->connector, 'Entropiq', true);

        expect(fn () => $service->sendVerificationCode('79001234567', '4321'))
            ->toThrow(SmsDeliveryException::class, 'SMS delivery failed.');

        Event::assertDispatched(MessageLogged::class, function (MessageLogged $event) {
            expect($event->message)->toBe('SMS Aero send failed')
                ->and($event->context['phone'])->toBe('79001234567')
                ->and($event->context['provider_message'])->toBe('not enough money')
                ->and($event->context)->not->toHaveKey('exception_class')
                ->and(json_encode($event->context))->not->toContain('4321');

            return true;
        });
    });

    it('throws a generic error when the HTTP response is not JSON', function () {
        ($this->mockSmsAero)(MockResponse::make('Internal Server Error', 500));
        $service = new SmsAeroService($this->connector, 'Entropiq', true);

        expect(fn () => $service->send('79001234567', 'Hello'))
            ->toThrow(SmsDeliveryException::class);
    });

    it('throws a generic error when the HTTP client fails', function () {
        Event::fake([MessageLogged::class]);

        $connector = Mockery::mock(SmsAeroConnector::class);
        $connector->shouldReceive('send')
            ->once()
            ->andThrow(new RuntimeException('cURL error 28: Connection timed out'));

        $service = new SmsAeroService($connector, 'Entropiq', true);

        expect(fn () => $service->sendVerificationCode('79001234567', '4321'))
            ->toThrow(SmsDeliveryException::class);

        Event::assertDispatched(MessageLogged::class, function (MessageLogged $event) {
            expect($event->context['exception_class'])->toBe(RuntimeException::class)
                ->and($event->context['exception_message'])->toBe('cURL error 28: Connection timed out')
                ->and(json_encode($event->context))->not->toContain('4321');

            return true;
        });
    });
});

describe('SmsAeroService sendVerificationCode', function () {
    it('sends a localized verification SMS', function () {
        $mockClient = ($this->mockSmsAero)($this->successfulSmsAeroResponse);
        $service = new SmsAeroService($this->connector, 'Entropiq', true);

        $service->sendVerificationCode('79001234567', '1234');

        $mockClient->assertSent(function (Request $request) {
            return $request instanceof SendSmsRequest
                && $request->body()->get('text') === __('auth.verification_sms', ['code' => '1234']);
        });
    });
});

describe('SMS driver binding', function () {
    it('resolves LogSmsService when the driver is log', function () {
        config(['services.sms.driver' => 'log']);

        expect(app(SmsServiceInterface::class))->toBeInstanceOf(LogSmsService::class);
    });

    it('resolves SmsAeroService when the driver is sms_aero', function () {
        config([
            'services.sms.driver' => 'sms_aero',
            'services.sms_aero.email' => 'dev@example.com',
            'services.sms_aero.api_key' => 'test-api-key',
            'services.sms_aero.sign' => 'Entropiq',
            'services.sms_aero.test_mode' => true,
        ]);

        expect(app(SmsServiceInterface::class))->toBeInstanceOf(SmsAeroService::class);
    });
});
