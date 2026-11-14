<?php

namespace App\Support;

use Collator;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use Locale;

/**
 * Phones are stored as E.164 digits without the plus sign (79991234567, 358501234567),
 * the format used before international numbers were allowed.
 */
class PhoneNumbers
{
    public const DEFAULT_REGION = 'RU';

    private const ACCEPTED_TYPES = [
        PhoneNumberType::MOBILE,
        PhoneNumberType::FIXED_LINE_OR_MOBILE,
    ];

    /**
     * Without a region the input must be international (with or without the plus sign).
     * A national number typed with a leading plus is parsed as international too.
     */
    public static function normalize(string $input, ?string $region = null): ?string
    {
        $input = trim($input);

        if ($input === '') {
            return null;
        }

        if ($region === null || str_starts_with($input, '+')) {
            $input = '+'.ltrim($input, '+');
            $region = null;
        }

        $number = self::parse($input, $region);

        if ($number === null || ! self::isAccepted($number)) {
            return null;
        }

        return ltrim(self::util()->format($number, PhoneNumberFormat::E164), '+');
    }

    public static function format(string $phone): string
    {
        if (preg_match('/^7(\d{3})(\d{3})(\d{2})(\d{2})$/', $phone, $parts) === 1) {
            return "+7 ({$parts[1]}) {$parts[2]}-{$parts[3]}-{$parts[4]}";
        }

        $number = ctype_digit($phone) ? self::parse("+{$phone}", null) : null;

        if ($number === null || ! self::util()->isValidNumber($number)) {
            return $phone;
        }

        return self::util()->format($number, PhoneNumberFormat::INTERNATIONAL);
    }

    public static function isAllowedRegion(string $region): bool
    {
        if (! in_array($region, self::util()->getSupportedRegions(), true)) {
            return false;
        }

        $allowed = self::allowedRegions();

        return $allowed === [] || in_array($region, $allowed, true);
    }

    /** @return list<array{code: string, dial: int, name: string, flag: string, example: string}> */
    public static function countries(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $allowed = self::allowedRegions();
        $regions = $allowed === [] ? self::util()->getSupportedRegions() : $allowed;

        $countries = array_map(fn (string $region): array => [
            'code' => $region,
            'dial' => self::util()->getCountryCodeForRegion($region),
            'name' => Locale::getDisplayRegion("-{$region}", $locale) ?: $region,
            'flag' => self::flag($region),
            'example' => self::example($region),
        ], array_values(array_filter($regions, fn (string $region): bool => self::isAllowedRegion($region))));

        $collator = new Collator($locale);
        usort($countries, function (array $a, array $b) use ($collator): int {
            if ($a['code'] === self::DEFAULT_REGION || $b['code'] === self::DEFAULT_REGION) {
                return $a['code'] === self::DEFAULT_REGION ? -1 : 1;
            }

            return (int) $collator->compare($a['name'], $b['name']);
        });

        return $countries;
    }

    private static function example(string $region): string
    {
        $example = self::util()->getExampleNumberForType($region, PhoneNumberType::MOBILE);

        if ($example === null) {
            return '';
        }

        $international = self::util()->format($example, PhoneNumberFormat::INTERNATIONAL);

        return trim(substr($international, strlen("+{$example->getCountryCode()}")));
    }

    private static function flag(string $region): string
    {
        return implode('', array_map(
            fn (string $letter): string => mb_chr(0x1F1E6 + ord($letter) - ord('A')),
            str_split($region),
        ));
    }

    /** @return list<string> */
    private static function allowedRegions(): array
    {
        $configured = config('services.sms.allowed_countries', '');

        if (! is_string($configured)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $code): string => strtoupper(trim($code)),
            explode(',', $configured),
        )));
    }

    private static function parse(string $input, ?string $region): ?PhoneNumber
    {
        try {
            return self::util()->parse($input, $region);
        } catch (NumberParseException) {
            return null;
        }
    }

    private static function isAccepted(PhoneNumber $number): bool
    {
        if (! self::util()->isValidNumber($number)) {
            return false;
        }

        $region = self::util()->getRegionCodeForNumber($number);

        if ($region === null || ! self::isAllowedRegion($region)) {
            return false;
        }

        return in_array(self::util()->getNumberType($number), self::ACCEPTED_TYPES, true);
    }

    private static function util(): PhoneNumberUtil
    {
        return PhoneNumberUtil::getInstance();
    }
}
