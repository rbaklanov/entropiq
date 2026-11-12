<?php

namespace App\Contracts;

interface SmsServiceInterface extends VerificationCodeSenderInterface
{
    public function send(string $phone, string $message): void;
}
