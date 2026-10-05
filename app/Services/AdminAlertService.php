<?php

namespace App\Services;

use App\Contracts\AdminAlertServiceInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AdminAlertService implements AdminAlertServiceInterface
{
    public function send(Mailable $mail, string $throttleKey, int $throttleHours): bool
    {
        $recipient = config('services.admin.email');

        if (! $recipient) {
            Log::warning('ADMIN_EMAIL is not set, admin alert was not sent', ['alert' => $throttleKey]);

            return false;
        }

        $cacheKey = "admin-alert:{$throttleKey}";

        if (! Cache::add($cacheKey, true, now()->addHours($throttleHours))) {
            return false;
        }

        try {
            Mail::to($recipient)->send($mail);
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);
            Log::error('Unable to send admin alert', ['alert' => $throttleKey, 'message' => $exception->getMessage()]);

            return false;
        }

        return true;
    }
}
