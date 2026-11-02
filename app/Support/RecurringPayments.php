<?php

namespace App\Support;

/**
 * Robokassa enables recurring charges per shop on request and rejects `Recurring=true` with error 34 until then.
 * While the flag is off nobody can opt in, and older subscriptions with auto renewal simply end with their period.
 */
class RecurringPayments
{
    public static function enabled(): bool
    {
        return config('services.payments.recurring') === true;
    }
}
