<?php

namespace App\Services;

use App\Contracts\AiAdviceServiceInterface;
use App\Models\AiAdvice;
use App\Models\User;

class AiAdviceService implements AiAdviceServiceInterface
{
    public function generate(User $user): ?AiAdvice
    {
        return null;
    }
}
