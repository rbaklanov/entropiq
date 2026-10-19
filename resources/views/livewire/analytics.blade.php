<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-h1">{{ __('analytics.title') }}</h1>

        <div class="flex gap-2">
            @foreach(['pdf' => 'export_pdf', 'xlsx' => 'export_excel'] as $format => $labelKey)
                <a
                    href="{{ auth()->user()->isPremium() ? route('analytics.export', $exportParams + ['format' => $format]) : route('settings.subscription') }}"
                    @unless(auth()->user()->isPremium()) title="{{ __('subscription.premium_lock') }}" @endunless
                    class="inline-flex min-h-10 items-center gap-1.5 rounded-md border border-gray-200 px-3 text-sm font-medium text-gray-900 transition-colors hover:border-gray-900"
                >
                    @unless(auth()->user()->isPremium())
                        <svg data-testid="export-lock" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="1.5" /><path d="M8 11V7a4 4 0 0 1 8 0v4" /></svg>
                    @endunless
                    {{ __("analytics.{$labelKey}") }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 rounded-md bg-gray-100 p-1">
        @foreach(['categories' => 'tab_categories', 'balance' => 'tab_balance', 'inflation' => 'tab_inflation'] as $tabKey => $labelKey)
            <button
                type="button"
                wire:click="setTab('{{ $tabKey }}')"
                aria-pressed="{{ $tab === $tabKey ? 'true' : 'false' }}"
                class="min-h-10 flex-1 rounded px-3 text-sm transition-colors {{ $tab === $tabKey ? 'bg-white font-semibold text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}"
            >
                {{ __("analytics.{$labelKey}") }}
            </button>
        @endforeach
    </div>

    {{-- Period selector --}}
    <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
        @foreach(['week', 'month', 'quarter', 'year', 'all'] as $p)
            <button
                type="button"
                wire:click="setPeriod('{{ $p }}')"
                aria-pressed="{{ $period === $p ? 'true' : 'false' }}"
                class="min-h-10 whitespace-nowrap rounded-md border px-4 text-sm transition-colors {{ $period === $p ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-200 text-gray-900 hover:border-gray-900' }}"
            >
                {{ __("analytics.period_{$p}") }}
            </button>
        @endforeach
    </div>

    @if($periodLocked)
        <x-premium-lock>
            <div class="rounded-xl bg-white p-8 text-center">
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
                                        :color="$expense['category_color'] ?? null"
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
                                            <span class="text-danger-600">↑{{ abs($changePercent) }}%</span>
                                        @elseif($direction === 'down')
                                            <span class="text-success-600">↓{{ abs($changePercent) }}%</span>
                                        @elseif($direction === 'new')
                                            <span class="text-small text-primary-600">new</span>
                                        @else
                                            <span class="text-gray-400">—</span>
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

                    @if($cpiPublishedMonth)
                        <p class="text-xs text-gray-500" data-testid="cpi-estimate-note">
                            {{ __('analytics.cpi_estimate_note', ['month' => $cpiPublishedMonth]) }}
                        </p>
                    @endif

                    @if($inflationLoss > 0)
                        <div class="rounded-md border border-warning-200 bg-warning-50 p-4">
                            <div class="flex items-start gap-3">
                                <p class="text-[15px] text-warning-800">
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
                        <p class="text-number-lg {{ $personalRate > $officialRate ? 'text-warning-600' : 'text-success-600' }}">
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

                @if($cpiBasisMonth)
                    <p class="text-xs text-gray-500 px-1" data-testid="cpi-basis-note">
                        {{ __('analytics.inflation_basis', ['month' => $cpiBasisMonth]) }}
                    </p>
                @endif

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
                                    <span class="text-base" aria-hidden="true">{{ $row['category_icon'] ?? '📦' }}</span>
                                    <span class="text-sm text-gray-900 truncate">{{ $row['category_name'][$locale] ?? '—' }}</span>
                                </div>
                                <div class="flex items-center gap-0">
                                    <span class="w-32 text-right text-caption text-gray-600">
                                        {{ number_format($row['share'] * 100, 1) }}%
                                    </span>
                                    <span class="w-32 text-right text-caption text-gray-600">
                                        {{ number_format($row['category_cpi'] * 100, 1) }}%
                                    </span>
                                    <span class="w-28 text-right text-caption font-medium {{ $row['contribution'] > 0 ? 'text-warning-600' : 'text-gray-500' }}">
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
