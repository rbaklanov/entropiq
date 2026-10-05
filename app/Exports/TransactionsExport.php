<?php

namespace App\Exports;

use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/** @implements WithMapping<Transaction> */
class TransactionsExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping
{
    /** @param Collection<int, Transaction> $transactions */
    public function __construct(private readonly Collection $transactions) {}

    /** @return Collection<int, Transaction> */
    public function collection(): Collection
    {
        return $this->transactions;
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            __('export.date'),
            __('export.type'),
            __('export.category'),
            __('export.amount'),
            __('export.currency'),
            __('export.comment'),
        ];
    }

    /**
     * @param  Transaction  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            Date::dateTimeToExcel($row->date),
            $row->type === TransactionType::Income ? __('export.income') : __('export.expense'),
            $row->category->localizedName(),
            $row->amount / 100,
            $row->currency_code,
            $row->comment ?? '',
        ];
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return [
            'A' => 'dd.mm.yyyy',
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
