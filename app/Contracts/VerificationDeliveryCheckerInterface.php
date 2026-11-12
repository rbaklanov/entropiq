<?php

namespace App\Contracts;

use App\Enums\VerificationDeliveryStatus;

interface VerificationDeliveryCheckerInterface
{
    public function deliveryStatus(string $phone): VerificationDeliveryStatus;
}
