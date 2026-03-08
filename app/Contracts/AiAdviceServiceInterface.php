<?php

namespace App\Contracts;

use App\Models\AiAdvice;
use App\Models\User;

interface AiAdviceServiceInterface
{
    public function generate(User $user): ?AiAdvice;
}
