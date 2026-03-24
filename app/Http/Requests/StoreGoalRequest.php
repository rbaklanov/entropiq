<?php

namespace App\Http\Requests;

use App\Enums\GoalType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGoalRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(GoalType::class)],
            'icon' => ['nullable', 'string', 'max:20'],
            'target_amount' => ['required', 'integer', 'min:1'],
            'initial_amount' => ['nullable', 'integer', 'min:0'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'target_date' => ['nullable', 'date', 'after:today'],
        ];
    }
}
