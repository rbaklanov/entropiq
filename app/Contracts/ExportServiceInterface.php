<?php

namespace App\Contracts;

use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface ExportServiceInterface
{
    /** @param array<string, mixed> $filters */
    public function transactionsToCsv(User $user, array $filters = []): StreamedResponse;

    /** @param array<string, mixed> $filters */
    public function transactionsToPdf(User $user, array $filters = []): StreamedResponse;

    /** @param array<string, mixed> $filters */
    public function transactionsToExcel(User $user, array $filters = []): StreamedResponse;
}
