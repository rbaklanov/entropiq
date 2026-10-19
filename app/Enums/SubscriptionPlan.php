<?php

namespace App\Enums;

use App\Support\Money;
use Illuminate\Support\Carbon;

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

    public function periodEnd(Carbon $from): Carbon
    {
        return match ($this) {
            self::Yearly => $from->copy()->addYear(),
            default => $from->copy()->addMonth(),
        };
    }

    public function periodStart(Carbon $end): Carbon
    {
        return match ($this) {
            self::Yearly => $end->copy()->subYear(),
            default => $end->copy()->subMonth(),
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
