<?php

namespace App\Services;

use App\Contracts\SmsServiceInterface;
use Illuminate\Support\Facades\Log;

class LogSmsService implements SmsServiceInterface
{
    public function send(string $phone, string $message): void
    {
        Log::channel('single')->info("SMS to {$phone}: {$message}");
    }
}
