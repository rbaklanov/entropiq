<?php

namespace App\Contracts;

interface VerificationCodeSenderInterface
{
    public function sendVerificationCode(string $phone, string $code): void;
}
