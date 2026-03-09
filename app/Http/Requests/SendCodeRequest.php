<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendCodeRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^7\d{10}$/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.required' => __('validation.required', ['attribute' => __('auth.phone_label')]),
            'phone.regex' => __('validation.regex', ['attribute' => __('auth.phone_label')]),
        ];
    }
}
