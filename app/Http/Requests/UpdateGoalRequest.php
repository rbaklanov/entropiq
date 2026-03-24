<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGoalRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:20'],
            'target_amount' => ['sometimes', 'integer', 'min:1'],
            'target_date' => ['nullable', 'date', 'after:today'],
        ];
    }
}
