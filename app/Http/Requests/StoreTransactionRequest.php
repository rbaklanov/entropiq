<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount' => ['required', 'integer', 'min:1'],
            'category_id' => ['required', 'exists:categories,id'],
            'date' => ['required', 'date'],
            'comment' => ['nullable', 'string', 'max:255'],
        ];
    }
}
