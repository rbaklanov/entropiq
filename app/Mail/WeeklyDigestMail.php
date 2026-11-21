<?php

namespace App\Mail;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class WeeklyDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var array{total_income: int, total_expense: int, balance: int, transactions_count: int, top_categories: array<int, array{name: string, total: int}>} */
    public readonly array $digest;

    public function __construct(
        public readonly User $user,
        public readonly Carbon $periodFrom,
        public readonly Carbon $periodTo,
    ) {
        $this->digest = $this->buildDigest();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('digest.subject', ['from' => $this->periodFrom->translatedFormat('j M'), 'to' => $this->periodTo->translatedFormat('j M')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.weekly-digest',
        );
    }

    /** @return array{total_income: int, total_expense: int, total_savings: int, balance: int, transactions_count: int, top_categories: array<int, array{name: string, total: int}>} */
    private function buildDigest(): array
    {
        $transactions = $this->user->transactions()
            ->with('category')
            ->forPeriod($this->periodFrom, $this->periodTo)
            ->get();

        $savingsTransfers = $transactions->filter(fn (Transaction $transaction) => $transaction->isSavingsTransfer());
        $spending = $transactions
            ->where('type', TransactionType::Expense)
            ->reject(fn (Transaction $transaction) => $transaction->isSavingsTransfer());

        $totalIncome = $transactions->where('type', TransactionType::Income)->sum('amount');
        $totalExpense = $spending->sum('amount');

        $topCategories = $spending
            ->groupBy('category_id')
            ->map(fn ($group) => [
                'name' => $group->first()->category->localizedName(),
                'total' => $group->sum('amount'),
            ])
            ->sortByDesc('total')
            ->take(5)
            ->values()
            ->all();

        return [
            'total_income' => (int) $totalIncome,
            'total_expense' => (int) $totalExpense,
            'total_savings' => (int) $savingsTransfers->sum('amount'),
            'balance' => (int) ($totalIncome - $totalExpense),
            'transactions_count' => $transactions->count(),
            'top_categories' => $topCategories,
        ];
    }
}
