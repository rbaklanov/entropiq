<?php

namespace App\Rules;

use App\Support\PhoneNumbers;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts a mobile number in international form, normalized by {@see PhoneNumbers::normalize()}.
 */
class InternationalPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || PhoneNumbers::normalize($value) !== $value) {
            $fail(__('auth.phone_invalid'));
        }
    }
}
