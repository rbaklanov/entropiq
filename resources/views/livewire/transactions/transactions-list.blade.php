<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-h1">{{ __('transactions.title') }}</h1>
        <a href="{{ route('transactions.create') }}"
           class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            {{ __('transactions.add') }}
        </a>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-small text-gray-500">{{ __('transactions.summary_income') }}</p>
            <x-money-display :amount="$summary['income']" type="income" size="md" />
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-small text-gray-500">{{ __('transactions.summary_expense') }}</p>
            <x-money-display :amount="$summary['expense']" type="expense" size="md" />
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-small text-gray-500">{{ __('transactions.summary_balance') }}</p>
            <x-money-display :amount="$summary['balance']" size="md" />
        </div>
    </div>

    {{-- Filters --}}
    <div class="space-y-3">

        {{-- Type tabs --}}
        <div class="flex gap-2">
            <button
                wire:click="setType('')"
                class="rounded-lg px-4 py-2 text-sm font-medium transition
                    {{ $type === '' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' }}"
            >
                {{ __('transactions.filter_all') }}
            </button>
            <button
                wire:click="setType('income')"
                class="rounded-lg px-4 py-2 text-sm font-medium transition
                    {{ $type === 'income' ? 'bg-success-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' }}"
            >
                {{ __('transactions.filter_income') }}
            </button>
            <button
                wire:click="setType('expense')"
                class="rounded-lg px-4 py-2 text-sm font-medium transition
                    {{ $type === 'expense' ? 'bg-danger-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' }}"
            >
                {{ __('transactions.filter_expense') }}
            </button>
        </div>

        {{-- Period + Category + Search --}}
        <div class="flex flex-wrap items-center gap-3">
            <select
                wire:model.live="period"
                class="rounded-xl border-2 border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 outline-none transition-all duration-200 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 focus:shadow-md"
            >
                <option value="week">{{ __('transactions.filter_week') }}</option>
                <option value="month">{{ __('transactions.filter_month') }}</option>
                <option value="year">{{ __('transactions.filter_period') }}</option>
            </select>

            <select
                wire:model.live="categoryId"
                class="rounded-xl border-2 border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 outline-none transition-all duration-200 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 focus:shadow-md"
            >
                <option value="0">{{ __('transactions.category') }}</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name['ru'] ?? $cat->name }}</option>
                @endforeach
            </select>

            <div class="relative flex-1">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('transactions.search_placeholder') }}"
                    class="w-full rounded-xl border-2 border-gray-200 bg-gray-50 px-3 py-2 pl-9 text-sm text-gray-700 outline-none transition-all duration-200 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 focus:shadow-md"
                />
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Transactions list grouped by date --}}
    @if($grouped->isEmpty())
        <x-empty-state
            icon="📝"
            :title="__('transactions.no_transactions')"
            :description="__('transactions.no_transactions_cta')"
            :actionUrl="route('transactions.create')"
            :actionLabel="__('transactions.add')"
        />
    @else
        <div class="space-y-6">
            @foreach($grouped as $dateLabel => $transactions)
                <div>
                    <h3 class="mb-2 text-caption font-medium text-gray-500">{{ $dateLabel }}</h3>
                    <div class="space-y-1">
                        @foreach($transactions as $transaction)
                            <x-transaction-row :transaction="$transaction" />
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Load more --}}
        @if($hasMore)
            <div class="flex justify-center pt-2" x-intersect="$wire.loadMore()">
                <div wire:loading wire:target="loadMore" class="flex items-center gap-2 text-sm text-gray-500">
                    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    {{ __('common.loading') }}
                </div>
            </div>
        @endif

        <p class="text-center text-small text-gray-400">
            {{ __('transactions.all_transactions') }}: {{ $total }}
        </p>
    @endif

</div>
