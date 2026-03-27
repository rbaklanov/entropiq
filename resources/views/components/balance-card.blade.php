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
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl bg-gradient-to-br from-primary-600 to-primary-700 p-6 text-white shadow-lg']) }}>
    <p class="text-sm font-medium text-primary-100">{{ __('dashboard.balance') }}</p>

    <p class="mt-2 text-3xl font-bold tracking-tight">
        {{ $formatAmount($nominalBalance) }} ₽
    </p>

    @if($inflationLoss > 0)
        <div class="mt-3 flex items-center gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-warning-300" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm font-medium text-warning-200">
                −{{ $formatAmount($inflationLoss) }} ₽ {{ __('dashboard.inflation_loss') }}
            </span>
        </div>

        <div class="mt-2">
            <p class="text-xs text-primary-200">{{ __('dashboard.real_balance') }}</p>
            <p class="text-lg font-semibold text-primary-100">
                {{ $formatAmount($realBalance) }} ₽
            </p>
        </div>
    @else
        <p class="mt-1 text-sm text-primary-200">{{ __('dashboard.balance_period') }}</p>
    @endif
</div>
