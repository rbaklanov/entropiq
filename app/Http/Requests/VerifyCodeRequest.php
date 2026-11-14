<?php

namespace App\Http\Requests;

use App\Rules\InternationalPhone;
use App\Support\PhoneNumbers;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyCodeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');

        if (is_string($phone) || is_int($phone)) {
            $this->merge(['phone' => PhoneNumbers::normalize((string) $phone) ?? (string) $phone]);
        }
    }

    /** @return array<string, array<int, string|ValidationRule>> */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new InternationalPhone],
            'consent' => ['accepted'],
            'code' => ['required', 'string', 'size:4'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.required' => __('validation.required', ['attribute' => __('auth.phone_label')]),
            'consent.accepted' => __('auth.consent_required'),
            'code.required' => __('validation.required', ['attribute' => __('auth.verify_title')]),
            'code.size' => __('validation.size.string', ['attribute' => __('auth.verify_title'), 'size' => 4]),
        ];
    }
}
