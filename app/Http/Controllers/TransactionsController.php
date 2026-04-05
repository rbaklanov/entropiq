<?php

namespace App\Http\Controllers;

use App\Contracts\SubscriptionServiceInterface;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TransactionsController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly SubscriptionServiceInterface $subscriptionService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $filters = $request->only(['type', 'category_id', 'from', 'to', 'search']);

        if (! isset($filters['from'], $filters['to'])) {
            $filters['from'] = now()->startOfMonth()->toDateString();
            $filters['to'] = now()->endOfMonth()->toDateString();
        }

        $transactions = $this->transactionService->getForPeriod($user->id, $filters);

        $summary = $this->transactionService->getSummary(
            $user->id,
            Carbon::parse($filters['from']),
            Carbon::parse($filters['to']),
        );

        return view('pages.app.transactions.index', [
            'transactions' => $transactions,
            'summary' => $summary,
            'filters' => $filters,
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        if (! $this->subscriptionService->canAddTransaction($request->user())) {
            return redirect()
                ->route('transactions.index')
                ->with('error', __('subscription.premium_required'));
        }

        $request->user()->transactions()->create($request->validated());

        return redirect()
            ->route('transactions.index')
            ->with('success', __('transactions.created'));
    }

    public function show(Request $request, Transaction $transaction): View
    {
        $this->authorizeTransaction($request, $transaction);

        $transaction->load('category');

        return view('pages.app.transactions.show', compact('transaction'));
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeTransaction($request, $transaction);

        $transaction->update($request->validated());

        return redirect()
            ->route('transactions.index')
            ->with('success', __('transactions.updated'));
    }

    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeTransaction($request, $transaction);

        $transaction->delete();

        return redirect()
            ->route('transactions.index')
            ->with('success', __('transactions.deleted'));
    }

    private function authorizeTransaction(Request $request, Transaction $transaction): void
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);
    }
}
