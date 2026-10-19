@props([
    'nominalBalance',
    'realBalance',
    'inflationLoss',
])

@php
    $formatAmount = function (int $amount): string {
        $rubles = abs($amount) / 100;
        $decimals = abs($amount) % 100 !== 0 ? 2 : 0;
        $formatted = number_format($rubles, $decimals, '.', ' ');
        $sign = $amount < 0 ? '−' : ($amount > 0 ? '+' : '');

        return "{$sign}{$formatted}";
    };

    $hasLoss = $inflationLoss != 0;
@endphp

<section
    {{ $attributes->merge(['class' => 'rounded-md border border-gray-200 bg-white p-6 sm:p-7']) }}
    @if($hasLoss)
        x-data="moneyToggle({ nominal: {{ (int) $nominalBalance }}, real: {{ (int) $realBalance }} })"
    @endif
>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <p class="text-sm text-gray-500">
            @if($hasLoss)
                <span x-show="! showReal">{{ __('dashboard.balance') }}</span>
                <span x-show="showReal" x-cloak>{{ __('dashboard.real_balance') }}</span>
            @else
                {{ __('dashboard.balance') }}
            @endif
        </p>

        @if($hasLoss)
            <div role="group" aria-label="{{ __('dashboard.amounts_toggle') }}" class="inline-flex rounded-md border border-gray-200 bg-paper p-0.5">
                <button type="button" x-on:click="show(false)" :aria-pressed="(! showReal).toString()" aria-pressed="true"
                        class="min-h-10 rounded px-4 text-sm transition-colors"
                        :class="showReal ? 'text-gray-500 hover:text-gray-900' : 'bg-gray-900 text-white'">
                    {{ __('dashboard.show_nominal') }}
                </button>
                <button type="button" x-on:click="show(true)" :aria-pressed="showReal.toString()" aria-pressed="false"
                        class="min-h-10 rounded px-4 text-sm text-gray-500 transition-colors"
                        :class="showReal ? 'bg-gray-900 text-white' : 'text-gray-500 hover:text-gray-900'">
                    {{ __('dashboard.show_real') }}
                </button>
            </div>
        @endif
    </div>

    <p class="mt-1 font-serif text-5xl font-semibold leading-tight tabular-nums transition-colors duration-500 sm:text-[3.5rem]"
       @if($hasLoss) :class="showReal && 'text-primary-600'" @endif>
        <span @if($hasLoss) x-text="display" @endif>{{ $formatAmount($nominalBalance) }}</span> ₽
    </p>

    @if($hasLoss)
        <p class="mt-3 text-[15px]">
            <span class="relative inline-block font-semibold text-warning-600">
                {{ $formatAmount(-abs($inflationLoss)) }} ₽
                <svg aria-hidden="true" viewBox="0 0 200 56" preserveAspectRatio="none" class="pointer-events-none absolute -inset-x-3.5 -inset-y-2.5 h-[calc(100%+20px)] w-[calc(100%+28px)] overflow-visible">
                    <path pathLength="1" class="pen-draw [animation-delay:1.2s]" d="M14 30 C 10 8, 186 2, 194 26 S 124 54, 44 48 S 4 24, 34 9" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                </svg>
            </span>
            <span class="text-gray-500">{{ __('dashboard.inflation_loss') }}</span>
        </p>
        <p class="mt-2 text-sm text-gray-500" x-show="! showReal">
            {{ __('dashboard.real_balance') }}: <span class="font-medium text-primary-600 tabular-nums">{{ $formatAmount($realBalance) }} ₽</span>
        </p>
    @else
        <p class="mt-2 text-sm text-gray-500">{{ __('dashboard.balance_period') }}</p>
    @endif
</section>
