<?php

namespace App\Contracts;

use Illuminate\Mail\Mailable;

interface AdminAlertServiceInterface
{
    /**
     * Emails the administrator at most once per throttle window.
     * Returns true only when a message was actually handed to the mailer.
     */
    public function send(Mailable $mail, string $throttleKey, int $throttleHours): bool;
}
