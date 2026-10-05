<?php

namespace App\Services;

use App\Contracts\ExportServiceInterface;
use App\Enums\TransactionType;
use App\Exports\TransactionsExport;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService implements ExportServiceInterface
{
    /** @param array<string, mixed> $filters */
    public function transactionsToCsv(User $user, array $filters = []): StreamedResponse
    {
        $transactions = $this->fetchTransactions($user, $filters);

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                __('export.date'),
                __('export.type'),
                __('export.category'),
                __('export.amount'),
                __('export.currency'),
                __('export.comment'),
            ], ';');

            foreach ($transactions as $transaction) {
                $categoryName = $transaction->category->localizedName();

                $type = $transaction->type === TransactionType::Income
                    ? __('export.income')
                    : __('export.expense');

                fputcsv($handle, [
                    $transaction->date->format('d.m.Y'),
                    $type,
                    $categoryName,
                    number_format($transaction->amount / 100, 2, ',', ''),
                    $transaction->currency_code,
                    $transaction->comment ?? '',
                ], ';');
            }

            fclose($handle);
        }, "transactions_{$user->id}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** @param array<string, mixed> $filters */
    public function transactionsToPdf(User $user, array $filters = []): StreamedResponse
    {
        $transactions = $this->fetchTransactions($user, $filters);

        $income = $transactions->where('type', TransactionType::Income)->sum('amount');
        $expense = $transactions->where('type', TransactionType::Expense)->sum('amount');

        $pdf = Pdf::loadView('pdf.transactions', [
            'transactions' => $transactions,
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
            'currency' => $user->currency_code,
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
            'generatedAt' => now(),
        ]);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            "transactions_{$user->id}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
    }

    /** @param array<string, mixed> $filters */
    public function transactionsToExcel(User $user, array $filters = []): StreamedResponse
    {
        $content = Excel::raw(
            new TransactionsExport($this->fetchTransactions($user, $filters)),
            ExcelType::XLSX,
        );

        return response()->streamDownload(
            fn () => print ($content),
            "transactions_{$user->id}.xlsx",
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Transaction>
     */
    private function fetchTransactions(User $user, array $filters): Collection
    {
        $query = $user->transactions()->with('category')->orderBy('date', 'desc');

        if (isset($filters['from']) && $filters['from'] instanceof Carbon) {
            $query->where('date', '>=', $filters['from']);
        }

        if (isset($filters['to']) && $filters['to'] instanceof Carbon) {
            $query->where('date', '<=', $filters['to']);
        }

        return $query->get();
    }
}
