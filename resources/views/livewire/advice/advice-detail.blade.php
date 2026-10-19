<div class="mx-auto max-w-3xl space-y-8">

    {{-- Back link --}}
    <a href="{{ route('advice.index') }}" class="-ml-1 inline-flex min-h-11 items-center gap-1.5 text-[15px] text-gray-500 hover:text-gray-900">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        {{ __('advice.back_to_list') }}
    </a>

    @if($locked)
        <x-premium-lock>
            <div class="rounded-md border border-gray-200 bg-white p-8">
                <p class="text-sm text-gray-500">{{ __('advice.card_label') }} · {{ $advice->generated_at->translatedFormat('j F Y, H:i') }}</p>
                <div class="mt-3 h-8 w-2/3 rounded-sm bg-gray-200"></div>
                <p class="mt-4 text-sm text-gray-500">{{ __('subscription.advice_limit') }}</p>
            </div>
        </x-premium-lock>
    @else

    {{-- Advice content --}}
    <article>
        <p class="text-sm text-gray-500">{{ __('advice.card_label') }} · {{ $advice->generated_at->translatedFormat('j F Y, H:i') }}</p>
        <h1 class="mt-2.5 font-serif text-[2rem] font-semibold leading-tight tracking-tight text-gray-900 sm:text-[2.75rem]">{{ $advice->title }}</h1>
        <p class="mt-6 font-serif text-lg leading-relaxed text-gray-900 sm:text-[1.3125rem]">{{ $advice->body }}</p>
    </article>

    {{-- Basis data --}}
    @php
        $basis = $advice->basis_data ?? [];
        $isSpike = ($basis['rule'] ?? null) === 'category_spike'
            && isset($basis['current_total'], $basis['avg_monthly'], $basis['growth_percent'])
            && $basis['current_total'] > 0;
    @endphp

    @if($isSpike)
        @php
            $current = (int) $basis['current_total'];
            $average = (int) $basis['avg_monthly'];
            $averageHeight = round($average / max($current, $average) * 100, 1);
            $currentHeight = round($current / max($current, $average) * 100, 1);
        @endphp
        <section class="rounded-md border border-gray-200 bg-white p-6 sm:p-8">
            <h2 class="text-[15px] font-semibold text-gray-900">{{ __('advice.spike_basis_title', ['percent' => $basis['growth_percent']]) }}</h2>
            <p class="mt-2 max-w-xl text-[15px] text-gray-500">{{ __('advice.spike_basis_intro', ['category' => $basis['category_name'] ?? '']) }}</p>

            <figure class="mt-9 max-w-md">
                <div role="img"
                     aria-label="{{ __('advice.spike_average') }}: {{ \App\Support\Money::amount($average) }} ₽. {{ __('advice.spike_this_month') }}: {{ \App\Support\Money::amount($current) }} ₽"
                     class="relative grid h-48 grid-cols-2 items-end gap-8 border-b border-gray-900">
                    <div class="ink-rise relative rounded-t-sm border border-b-0 border-gray-200 bg-gray-100" style="height: {{ $averageHeight }}%">
                        <span class="absolute inset-x-0 -top-6 text-center text-[13px] text-gray-500 tabular-nums">{{ \App\Support\Money::amount($average) }} ₽</span>
                    </div>
                    <div class="ink-rise relative rounded-t-sm bg-warning-600 [animation-delay:.12s]" style="height: {{ $currentHeight }}%">
                        <span class="absolute inset-x-0 -top-6 text-center text-[13px] font-semibold text-warning-600 tabular-nums">{{ \App\Support\Money::amount($current) }} ₽</span>
                    </div>
                    <div aria-hidden="true" class="ink-grow absolute inset-x-0 border-t-2 border-dashed border-gray-900 [animation-delay:.5s]" style="bottom: {{ $averageHeight }}%"></div>
                </div>
                <div aria-hidden="true" class="mt-1.5 grid grid-cols-2 gap-8 text-center text-[13px] text-gray-500">
                    <span>{{ __('advice.spike_average') }}</span>
                    <span class="font-semibold text-gray-900">{{ __('advice.spike_this_month') }}</span>
                </div>
            </figure>

            <p class="mt-6 text-lg">
                {{ __('advice.spike_summary', ['current' => \App\Support\Money::amount($current), 'average' => \App\Support\Money::amount($average)]) }}
                <span class="font-semibold text-warning-600">+{{ $basis['growth_percent'] }}%</span>
            </p>
        </section>
    @elseif($advice->basis_data && count($advice->basis_data) > 1)
        @php
            $hiddenKeys = ['rule', 'category_id', 'goal_id', 'transaction_id'];
            $labels = [
                'category_name' => 'Категория',
                'current_total' => 'Расход в этом месяце',
                'avg_monthly' => 'Средний расход за 3 мес.',
                'growth_percent' => 'Рост',
                'income' => 'Доход',
                'expense' => 'Расход',
                'overspend' => 'Перерасход',
                'overspend_percent' => 'Перерасход',
                'goal_name' => 'Цель',
                'expected_percent' => 'Ожидаемый прогресс',
                'actual_percent' => 'Фактический прогресс',
                'lag_percent' => 'Отставание',
                'amount' => 'Сумма транзакции',
                'multiplier' => 'Превышение средней',
                'date' => 'Дата',
                'discretionary_total' => 'Необязательные расходы',
                'discretionary_share_percent' => 'Доля от общих трат',
                'total_expense' => 'Общие расходы',
                'potential_saving' => 'Потенциальная экономия',
                'monthly_saving' => 'Экономия в месяц',
                'period_months' => 'Период анализа (мес.)',
            ];
            $moneyKeys = \App\Dto\AdvicePayload::MONEY_KEYS;
            $percentKeys = ['growth_percent', 'overspend_percent', 'expected_percent', 'actual_percent', 'lag_percent', 'discretionary_share_percent'];
            $multiplierKeys = ['multiplier'];
        @endphp

        <section class="rounded-md border border-gray-200 bg-white p-6 sm:p-8">
            <h2 class="text-[15px] font-semibold text-gray-900">{{ __('advice.basis') }}</h2>

            <dl class="mt-3">
                @foreach($advice->basis_data as $key => $value)
                    @if(!in_array($key, $hiddenKeys))
                        <div class="flex items-center justify-between gap-4 border-b border-gray-200 py-2.5 last:border-0">
                            <dt class="text-[15px] text-gray-500">{{ $labels[$key] ?? $key }}</dt>
                            <dd class="text-[15px] font-medium text-gray-900 tabular-nums">
                                @if(in_array($key, $moneyKeys))
                                    {{ \App\Support\Money::amount($value) }} ₽
                                @elseif(in_array($key, $percentKeys))
                                    {{ $value }}%
                                @elseif(in_array($key, $multiplierKeys))
                                    ×{{ $value }}
                                @else
                                    {{ $value }}
                                @endif
                            </dd>
                        </div>
                    @endif
                @endforeach
            </dl>
        </section>
    @endif

    {{-- Rating --}}
    <section class="flex flex-wrap items-center gap-4">
        <h2 class="text-base font-semibold text-gray-900">{{ __('advice.rate_helpful') }}</h2>

        <div class="flex gap-2.5">
            <button
                type="button"
                wire:click="rate(1)"
                aria-pressed="{{ $advice->rating === 1 ? 'true' : 'false' }}"
                aria-label="{{ __('advice.rate_helpful_yes') }}"
                @class([
                    'flex h-12 w-12 items-center justify-center rounded-full border transition-colors',
                    'border-gray-900 bg-gray-900 text-white' => $advice->rating === 1,
                    'border-gray-200 text-gray-900 hover:border-gray-900' => $advice->rating !== 1,
                ])
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 11v9H4v-9zM7 11l4-8a2 2 0 0 1 2 2v4h5.5a2 2 0 0 1 2 2.3l-1.2 7A2 2 0 0 1 17.3 20H7" /></svg>
            </button>
            <button
                type="button"
                wire:click="rate(-1)"
                aria-pressed="{{ $advice->rating === -1 ? 'true' : 'false' }}"
                aria-label="{{ __('advice.rate_helpful_no') }}"
                @class([
                    'flex h-12 w-12 items-center justify-center rounded-full border transition-colors',
                    'border-gray-900 bg-gray-900 text-white' => $advice->rating === -1,
                    'border-gray-200 text-gray-900 hover:border-gray-900' => $advice->rating !== -1,
                ])
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 13V4h3v9zM17 13l-4 8a2 2 0 0 1-2-2v-4H5.5a2 2 0 0 1-2-2.3l1.2-7A2 2 0 0 1 6.7 4H17" /></svg>
            </button>
        </div>
    </section>

    @endif

</div>
