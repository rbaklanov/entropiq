<?php

namespace App\Http\Requests;

use App\Enums\Locale;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($this->user()?->id)->whereNull('deleted_at'),
            ],
            'locale' => ['sometimes', 'string', Rule::enum(Locale::class)],
            'currency_code' => ['sometimes', 'string', 'size:3', Rule::exists(Currency::class, 'code')],
        ];
    }
}
