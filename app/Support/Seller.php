<?php

namespace App\Support;

class Seller
{
    public static function name(): ?string
    {
        return config('app.seller.name') ?: null;
    }

    public static function inn(): ?string
    {
        return config('app.seller.inn') ?: null;
    }

    public static function email(): string
    {
        return config('app.seller.email') ?: 'support@entropiq.ru';
    }

    public static function hasRequisites(): bool
    {
        return self::name() !== null && self::inn() !== null;
    }

    /** @return array<string, string> */
    public static function replacements(): array
    {
        return [
            'seller' => self::requisites(),
            'email' => self::email(),
            'site' => (string) config('app.url'),
        ];
    }

    public static function requisites(): string
    {
        $name = self::name();

        if ($name === null) {
            return __('legal.seller_pending');
        }

        return __('legal.seller_requisites', [
            'name' => $name,
            'inn' => self::inn() ?? __('legal.seller_inn_pending'),
        ]);
    }
}
