<?php

namespace App\Http\Controllers;

use App\Contracts\ExportServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(
        private readonly ExportServiceInterface $exportService,
    ) {}

    public function transactions(Request $request): StreamedResponse
    {
        $filters = [];

        if ($request->filled('from')) {
            $filters['from'] = Carbon::parse($request->input('from'));
        }

        if ($request->filled('to')) {
            $filters['to'] = Carbon::parse($request->input('to'));
        }

        return $this->exportService->transactionsToCsv($request->user(), $filters);
    }
}
