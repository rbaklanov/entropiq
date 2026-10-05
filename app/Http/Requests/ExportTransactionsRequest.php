<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ExportTransactionsRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'format' => ['nullable', Rule::in(['csv', 'pdf', 'xlsx'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ];
    }

    public function exportFormat(): string
    {
        return (string) $this->input('format', 'csv');
    }

    /** @return array<string, Carbon> */
    public function filters(): array
    {
        $filters = [];

        if ($this->filled('from')) {
            $filters['from'] = Carbon::parse($this->input('from'))->startOfDay();
        }

        if ($this->filled('to')) {
            $filters['to'] = Carbon::parse($this->input('to'))->endOfDay();
        }

        return $filters;
    }
}
