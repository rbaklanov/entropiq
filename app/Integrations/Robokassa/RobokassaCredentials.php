<?php

namespace App\Integrations\Robokassa;

readonly class RobokassaCredentials
{
    public function __construct(
        public string $merchantLogin,
        public string $password1,
        public string $password2,
        public string $password3,
        public string $testPassword1,
        public string $testPassword2,
        public bool $testMode,
        public string $hashAlgorithm = 'md5',
        public bool $sendReceipt = false,
    ) {}

    public function paymentPassword(): string
    {
        return $this->testMode ? $this->testPassword1 : $this->password1;
    }

    public function resultPassword(): string
    {
        return $this->testMode ? $this->testPassword2 : $this->password2;
    }
}
