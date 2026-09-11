<?php

namespace App\Services;

use App\Contracts\SmsServiceInterface;
use App\Exceptions\SmsDeliveryException;
use App\Integrations\SmsAero\Requests\SendSmsRequest;
use App\Integrations\SmsAero\SmsAeroConnector;
use Exception;
use Illuminate\Support\Facades\Log;
use JsonException;
use Saloon\Http\Response;

class SmsAeroService implements SmsServiceInterface
{
    public function __construct(
        private SmsAeroConnector $connector,
        private string $sign,
        private bool $testMode = false,
    ) {}

    public function send(string $phone, string $message): void
    {
        try {
            $response = $this->connector->send(new SendSmsRequest(
                phone: $phone,
                text: $message,
                sign: $this->sign,
                testMode: $this->testMode,
            ));
        } catch (Exception $exception) {
            $this->logFailure($phone, exception: $exception);

            throw new SmsDeliveryException;
        }

        if ($this->wasAccepted($response)) {
            return;
        }

        $this->logFailure($phone, $response);

        throw new SmsDeliveryException;
    }

    public function sendVerificationCode(string $phone, string $code): void
    {
        $this->send($phone, __('auth.verification_sms', ['code' => $code]));
    }

    private function wasAccepted(Response $response): bool
    {
        if (! $response->successful()) {
            return false;
        }

        try {
            return $response->json('success') === true;
        } catch (JsonException) {
            return false;
        }
    }

    private function logFailure(string $phone, ?Response $response = null, ?Exception $exception = null): void
    {
        $providerMessage = null;

        if ($response instanceof Response) {
            try {
                $message = $response->json('message');
                $providerMessage = is_string($message) ? $message : null;
            } catch (JsonException) {
                $providerMessage = null;
            }
        }

        $context = [
            'phone' => $phone,
            'http_status' => $response?->status(),
            'provider_message' => $providerMessage,
        ];

        if ($exception instanceof Exception) {
            $context['exception_class'] = $exception::class;
            $context['exception_message'] = $exception->getMessage();
        }

        Log::warning('SMS Aero send failed', $context);
    }
}
