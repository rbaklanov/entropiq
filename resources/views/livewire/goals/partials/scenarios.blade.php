<div class="space-y-3">
    <h2 class="text-base font-semibold text-gray-900">{{ __('goals.scenarios') }}</h2>
    <div class="grid grid-cols-3 gap-3">
        @php
            $scenarioConfig = [
                'optimistic' => ['label' => __('goals.scenario_optimistic'), 'border' => '#BBF7D0', 'bg' => '#F0FDF4', 'text' => '#15803D'],
                'baseline' => ['label' => __('goals.scenario_base'), 'border' => '#BFDBFE', 'bg' => '#EFF6FF', 'text' => '#1D4ED8'],
                'pessimistic' => ['label' => __('goals.scenario_pessimistic'), 'border' => '#FED7AA', 'bg' => '#FFF7ED', 'text' => '#C2410C'],
            ];
        @endphp

        @foreach($scenarios as $key => $scenario)
            @php $cfg = $scenarioConfig[$key]; @endphp
            <div class="rounded-xl p-3 text-center" style="border: 1px solid {{ $cfg['border'] }}; background-color: {{ $cfg['bg'] }}">
                <p class="text-xs font-medium" style="color: {{ $cfg['text'] }}">{{ $cfg['label'] }}</p>
                <p class="mt-2 text-sm font-bold text-gray-900">
                    {{ number_format($scenario['monthly_payment'] / 100, 0, '.', ' ') }} ₽
                </p>
                <p class="text-xs text-gray-500">/ {{ __('goals.per_month') }}</p>
                @if($scenario['completion_date'])
                    <p class="mt-1 text-xs text-gray-500">
                        {{ \Illuminate\Support\Carbon::parse($scenario['completion_date'])->translatedFormat('M Y') }}
                    </p>
                @endif
                <p class="mt-1 text-xs text-gray-400">
                    {{ number_format($scenario['inflation'] * 100, 1) }}%
                </p>
            </div>
        @endforeach
    </div>
</div>
