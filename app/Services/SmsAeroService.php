<?php

namespace App\Services;

use App\Contracts\SmsServiceInterface;
use App\Exceptions\SmsDeliveryException;
use App\Integrations\SmsAero\Concerns\HandlesSmsAeroResponses;
use App\Integrations\SmsAero\Requests\SendSmsRequest;
use App\Integrations\SmsAero\SmsAeroConnector;
use Exception;

class SmsAeroService implements SmsServiceInterface
{
    use HandlesSmsAeroResponses;

    private const LOG_MESSAGE = 'SMS Aero send failed';

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
            $this->logFailure(self::LOG_MESSAGE, $phone, exception: $exception);

            throw new SmsDeliveryException;
        }

        if ($this->wasAccepted($response)) {
            return;
        }

        $this->logFailure(self::LOG_MESSAGE, $phone, $response);

        throw new SmsDeliveryException;
    }

    public function sendVerificationCode(string $phone, string $code): void
    {
        $this->send($phone, __('auth.verification_sms', ['code' => $code]));
    }
}
