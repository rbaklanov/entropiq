<?php

namespace App\Http\Requests;

use App\Enums\Locale;
use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'locale' => ['sometimes', 'string', Rule::enum(Locale::class)],
            'currency_code' => ['sometimes', 'string', 'size:3', Rule::exists(Currency::class, 'code')],
        ];
    }
}
