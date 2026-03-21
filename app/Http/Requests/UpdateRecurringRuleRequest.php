<?php

namespace App\Http\Requests;

use App\Enums\RecurringInterval;
use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecurringRuleRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'required', Rule::enum(TransactionType::class)],
            'amount' => ['sometimes', 'required', 'integer', 'min:1'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'interval' => ['sometimes', 'required', Rule::enum(RecurringInterval::class)],
            'currency_code' => ['sometimes', 'string', 'size:3'],
            'comment' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
