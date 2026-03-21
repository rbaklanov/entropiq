<?php

namespace App\Livewire\Transactions;

use App\Models\Category;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class TransactionsList extends Component
{
    #[Url]
    public string $type = '';

    #[Url]
    public string $period = 'month';

    #[Url]
    public int $categoryId = 0;

    #[Url]
    public string $search = '';

    public int $perPage = 20;

    public function loadMore(): void
    {
        $this->perPage += 20;
    }

    public function setType(string $type): void
    {
        $this->type = $this->type === $type ? '' : $type;
        $this->perPage = 20;
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->perPage = 20;
    }

    public function deleteTransaction(int $id): void
    {
        $transaction = Transaction::find($id);

        if (! $transaction || $transaction->user_id !== auth()->id()) {
            return;
        }

        $transaction->delete();

        $this->dispatch('transaction-deleted');
    }

    /** @return array{from: string, to: string} */
    private function getPeriodDates(): array
    {
        return match ($this->period) {
            'week' => [
                'from' => now()->startOfWeek()->toDateString(),
                'to' => now()->endOfWeek()->toDateString(),
            ],
            'year' => [
                'from' => now()->startOfYear()->toDateString(),
                'to' => now()->endOfYear()->toDateString(),
            ],
            default => [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->endOfMonth()->toDateString(),
            ],
        };
    }

    /** @return array<string, mixed> */
    private function buildFilters(): array
    {
        $dates = $this->getPeriodDates();

        $filters = [
            'from' => $dates['from'],
            'to' => $dates['to'],
        ];

        if ($this->type !== '') {
            $filters['type'] = $this->type;
        }

        if ($this->categoryId > 0) {
            $filters['category_id'] = $this->categoryId;
        }

        if ($this->search !== '') {
            $filters['search'] = $this->search;
        }

        return $filters;
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @return Collection<string, Collection<int, Transaction>>
     */
    private function groupByDate(Collection $transactions): Collection
    {
        return $transactions->groupBy(
            fn (Transaction $t) => $t->date->translatedFormat('j F, l'),
        );
    }

    public function render(): View
    {
        $service = app(TransactionService::class);
        $userId = auth()->id();
        $filters = $this->buildFilters();

        $paginator = $service->getForPeriod($userId, $filters, $this->perPage);

        $dates = $this->getPeriodDates();
        $summary = $service->getSummary(
            $userId,
            Carbon::parse($dates['from']),
            Carbon::parse($dates['to']),
        );

        $categories = Category::forUser($userId)
            ->orderBy('sort_order')
            ->get();

        return view('livewire.transactions.transactions-list', [
            'grouped' => $this->groupByDate($paginator->getCollection()),
            'summary' => $summary,
            'categories' => $categories,
            'hasMore' => $paginator->hasMorePages(),
            'total' => $paginator->total(),
        ]);
    }
}
