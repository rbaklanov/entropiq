<?php

namespace App\Enums;

use App\Support\Money;

enum SubscriptionPlan: string
{
    case Free = 'free';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function price(): int
    {
        return match ($this) {
            self::Free => 0,
            self::Monthly => 9900,
            self::Yearly => 59000,
        };
    }

    /** @return array<string, string> */
    public static function priceReplacements(): array
    {
        $monthly = self::Monthly->price();
        $yearly = self::Yearly->price();

        return [
            'monthly_price' => Money::amount($monthly),
            'yearly_price' => Money::amount($yearly),
            'yearly_savings' => (string) round(100 - $yearly * 100 / ($monthly * 12)),
        ];
    }
}
