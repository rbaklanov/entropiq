<?php

namespace App\Integrations\Robokassa;

class RobokassaSignature
{
    public function __construct(private readonly string $algorithm = 'md5') {}

    public function make(string ...$parts): string
    {
        return strtoupper(hash($this->algorithm, implode(':', $parts)));
    }

    public function matches(string $signature, string ...$parts): bool
    {
        return hash_equals($this->make(...$parts), strtoupper($signature));
    }

    /**
     * Robokassa expects amounts in rubles with a dot, the same string must go to the request and the signature.
     */
    public static function amount(int $kopecks): string
    {
        return number_format($kopecks / 100, 2, '.', '');
    }

    public static function kopecks(string $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
