<div class="mx-auto max-w-5xl space-y-6">

    {{-- Greeting --}}
    <div>
        <h1 class="text-h1 sm:text-4xl">
            @if(auth()->user()->name)
                {{ __('dashboard.greeting', ['name' => auth()->user()->name]) }}
            @else
                {{ __('dashboard.greeting_short') }}
            @endif
        </h1>
        <p class="mt-1 text-[15px] text-gray-500">{{ now()->translatedFormat('j F, l') }}</p>
    </div>

    {{-- Balance card --}}
    <x-balance-card
        :nominalBalance="$nominalBalance"
        :realBalance="$realBalance"
        :inflationLoss="$inflationLoss"
    />

    {{-- Monthly metrics --}}
    <section class="rounded-md border border-gray-200 bg-white px-6 py-5">
        <h2 class="text-sm font-semibold text-gray-900">{{ ucfirst(now()->translatedFormat('F')) }}</h2>
        <dl class="mt-3 grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3">
            <div>
                <dt class="text-[13px] text-gray-500">{{ __('dashboard.income') }}</dt>
                <dd><x-money-display :amount="$monthlySummary['income']" type="income" size="md" :showSign="true" /></dd>
            </div>
            <div>
                <dt class="text-[13px] text-gray-500">{{ __('dashboard.expenses') }}</dt>
                <dd><x-money-display :amount="$monthlySummary['expense']" type="expense" size="md" :showSign="true" /></dd>
            </div>
            <div class="col-span-2 sm:col-span-1">
                <dt class="text-[13px] text-gray-500">{{ __('dashboard.savings_rate') }}</dt>
                <dd class="text-number-md {{ $savingsRate >= 0 ? 'text-gray-900' : 'text-danger-600' }}">{{ $savingsRate }}%</dd>
            </div>
        </dl>
    </section>

    <div @class(['grid gap-6', 'lg:grid-cols-2' => (bool) $dailyAdvice])>
        {{-- Inflation widget --}}
        <x-inflation-widget
            :personalInflation="$personalInflation"
            :nationalInflation="$nationalInflation"
        />

        {{-- Daily advice --}}
        @if($dailyAdvice)
            <x-ai-advice-card :advice="$dailyAdvice" :teaser="$adviceTeaser" :locked="$adviceLocked" />
        @endif
    </div>

    {{-- Goals ribbon --}}
    @if($goalData->isNotEmpty())
        <section>
            <div class="mb-3 flex items-baseline justify-between">
                <h2 class="text-[15px] font-semibold text-gray-900">{{ __('dashboard.goals') }}</h2>
                <a href="{{ route('goals.index') }}" class="text-sm text-primary-600 hover:text-primary-700">
                    {{ __('dashboard.view_all') }}
                </a>
            </div>
            <div class="scrollbar-hide -mx-4 flex gap-4 overflow-x-auto px-4 pb-2 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                @foreach($goalData as $item)
                    <x-goal-card
                        :goal="$item['goal']"
                        :monthlyPayment="$item['monthly_payment']"
                        :completionDate="$item['completion_date']"
                        :realProgress="$item['real_progress']"
                        class="min-w-[260px] max-w-[300px] flex-shrink-0"
                    />
                @endforeach
            </div>
        </section>
    @else
        <section class="rounded-md border border-dashed border-gray-300 p-8 text-center">
            <h2 class="font-serif text-xl font-semibold text-gray-900">{{ __('dashboard.goals_empty') }}</h2>
            <p class="mt-1.5 text-sm text-gray-500">{{ __('dashboard.goals_empty_cta') }}</p>
            <a
                href="{{ route('goals.create') }}"
                class="mt-4 inline-flex min-h-11 items-center rounded-md bg-primary-600 px-5 text-sm font-semibold text-white transition-colors hover:bg-primary-700"
            >
                {{ __('dashboard.goals_create') }}
            </a>
        </section>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Top expense categories --}}
        @if($topCategories->isNotEmpty())
            <section>
                <div class="mb-1 flex items-baseline justify-between">
                    <h2 class="text-[15px] font-semibold text-gray-900">{{ __('dashboard.top_expenses') }}</h2>
                    <a href="{{ route('transactions.index', ['type' => 'expense']) }}" class="text-sm text-primary-600 hover:text-primary-700">
                        {{ __('dashboard.view_all') }}
                    </a>
                </div>

                <ul>
                    @foreach($topCategories as $cat)
                        @php
                            $percent = $totalExpense > 0 ? round($cat['total'] / $totalExpense * 100) : 0;
                        @endphp
                        <li class="border-b border-gray-200 py-2.5">
                            <div class="flex items-center justify-between gap-3">
                                <span class="flex items-center gap-2 text-[15px] text-gray-900">
                                    <x-category-glyph :icon="$cat['icon']" class="h-[18px] w-[18px] text-gray-500" />
                                    {{ $cat['name'] }}
                                </span>
                                <span class="flex items-baseline gap-2">
                                    <x-money-display :amount="$cat['total']" type="expense" size="xs" />
                                    <span class="text-small text-gray-500">{{ $percent }}%</span>
                                </span>
                            </div>
                            <div aria-hidden="true" class="mt-1.5 h-1 overflow-hidden rounded-sm bg-gray-100">
                                <div class="ink-grow h-full rounded-sm bg-gray-900" style="width: {{ $percent }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Recent transactions --}}
        <section @class(['lg:col-span-2' => $topCategories->isEmpty()])>
            <div class="mb-1 flex items-baseline justify-between">
                <h2 class="text-[15px] font-semibold text-gray-900">{{ __('dashboard.recent_transactions') }}</h2>
                <a href="{{ route('transactions.index') }}" class="text-sm text-primary-600 hover:text-primary-700">
                    {{ __('dashboard.view_all') }}
                </a>
            </div>

            @if($recentTransactions->isEmpty())
                <x-empty-state
                    :title="__('transactions.no_transactions')"
                    :description="__('transactions.no_transactions_cta')"
                    :actionUrl="route('transactions.create')"
                    :actionLabel="__('transactions.add')"
                />
            @else
                <div class="divide-y divide-gray-200 border-b border-gray-200">
                    @foreach($recentTransactions as $transaction)
                        <x-transaction-row :transaction="$transaction" />
                    @endforeach
                </div>
            @endif
        </section>
    </div>

</div>
