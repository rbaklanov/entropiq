<?php

namespace App\Enums;

enum VerificationDeliveryStatus: string
{
    case Pending = 'pending';
    case Delivered = 'delivered';
    case Undelivered = 'undelivered';
    case Unknown = 'unknown';

    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }
}
