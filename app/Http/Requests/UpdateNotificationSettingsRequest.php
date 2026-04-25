<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingsRequest extends FormRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'email_weekly' => ['sometimes', 'boolean'],
            'push_goals' => ['sometimes', 'boolean'],
            'push_ai_advice' => ['sometimes', 'boolean'],
        ];
    }
}
