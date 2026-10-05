<?php

namespace App\Http\Controllers;

use App\Contracts\ExportServiceInterface;
use App\Http\Requests\ExportTransactionsRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(
        private readonly ExportServiceInterface $exportService,
    ) {}

    public function transactions(ExportTransactionsRequest $request): StreamedResponse
    {
        $user = $request->user();
        $filters = $request->filters();

        return match ($request->exportFormat()) {
            'pdf' => $this->exportService->transactionsToPdf($user, $filters),
            'xlsx' => $this->exportService->transactionsToExcel($user, $filters),
            default => $this->exportService->transactionsToCsv($user, $filters),
        };
    }
}
