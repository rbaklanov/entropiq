<?php

namespace App\Livewire\Transactions;

use App\Contracts\SubscriptionServiceInterface;
use App\Enums\RecurringInterval;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\RecurringService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class TransactionForm extends Component
{
    public ?int $transactionId = null;

    public string $type = 'expense';

    public int $amount = 0;

    public string $amountDisplay = '';

    public int $categoryId = 0;

    public string $date = '';

    public string $comment = '';

    public bool $showComment = false;

    public bool $showRecurring = false;

    public bool $isRecurring = false;

    public string $recurringInterval = 'monthly';

    public function mount(?int $transaction = null): void
    {
        $this->date = now()->toDateString();

        if ($transaction) {
            $existing = Transaction::findOrFail($transaction);
            abort_unless($existing->user_id === auth()->id(), 403);

            $this->transactionId = $existing->id;
            $this->type = $existing->type->value;
            $this->amount = $existing->amount;
            $this->amountDisplay = $this->formatAmount($existing->amount);
            $this->categoryId = $existing->category_id;
            $this->date = $existing->date->toDateString();
            $this->comment = $existing->comment ?? '';
            $this->showComment = $this->comment !== '';
        }
    }

    public function updatedAmountDisplay(string $value): void
    {
        $normalized = str_replace(',', '.', $value);
        $normalized = preg_replace('/[^\d.]/', '', $normalized);

        $parts = explode('.', $normalized, 2);
        $integer = ltrim($parts[0], '0') ?: '';
        $decimal = isset($parts[1]) ? substr($parts[1], 0, 2) : null;

        $kopecks = ((int) $integer) * 100;
        if ($decimal !== null) {
            $kopecks += (int) str_pad($decimal, 2, '0');
        }

        $this->amount = $kopecks;

        if ($integer === '' && $decimal === null) {
            $this->amountDisplay = '';

            return;
        }

        $formatted = $integer !== '' ? number_format((int) $integer, 0, '', ' ') : '0';
        $this->amountDisplay = $decimal !== null
            ? "{$formatted}.{$decimal}"
            : $formatted;
    }

    private function formatAmount(int $kopecks): string
    {
        $rubles = intdiv($kopecks, 100);
        $cents = $kopecks % 100;

        $formatted = number_format($rubles, 0, '', ' ');

        if ($cents > 0) {
            return "{$formatted}.".str_pad((string) $cents, 2, '0');
        }

        return $formatted;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
        $this->categoryId = 0;
    }

    public function selectCategory(int $id): void
    {
        $this->categoryId = $id;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'type' => ['required'],
            'amount' => ['required', 'integer', 'min:1'],
            'categoryId' => ['required', 'exists:categories,id'],
            'date' => ['required', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $typeLabel = $this->type === 'income'
            ? __('transactions.income_genitive')
            : __('transactions.expense_genitive');

        return [
            'type.required' => __('transactions.validation.type_required'),
            'amount.required' => __('transactions.validation.amount_required'),
            'amount.min' => __('transactions.validation.amount_required'),
            'categoryId.required' => __('transactions.validation.category_required', ['type' => $typeLabel]),
            'categoryId.exists' => __('transactions.validation.category_required', ['type' => $typeLabel]),
            'date.required' => __('transactions.validation.date_required'),
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'type' => $this->type,
            'amount' => $this->amount,
            'category_id' => $this->categoryId,
            'date' => $this->date,
            'comment' => $this->comment ?: null,
        ];

        if (! $this->transactionId) {
            $subscriptionService = app(SubscriptionServiceInterface::class);

            if (! $subscriptionService->canAddTransaction(auth()->user())) {
                session()->flash('error', __('subscription.premium_required'));
                $this->redirectRoute('transactions.index');

                return;
            }
        }

        if ($this->transactionId) {
            $transaction = Transaction::findOrFail($this->transactionId);
            abort_unless($transaction->user_id === auth()->id(), 403);
            $transaction->update($data);

            session()->flash('success', __('transactions.updated'));
        } else {
            $data['user_id'] = auth()->id();
            Transaction::create($data);

            if ($this->isRecurring) {
                app(RecurringService::class)->createRule(auth()->id(), [
                    'type' => $this->type,
                    'amount' => $this->amount,
                    'category_id' => $this->categoryId,
                    'interval' => $this->recurringInterval,
                    'start_date' => $this->date,
                    'comment' => $this->comment ?: null,
                ]);
            }

            session()->flash('success', __('transactions.created'));
        }

        $this->redirectRoute('transactions.index');
    }

    /** @return Collection<int, Category> */
    private function getCategories(): Collection
    {
        $query = Category::forUser(auth()->id())
            ->orderBy('sort_order');

        if ($this->type !== '') {
            $query->where('type', $this->type);
        }

        return $query->get();
    }

    public function render(): View
    {
        return view('livewire.transactions.transaction-form', [
            'categories' => $this->getCategories(),
            'intervals' => RecurringInterval::cases(),
            'isEditing' => $this->transactionId !== null,
        ]);
    }
}
