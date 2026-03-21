<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'required', Rule::enum(TransactionType::class)],
            'amount' => ['sometimes', 'required', 'integer', 'min:1'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'date' => ['sometimes', 'required', 'date'],
            'comment' => ['nullable', 'string', 'max:255'],
        ];
    }
}
