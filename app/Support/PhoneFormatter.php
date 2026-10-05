<?php

namespace App\Support;

class PhoneFormatter
{
    public static function mask(string $phone): string
    {
        if (! preg_match('/^7(\d{3})(\d{3})(\d{2})(\d{2})$/', $phone, $parts)) {
            return $phone;
        }

        return "+7 ({$parts[1]}) {$parts[2]}-{$parts[3]}-{$parts[4]}";
    }
}
