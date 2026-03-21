<?php

namespace App\Http\Requests;

use App\Enums\RecurringInterval;
use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecurringRuleRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount' => ['required', 'integer', 'min:1'],
            'category_id' => ['required', 'exists:categories,id'],
            'interval' => ['required', Rule::enum(RecurringInterval::class)],
            'start_date' => ['required', 'date'],
            'currency_code' => ['sometimes', 'string', 'size:3'],
            'comment' => ['nullable', 'string', 'max:255'],
        ];
    }
}
