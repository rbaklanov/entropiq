<?php

namespace App\Contracts;

use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface ExportServiceInterface
{
    public function transactionsToPdf(User $user, array $filters = []): StreamedResponse;

    public function transactionsToExcel(User $user, array $filters = []): StreamedResponse;
}
