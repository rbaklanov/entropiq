<?php

namespace App\Enums;

enum ConsentType: string
{
    case PersonalData = 'personal_data';
    case RecurringPayments = 'recurring_payments';

    public function currentVersion(): string
    {
        return match ($this) {
            self::PersonalData => '2026-11-12',
            self::RecurringPayments => '2026-11-02',
        };
    }
}
