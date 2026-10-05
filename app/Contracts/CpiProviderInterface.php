<?php

namespace App\Contracts;

use App\Dto\CpiRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface CpiProviderInterface
{
    /**
     * Monthly CPI relative to the previous month (100.00 = no change).
     *
     * @return Collection<int, CpiRecord>
     */
    public function fetch(Carbon $from, Carbon $to): Collection;
}
