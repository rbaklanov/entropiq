<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class TransactionsController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $filters = $request->only(['type', 'category_id', 'from', 'to', 'search']);

        if (! isset($filters['from'], $filters['to'])) {
            $filters['from'] = now()->startOfMonth()->toDateString();
            $filters['to'] = now()->endOfMonth()->toDateString();
        }

        $transactions = $this->transactionService->getForPeriod($user->id, $filters);

        return TransactionResource::collection($transactions);
    }

    public function store(StoreTransactionRequest $request): TransactionResource
    {
        $transaction = $request->user()
            ->transactions()
            ->create($request->validated());

        $transaction->load('category');

        return new TransactionResource($transaction);
    }

    public function show(Request $request, Transaction $transaction): TransactionResource
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        $transaction->load('category');

        return new TransactionResource($transaction);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): TransactionResource
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        $transaction->update($request->validated());
        $transaction->load('category');

        return new TransactionResource($transaction);
    }

    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        $transaction->delete();

        return response()->json(['message' => __('transactions.deleted')]);
    }

    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()));
        $to = Carbon::parse($request->input('to', now()->endOfMonth()->toDateString()));

        return response()->json(
            $this->transactionService->getSummary($user->id, $from, $to),
        );
    }
}
