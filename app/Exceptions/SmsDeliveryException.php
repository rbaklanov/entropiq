<?php

namespace App\Exceptions;

use RuntimeException;

class SmsDeliveryException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('SMS delivery failed.');
    }
}
