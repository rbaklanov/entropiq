<?php

namespace App\Services;

use App\Contracts\SmsServiceInterface;
use Illuminate\Support\Facades\Log;

class LogSmsService implements SmsServiceInterface
{
    public function send(string $phone, string $message): void
    {
        Log::channel('single')->info('[SMS]', [
            'phone' => $phone,
            'message' => $message,
        ]);
    }

    public function sendVerificationCode(string $phone, string $code): void
    {
        $message = "Entropiq: ваш код подтверждения — {$code}";

        $this->send($phone, $message);

        Log::channel('single')->info('[SMS] Verification code for easy access', [
            'phone' => $phone,
            'code' => $code,
        ]);
    }
}
