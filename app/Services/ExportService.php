<?php

namespace App\Services;

use App\Contracts\ExportServiceInterface;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService implements ExportServiceInterface
{
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
