<?php

namespace App\Services;

use App\Contracts\ExportServiceInterface;
use App\Enums\TransactionType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService implements ExportServiceInterface
{
    /** @param array<string, mixed> $filters */
    public function transactionsToCsv(User $user, array $filters = []): StreamedResponse
    {
        $query = $user->transactions()->with('category')->orderBy('date', 'desc');

        if (isset($filters['from']) && $filters['from'] instanceof Carbon) {
            $query->where('date', '>=', $filters['from']);
        }

        if (isset($filters['to']) && $filters['to'] instanceof Carbon) {
            $query->where('date', '<=', $filters['to']);
        }

        $transactions = $query->get();
        $locale = $user->locale->value;

        return response()->streamDownload(function () use ($transactions, $locale) {
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
                $categoryName = $transaction->category->name[$locale]
                    ?? $transaction->category->name['ru']
                    ?? '';

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
        throw new \RuntimeException('PDF export is not yet implemented.');
    }

    /** @param array<string, mixed> $filters */
    public function transactionsToExcel(User $user, array $filters = []): StreamedResponse
    {
        throw new \RuntimeException('Excel export is not yet implemented.');
    }
}
