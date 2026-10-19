<div class="space-y-3">
    <h2 class="text-[15px] font-semibold text-gray-900">{{ __('goals.scenarios') }}</h2>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @php
            $scenarioConfig = [
                'optimistic' => ['label' => __('goals.scenario_optimistic'), 'class' => 'text-success-600'],
                'baseline' => ['label' => __('goals.scenario_base'), 'class' => 'text-primary-600'],
                'pessimistic' => ['label' => __('goals.scenario_pessimistic'), 'class' => 'text-warning-600'],
            ];
        @endphp

        @foreach($scenarios as $key => $scenario)
            @php $cfg = $scenarioConfig[$key]; @endphp
            <div @class([
                'rounded-md border bg-white p-4',
                'border-gray-900' => $key === 'baseline',
                'border-gray-200' => $key !== 'baseline',
            ])>
                <p class="text-sm font-semibold {{ $cfg['class'] }}">{{ $cfg['label'] }}</p>
                <p class="mt-2 font-serif text-xl font-semibold text-gray-900 tabular-nums">
                    {{ number_format($scenario['monthly_payment'] / 100, 0, '.', ' ') }} ₽
                    <span class="font-sans text-xs font-normal text-gray-500">/ {{ __('goals.per_month') }}</span>
                </p>
                <div class="mt-2 flex justify-between text-xs text-gray-500">
                    <span>
                        @if($scenario['completion_date'])
                            {{ \Illuminate\Support\Carbon::parse($scenario['completion_date'])->translatedFormat('M Y') }}
                        @endif
                    </span>
                    <span>{{ number_format($scenario['inflation'] * 100, 1) }}%</span>
                </div>
            </div>
        @endforeach
    </div>
</div>
