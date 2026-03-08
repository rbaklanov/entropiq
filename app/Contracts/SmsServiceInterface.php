<?php

namespace App\Contracts;

interface SmsServiceInterface
{
    public function send(string $phone, string $message): void;

    public function sendVerificationCode(string $phone, string $code): void;
}
