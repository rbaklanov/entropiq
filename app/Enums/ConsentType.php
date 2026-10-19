<?php

namespace App\Enums;

enum ConsentType: string
{
    case PersonalData = 'personal_data';

    public function currentVersion(): string
    {
        return match ($this) {
            self::PersonalData => '2026-10-19',
        };
    }
}
