<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCodeRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^7\d{10}$/'],
            'code' => ['required', 'string', 'size:4'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.required' => __('validation.required', ['attribute' => __('auth.phone_label')]),
            'phone.regex' => __('validation.regex', ['attribute' => __('auth.phone_label')]),
            'code.required' => __('validation.required', ['attribute' => __('auth.verify_title')]),
            'code.size' => __('validation.size.string', ['attribute' => __('auth.verify_title'), 'size' => 4]),
        ];
    }
}
