<?php

namespace App\Dto;

use Illuminate\Support\Carbon;

final readonly class CpiRecord
{
    public function __construct(
        public Carbon $period,
        public string $categoryCode,
        public float $value,
    ) {}
}
