<?php

namespace App\Support;

class Money
{
    public static function amount(int|float $kopecks): string
    {
        $kopecks = (int) round($kopecks);

        return number_format($kopecks / 100, $kopecks % 100 === 0 ? 0 : 2, ',', ' ');
    }
}
