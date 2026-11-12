<?php

namespace App\Enums;

enum VerificationChannel: string
{
    case Sms = 'sms';
    case Telegram = 'telegram';

    public const TELEGRAM_DRIVER = 'sms_aero_telegram';

    public static function current(): self
    {
        return config('services.sms.driver') === self::TELEGRAM_DRIVER ? self::Telegram : self::Sms;
    }

    /**
     * Translates an `auth.*` key, using its `_telegram` variant when codes go to Telegram.
     *
     * @param  array<string, string>  $replace
     */
    public function trans(string $key, array $replace = []): string
    {
        $suffix = $this === self::Telegram ? '_telegram' : '';
        $line = __("auth.{$key}{$suffix}", $replace);

        return is_string($line) ? $line : $key;
    }
}
