<?php

namespace App\Livewire\Transactions;

use App\Contracts\SubscriptionServiceInterface;
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

    #[Url]
    public string $customFrom = '';

    #[Url]
    public string $customTo = '';

    public int $periodOffset = 0;

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
        $this->periodOffset = 0;
        $this->perPage = 20;
    }

    public function prevPeriod(): void
    {
        $this->periodOffset--;
        $this->perPage = 20;
    }

    public function nextPeriod(): void
    {
        $this->periodOffset++;
        $this->perPage = 20;
    }

    public function setCustomRange(string $from, string $to): void
    {
        $this->customFrom = $from;
        $this->customTo = $to;
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

    private function getAnchorDate(): Carbon
    {
        return Carbon::now()->addWeeks(
            $this->period === 'week' ? $this->periodOffset : 0,
        )->addMonths(
            $this->period === 'month' ? $this->periodOffset : 0,
        )->addYears(
            $this->period === 'year' ? $this->periodOffset : 0,
        );
    }

    /** @return array{from: string, to: string} */
    private function getPeriodDates(): array
    {
        $anchor = $this->getAnchorDate();

        return match ($this->period) {
            'week' => [
                'from' => $anchor->copy()->startOfWeek()->toDateString(),
                'to' => $anchor->copy()->endOfWeek()->toDateString(),
            ],
            'year' => [
                'from' => $anchor->copy()->startOfYear()->toDateString(),
                'to' => $anchor->copy()->endOfYear()->toDateString(),
            ],
            'custom' => [
                'from' => $this->customFrom ?: now()->startOfMonth()->toDateString(),
                'to' => $this->customTo ?: now()->endOfMonth()->toDateString(),
            ],
            default => [
                'from' => $anchor->copy()->startOfMonth()->toDateString(),
                'to' => $anchor->copy()->endOfMonth()->toDateString(),
            ],
        };
    }

    public function getPeriodLabel(): string
    {
        $anchor = $this->getAnchorDate();

        return match ($this->period) {
            'week' => $anchor->copy()->startOfWeek()->translatedFormat('d.m')
                .' — '
                .$anchor->copy()->endOfWeek()->translatedFormat('d.m.Y'),
            'year' => (string) $anchor->year,
            'month' => $anchor->translatedFormat('F Y'),
            default => '',
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

        $subscriptionService = app(SubscriptionServiceInterface::class);
        $user = auth()->user();
        $transactionsRemaining = $subscriptionService->transactionsRemaining($user);

        return view('livewire.transactions.transactions-list', [
            'grouped' => $this->groupByDate($paginator->getCollection()),
            'summary' => $summary,
            'categories' => $categories,
            'hasMore' => $paginator->hasMorePages(),
            'total' => $paginator->total(),
            'periodLabel' => $this->getPeriodLabel(),
            'transactionsRemaining' => $transactionsRemaining,
        ]);
    }
}
