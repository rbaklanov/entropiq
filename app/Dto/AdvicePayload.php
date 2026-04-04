<?php

namespace App\Dto;

class AdvicePayload
{
    /**
     * @param  array<string, mixed>  $basisData
     */
    public function __construct(
        public readonly string $ruleKey,
        public readonly string $title,
        public readonly string $body,
        public readonly array $basisData = [],
    ) {}
}
