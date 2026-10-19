@props(['dynamics' => []])

@php
    // Cumulative nominal and real savings since January, drawn with the same pen as the landing chart.
    $points = array_values($dynamics);
    $count = count($points);

    $width = 600;
    $height = 150;
    $top = 12;
    $bottom = 138;

    $values = array_merge(array_column($points, 'cumulative_balance'), array_column($points, 'real_cumulative_balance'), [0]);
    $max = max($values);
    $min = min($values);
    $range = max(1, $max - $min);

    $x = fn (int $i) => $count > 1 ? round($i / ($count - 1) * $width, 1) : 0;
    $y = fn (int $v) => round($bottom - ($v - $min) / $range * ($bottom - $top), 1);

    $nominal = [];
    $real = [];
    foreach ($points as $i => $point) {
        $nominal[] = [$x($i), $y((int) $point['cumulative_balance'])];
        $real[] = [$x($i), $y((int) $point['real_cumulative_balance'])];
    }

    $path = fn (array $line) => 'M'.collect($line)->map(fn ($p) => "{$p[0]} {$p[1]}")->implode(' L');
    $band = collect($nominal)->concat(array_reverse($real))->map(fn ($p) => "{$p[0]},{$p[1]}")->implode(' ');

    $last = $count > 0 ? end($points) : null;
    $toRubles = fn (int|float $kopecks) => (int) round($kopecks / 100) * 100;
    $nominalTotal = $last ? $toRubles($last['cumulative_balance']) : 0;
    $realTotal = $last ? $toRubles($last['real_cumulative_balance']) : 0;
    $loss = max(0, $nominalTotal - $realTotal);

    $firstMonth = $count > 0 ? \Illuminate\Support\Carbon::parse($points[0]['date'])->translatedFormat('M') : '';
    $lastMonth = $last ? \Illuminate\Support\Carbon::parse($last['date'])->translatedFormat('M') : '';
@endphp

@if($count > 1)
    <section {{ $attributes->merge(['class' => 'rounded-md border border-gray-200 bg-white p-6']) }}>
        <div class="flex flex-wrap items-baseline justify-between gap-3">
            <h2 class="text-[15px] font-semibold text-gray-900">{{ __('dashboard.savings_trend') }}</h2>
            <a href="{{ route('analytics', ['tab' => 'balance', 'period' => 'year']) }}" class="text-sm text-primary-600 hover:text-primary-700">
                {{ __('dashboard.savings_trend_more') }}
            </a>
        </div>

        <dl class="mt-3 flex flex-wrap gap-x-8 gap-y-2">
            <div>
                <dt class="text-[13px] text-gray-500">{{ __('dashboard.savings_trend_nominal') }}</dt>
                <dd class="font-serif text-xl font-semibold text-gray-900 tabular-nums">{{ $nominalTotal < 0 ? '−' : '' }}{{ \App\Support\Money::amount(abs($nominalTotal)) }} ₽</dd>
            </div>
            <div>
                <dt class="text-[13px] text-gray-500">{{ __('dashboard.savings_trend_real') }}</dt>
                <dd class="font-serif text-xl font-semibold text-primary-600 tabular-nums">{{ \App\Support\Money::amount($realTotal) }} ₽</dd>
            </div>
            @if($loss > 0)
                <div>
                    <dt class="text-[13px] text-gray-500">{{ __('dashboard.savings_trend_loss') }}</dt>
                    <dd class="font-serif text-xl font-semibold text-warning-600 tabular-nums">−{{ \App\Support\Money::amount($loss) }} ₽</dd>
                </div>
            @endif
        </dl>

        <svg role="img" aria-label="{{ __('dashboard.savings_trend_chart') }}" viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" class="mt-4 block h-36 w-full overflow-visible">
            <line x1="0" y1="{{ $y(0) }}" x2="{{ $width }}" y2="{{ $y(0) }}" class="stroke-gray-200" stroke-width="1" vector-effect="non-scaling-stroke" />
            <polygon points="{{ $band }}" class="ink-fade fill-warning-600/15 [animation-delay:1.2s]" />
            <path pathLength="1" d="{{ $path($nominal) }}" class="pen-draw stroke-gray-900 [animation-duration:1.6s]" fill="none" stroke-width="2.5" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
            <path pathLength="1" d="{{ $path($real) }}" class="pen-draw stroke-primary-600 [animation-delay:.3s] [animation-duration:1.6s]" fill="none" stroke-width="2.5" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
        </svg>
        <div aria-hidden="true" class="mt-1.5 flex justify-between text-xs text-gray-500">
            <span>{{ $firstMonth }}</span>
            <span>{{ $lastMonth }}</span>
        </div>
    </section>
@endif
