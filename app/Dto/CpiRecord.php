<?php

namespace App\Dto;

use App\Models\CpiValue;
use Illuminate\Support\Carbon;

final readonly class CpiRecord
{
    public function __construct(
        public Carbon $period,
        public string $categoryCode,
        public float $value,
        public string $source = CpiValue::SOURCE_EMISS,
    ) {}
}
