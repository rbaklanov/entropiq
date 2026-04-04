<?php

namespace App\Contracts;

use App\Dto\AdvicePayload;

interface LlmServiceInterface
{
    /**
     * @return array{title: string, body: string}
     */
    public function generateAdviceText(AdvicePayload $payload): array;
}
