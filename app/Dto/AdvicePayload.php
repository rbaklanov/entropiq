<?php

namespace App\Dto;

class AdvicePayload
{
    public const MONEY_KEYS = [
        'current_total',
        'avg_monthly',
        'income',
        'expense',
        'overspend',
        'amount',
        'discretionary_total',
        'total_expense',
        'potential_saving',
        'monthly_saving',
    ];

    /**
     * @param  array<string, mixed>  $basisData  money values are stored in kopecks
     */
    public function __construct(
        public readonly string $ruleKey,
        public readonly string $title,
        public readonly string $body,
        public readonly array $basisData = [],
    ) {}

    /** @return array<string, mixed> */
    public function basisDataInRubles(): array
    {
        $data = [];

        foreach ($this->basisData as $key => $value) {
            if (! in_array($key, self::MONEY_KEYS, true) || ! is_numeric($value)) {
                $data[$key] = $value;

                continue;
            }

            $rubles = $value / 100;
            $data[$key] = floor($rubles) == $rubles ? (int) $rubles : round($rubles, 2);
        }

        return $data;
    }
}
