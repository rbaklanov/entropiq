<div class="space-y-6">

    {{-- Greeting --}}
    <div>
        <h1 class="text-h1">
            @if(auth()->user()->name)
                {{ __('dashboard.greeting', ['name' => auth()->user()->name]) }}
            @else
                {{ __('dashboard.greeting_short') }}
            @endif
        </h1>
        <p class="mt-1 text-sm text-gray-500">{{ now()->translatedFormat('j F, l') }}</p>
    </div>

    {{-- Balance card --}}
    <div class="rounded-2xl bg-gradient-to-br from-primary-600 to-primary-700 p-6 text-white shadow-lg">
        <p class="text-sm font-medium text-primary-100">{{ __('dashboard.balance') }}</p>
        <p class="mt-2 text-3xl font-bold tracking-tight">
            @php
                $balance = $monthlySummary['balance'];
                $rubles = abs($balance) / 100;
                $decimals = abs($balance) % 100 !== 0 ? 2 : 0;
                $formatted = number_format($rubles, $decimals, '.', ' ');
                $sign = $balance < 0 ? '−' : ($balance > 0 ? '+' : '');
            @endphp
            {{ $sign }}{{ $formatted }} ₽
        </p>
        <p class="mt-1 text-sm text-primary-200">{{ __('dashboard.balance_period') }}</p>
    </div>

    {{-- Monthly metrics --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-small text-gray-500">{{ __('dashboard.income') }}</p>
            <x-money-display :amount="$monthlySummary['income']" type="income" size="md" :showSign="true" />
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-small text-gray-500">{{ __('dashboard.expenses') }}</p>
            <x-money-display :amount="$monthlySummary['expense']" type="expense" size="md" :showSign="true" />
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-small text-gray-500">{{ __('dashboard.savings_rate') }}</p>
            <p class="text-number-md {{ $savingsRate >= 0 ? 'text-success-600' : 'text-danger-500' }}">
                {{ $savingsRate }}%
            </p>
        </div>
    </div>

    {{-- Goals ribbon --}}
    @if($goalData->isNotEmpty())
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('dashboard.goals') }}</h2>
                <a href="{{ route('goals.index') }}" class="text-sm text-primary-600 hover:text-primary-700">
                    {{ __('dashboard.view_all') }}
                </a>
            </div>
            <div class="flex gap-3 overflow-x-auto pb-2 -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 scrollbar-hide">
                @foreach($goalData as $item)
                    <x-goal-card
                        :goal="$item['goal']"
                        :monthlyPayment="$item['monthly_payment']"
                        :completionDate="$item['completion_date']"
                        class="min-w-[260px] max-w-[300px] flex-shrink-0"
                    />
                @endforeach
            </div>
        </div>
    @else
        <div class="rounded-2xl border-2 border-dashed border-gray-200 p-6 text-center">
            <p class="text-2xl">🎯</p>
            <p class="mt-2 text-sm font-medium text-gray-900">{{ __('dashboard.goals_empty') }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ __('dashboard.goals_empty_cta') }}</p>
            <a
                href="{{ route('goals.create') }}"
                class="mt-3 inline-block rounded-full bg-primary-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-primary-700"
            >
                {{ __('dashboard.goals_create') }}
            </a>
        </div>
    @endif

    {{-- Top expense categories --}}
    @if($topCategories->isNotEmpty())
        <div class="rounded-xl bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">{{ __('dashboard.top_expenses') }}</h2>
                <a href="{{ route('transactions.index', ['type' => 'expense']) }}" class="text-sm text-primary-600 hover:text-primary-700">
                    {{ __('dashboard.view_all') }}
                </a>
            </div>

            <div class="space-y-3">
                @foreach($topCategories as $cat)
                    @php
                        $percent = $totalExpense > 0 ? round($cat['total'] / $totalExpense * 100) : 0;
                    @endphp
                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-base">{{ $cat['icon'] }}</span>
                                <span class="text-sm text-gray-700">{{ $cat['name'] }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-money-display :amount="$cat['total']" type="expense" size="xs" />
                                <span class="text-small text-gray-400">{{ $percent }}%</span>
                            </div>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                style="width: {{ $percent }}%; background-color: {{ $cat['color'] }}"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Recent transactions --}}
    <div>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">{{ __('dashboard.recent_transactions') }}</h2>
            <a href="{{ route('transactions.index') }}" class="text-sm text-primary-600 hover:text-primary-700">
                {{ __('dashboard.view_all') }}
            </a>
        </div>

        @if($recentTransactions->isEmpty())
            <x-empty-state
                icon="📝"
                :title="__('transactions.no_transactions')"
                :description="__('transactions.no_transactions_cta')"
                :actionUrl="route('transactions.create')"
                :actionLabel="__('transactions.add')"
            />
        @else
            <div class="space-y-1">
                @foreach($recentTransactions as $transaction)
                    <x-transaction-row :transaction="$transaction" />
                @endforeach
            </div>
        @endif
    </div>

    {{-- Quick add button (mobile) --}}
    <div class="flex justify-center pb-4 lg:hidden">
        <a
            href="{{ route('transactions.create') }}"
            class="inline-flex items-center gap-2 rounded-full bg-primary-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-primary-700"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            {{ __('transactions.add') }}
        </a>
    </div>

</div>
