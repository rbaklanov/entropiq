<?php

namespace App\Services;

use App\Contracts\VerificationCodeSenderInterface;
use App\Contracts\VerificationDeliveryCheckerInterface;
use App\Enums\VerificationDeliveryStatus;
use App\Exceptions\SmsDeliveryException;
use App\Integrations\SmsAero\Concerns\HandlesSmsAeroResponses;
use App\Integrations\SmsAero\Requests\GetTelegramCodeStatusRequest;
use App\Integrations\SmsAero\Requests\SendTelegramCodeRequest;
use App\Integrations\SmsAero\SmsAeroConnector;
use App\Models\VerificationCode;
use Exception;
use Illuminate\Support\Facades\Cache;
use JsonException;

/**
 * Delivers sign-in codes to Telegram through SMS Aero. The message text is fixed by Telegram.
 * Acceptance only means the code was queued: a phone without Telegram turns `undelivered`
 * about ten seconds later, and such codes are not charged.
 */
class SmsAeroTelegramService implements VerificationCodeSenderInterface, VerificationDeliveryCheckerInterface
{
    use HandlesSmsAeroResponses;

    private const LOG_MESSAGE = 'SMS Aero Telegram code send failed';

    private const STATUS_DELIVERED = 1;

    private const STATUS_UNDELIVERED = 2;

    private const STATUS_REJECTED = 6;

    public function __construct(
        private SmsAeroConnector $connector,
    ) {}

    public function sendVerificationCode(string $phone, string $code): void
    {
        Cache::forget($this->cacheKey($phone));

        try {
            $response = $this->connector->send(new SendTelegramCodeRequest($phone, $code));
        } catch (Exception $exception) {
            $this->logFailure(self::LOG_MESSAGE, $phone, exception: $exception);

            throw new SmsDeliveryException;
        }

        if (! $this->wasAccepted($response)) {
            $this->logFailure(self::LOG_MESSAGE, $phone, $response);

            throw new SmsDeliveryException;
        }

        $id = $response->json('data.id');

        if (is_int($id)) {
            Cache::put($this->cacheKey($phone), $id, now()->addMinutes(VerificationCode::EXPIRATION_MINUTES));
        }
    }

    public function deliveryStatus(string $phone): VerificationDeliveryStatus
    {
        $id = Cache::get($this->cacheKey($phone));

        if (! is_numeric($id)) {
            return VerificationDeliveryStatus::Unknown;
        }

        try {
            $response = $this->connector->send(new GetTelegramCodeStatusRequest((int) $id));

            $status = $this->wasAccepted($response) ? $response->json('data.status') : null;
        } catch (Exception|JsonException) {
            return VerificationDeliveryStatus::Pending;
        }

        return match ($status) {
            self::STATUS_DELIVERED => VerificationDeliveryStatus::Delivered,
            self::STATUS_UNDELIVERED, self::STATUS_REJECTED => VerificationDeliveryStatus::Undelivered,
            default => VerificationDeliveryStatus::Pending,
        };
    }

    private function cacheKey(string $phone): string
    {
        return "sms-aero:telegram-code:{$phone}";
    }
}
