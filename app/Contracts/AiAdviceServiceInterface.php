<?php

namespace App\Contracts;

use App\Models\AiAdvice;
use App\Models\User;
use Illuminate\Support\Collection;

interface AiAdviceServiceInterface
{
    /**
     * @return Collection<int, AiAdvice>
     */
    public function generateForUser(User $user): Collection;
}
