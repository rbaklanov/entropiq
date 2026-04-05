<div class="space-y-6">

    {{-- Header --}}
    <h1 class="text-h1">{{ __('analytics.title') }}</h1>

    {{-- Tabs --}}
    <div class="flex gap-1 rounded-xl bg-gray-100 p-1">
        @foreach(['categories' => 'tab_categories', 'balance' => 'tab_balance', 'inflation' => 'tab_inflation'] as $tabKey => $labelKey)
            <button
                wire:click="setTab('{{ $tabKey }}')"
                class="flex-1 rounded-lg px-3 py-2 text-sm font-medium transition {{ $tab === $tabKey ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
            >
                {{ __("analytics.{$labelKey}") }}
            </button>
        @endforeach
    </div>

    {{-- Period selector --}}
    <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
        @foreach(['week', 'month', 'quarter', 'year', 'all'] as $p)
            <button
                wire:click="setPeriod('{{ $p }}')"
                class="whitespace-nowrap rounded-full px-4 py-1.5 text-sm font-medium transition {{ $period === $p ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
            >
                {{ __("analytics.period_{$p}") }}
            </button>
        @endforeach
    </div>

    @if($periodLocked)
        <x-premium-lock>
            <div class="rounded-xl bg-white p-8 text-center">
                <p class="text-2xl">📊</p>
                <p class="mt-2 text-sm font-medium text-gray-900">{{ __('subscription.period_limit') }}</p>
            </div>
        </x-premium-lock>
    @else

    {{-- Content --}}
    <div class="relative min-h-[200px]" wire:loading.class="opacity-50 pointer-events-none">

        {{-- ================================================================
             Tab: Categories
             ================================================================ --}}
        @if($tab === 'categories')
            @if(empty($expenses))
                <x-empty-state
                    icon="📊"
                    :title="__('analytics.no_data')"
                    :description="__('analytics.no_data_hint')"
                />
            @else
                <div class="space-y-6">
                    <div class="rounded-xl bg-white p-5 shadow-sm" wire:key="donut-{{ $period }}">
                        <x-donut-chart
                            :data="$chartData"
                            :labels="$chartLabels"
                            :colors="$chartColors"
                        />
                    </div>

                    <div class="rounded-xl bg-white shadow-sm divide-y divide-gray-50">
                        <div class="hidden sm:flex items-center px-5 py-3 text-small font-medium text-gray-400">
                            <span class="flex-1">{{ __('analytics.category_column') }}</span>
                            <span class="w-28 text-right">{{ __('analytics.amount_column') }}</span>
                            <span class="w-16 text-right">{{ __('analytics.percent_column') }}</span>
                            <span class="w-20 text-right">{{ __('analytics.trend_column') }}</span>
                        </div>

                        @foreach($expenses as $expense)
                            @php
                                $trend = $trendsMap->get($expense['category_id']);
                                $direction = $trend['direction'] ?? 'stable';
                                $changePercent = $trend['change_percent'] ?? null;
                            @endphp
                            <div class="flex items-center px-5 py-3">
                                <div class="flex flex-1 items-center gap-3 min-w-0">
                                    <x-category-icon
                                        :icon="$expense['category_icon'] ?? '📦'"
                                        :color="$expense['category_color'] ?? '#6366F1'"
                                        size="sm"
                                    />
                                    <span class="text-sm font-medium text-gray-900 truncate">
                                        {{ $expense['category_name'][$locale] ?? '—' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 sm:gap-0">
                                    <span class="sm:w-28 text-right">
                                        <x-money-display :amount="$expense['total']" size="xs" />
                                    </span>
                                    <span class="hidden sm:inline-block w-16 text-right text-caption text-gray-500">
                                        {{ round($expense['share'] * 100, 1) }}%
                                    </span>
                                    <span class="sm:w-20 text-right text-caption">
                                        @if($direction === 'up')
                                            <span class="text-danger-500">↑{{ abs($changePercent) }}%</span>
                                        @elseif($direction === 'down')
                                            <span class="text-success-500">↓{{ abs($changePercent) }}%</span>
                                        @elseif($direction === 'new')
                                            <span class="text-primary-400 text-small">new</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        {{-- ================================================================
             Tab: Balance dynamics
             ================================================================ --}}
        @elseif($tab === 'balance')
            @if(empty($dynamics))
                <x-empty-state
                    icon="📈"
                    :title="__('analytics.no_data')"
                    :description="__('analytics.no_data_hint')"
                />
            @else
                <div class="space-y-6">
                    <div class="rounded-xl bg-white p-5 shadow-sm" wire:key="line-{{ $period }}">
                        <x-line-chart
                            :series="$chartSeries"
                            :categories="$chartCategories"
                        />
                    </div>

                    @if($inflationLoss > 0)
                        <div class="rounded-xl bg-warning-50 border border-warning-100 p-4">
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 text-lg">⚠️</span>
                                <p class="text-sm text-warning-800">
                                    {{ __('analytics.inflation_loss', ['amount' => number_format($inflationLoss / 100, 0, '.', ' ') . ' ₽']) }}
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

        {{-- ================================================================
             Tab: Personal inflation
             ================================================================ --}}
        @elseif($tab === 'inflation')
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-xl bg-white p-5 shadow-sm text-center">
                        <p class="text-small text-gray-500 mb-2">{{ __('analytics.personal_inflation') }}</p>
                        <p class="text-number-lg {{ $personalRate > $officialRate ? 'text-danger-500' : 'text-success-500' }}">
                            {{ number_format($personalRate * 100, 1) }}%
                        </p>
                    </div>
                    <div class="rounded-xl bg-white p-5 shadow-sm text-center">
                        <p class="text-small text-gray-500 mb-2">{{ __('analytics.average_inflation') }}</p>
                        <p class="text-number-lg text-gray-900">
                            {{ number_format($officialRate * 100, 1) }}%
                        </p>
                    </div>
                </div>

                <p class="text-caption text-gray-500 px-1">
                    {{ __('analytics.inflation_explanation') }}
                </p>

                @if(!empty($breakdown))
                    <div class="rounded-xl bg-white shadow-sm divide-y divide-gray-50">
                        <div class="hidden sm:flex items-center px-5 py-3 text-small font-medium text-gray-400">
                            <span class="flex-1">{{ __('analytics.inflation_category') }}</span>
                            <span class="w-32 text-right">{{ __('analytics.inflation_share') }}</span>
                            <span class="w-32 text-right">{{ __('analytics.inflation_cpi') }}</span>
                            <span class="w-28 text-right">{{ __('analytics.inflation_contribution') }}</span>
                        </div>

                        @foreach($breakdown as $row)
                            <div class="flex items-center px-5 py-3">
                                <div class="flex flex-1 items-center gap-3 min-w-0">
                                    <span class="text-base">{{ $row['category_icon'] ?? '📦' }}</span>
                                    <span class="text-sm text-gray-900 truncate">{{ $row['category_name'][$locale] ?? '—' }}</span>
                                </div>
                                <div class="flex items-center gap-0">
                                    <span class="w-32 text-right text-caption text-gray-600">
                                        {{ number_format($row['share'] * 100, 1) }}%
                                    </span>
                                    <span class="w-32 text-right text-caption text-gray-600">
                                        {{ number_format($row['category_cpi'] * 100, 1) }}%
                                    </span>
                                    <span class="w-28 text-right text-caption font-medium {{ $row['contribution'] > 0 ? 'text-danger-500' : 'text-gray-600' }}">
                                        {{ number_format($row['contribution'] * 100, 2) }}%
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

    </div>

    @endif

</div>
