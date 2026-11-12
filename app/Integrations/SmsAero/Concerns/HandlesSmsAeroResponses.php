<?php

namespace App\Integrations\SmsAero\Concerns;

use Exception;
use Illuminate\Support\Facades\Log;
use JsonException;
use Saloon\Http\Response;

trait HandlesSmsAeroResponses
{
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

    private function logFailure(string $message, string $phone, ?Response $response = null, ?Exception $exception = null): void
    {
        $providerMessage = null;

        if ($response instanceof Response) {
            try {
                $value = $response->json('message');
                $providerMessage = is_string($value) ? $value : null;
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

        Log::warning($message, $context);
    }
}
