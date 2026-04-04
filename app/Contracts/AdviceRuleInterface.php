<?php

namespace App\Contracts;

use App\Dto\AdvicePayload;
use App\Models\User;

interface AdviceRuleInterface
{
    public function evaluate(User $user): ?AdvicePayload;
}
