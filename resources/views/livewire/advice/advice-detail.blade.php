<div class="space-y-6">

    {{-- Back link --}}
    <a href="{{ route('advice.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        {{ __('advice.title') }}
    </a>

    {{-- Advice content --}}
    <div class="rounded-xl bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-warning-50 text-xl">✨</span>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">{{ $advice->title }}</h1>
                <p class="text-xs text-gray-400">{{ $advice->generated_at->translatedFormat('j F Y, H:i') }}</p>
            </div>
        </div>

        <div class="prose prose-sm max-w-none text-gray-700">
            <p>{{ $advice->body }}</p>
        </div>
    </div>

    {{-- Basis data --}}
    @if($advice->basis_data && count($advice->basis_data) > 1)
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
            $moneyKeys = ['current_total', 'avg_monthly', 'income', 'expense', 'overspend', 'amount', 'discretionary_total', 'total_expense', 'potential_saving', 'monthly_saving'];
            $percentKeys = ['growth_percent', 'overspend_percent', 'expected_percent', 'actual_percent', 'lag_percent', 'discretionary_share_percent'];
            $multiplierKeys = ['multiplier'];
        @endphp

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold text-gray-900">{{ __('advice.basis') }}</h2>

            <div class="space-y-2">
                @foreach($advice->basis_data as $key => $value)
                    @if(!in_array($key, $hiddenKeys))
                        <div class="flex items-center justify-between border-b border-gray-50 py-1.5 last:border-0">
                            <span class="text-sm text-gray-500">{{ $labels[$key] ?? $key }}</span>
                            <span class="text-sm font-medium text-gray-900">
                                @if(in_array($key, $moneyKeys))
                                    {{ number_format($value / 100, 0, ',', ' ') }} ₽
                                @elseif(in_array($key, $percentKeys))
                                    {{ $value }}%
                                @elseif(in_array($key, $multiplierKeys))
                                    ×{{ $value }}
                                @else
                                    {{ $value }}
                                @endif
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Rating --}}
    <div class="rounded-xl bg-white p-6 shadow-sm">
        <p class="mb-3 text-sm font-semibold text-gray-900">{{ __('advice.rate_helpful') }}</p>

        <div class="flex gap-3">
            <button
                wire:click="rate(1)"
                class="flex h-12 w-12 items-center justify-center rounded-full text-2xl transition
                    {{ $advice->rating === 1 ? 'bg-success-100 ring-2 ring-success-500' : 'bg-gray-100 hover:bg-gray-200' }}"
            >
                👍
            </button>
            <button
                wire:click="rate(-1)"
                class="flex h-12 w-12 items-center justify-center rounded-full text-2xl transition
                    {{ $advice->rating === -1 ? 'bg-danger-100 ring-2 ring-danger-500' : 'bg-gray-100 hover:bg-gray-200' }}"
            >
                👎
            </button>
        </div>
    </div>

</div>
