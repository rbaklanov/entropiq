@props([
    'amount',
    'currency' => '₽',
    'type' => null,
    'size' => 'md',
    'showSign' => false,
])

@php
    $rubles = abs($amount) / 100;
    $decimals = abs($amount) % 100 !== 0 ? 2 : 0;
    $formatted = number_format($rubles, $decimals, '.', ' ');

    $sign = '';
    if ($showSign) {
        $sign = $type === 'expense' ? '−' : '+';
    }

    $colorClass = match($type) {
        'income' => 'text-income',
        'expense' => 'text-expense',
        'inflation' => 'text-inflation',
        default => 'text-gray-900',
    };

    $sizeClass = match($size) {
        'lg' => 'text-number-lg',
        'md' => 'text-number-md',
        'sm' => 'text-base font-semibold',
        'xs' => 'text-caption font-medium',
        default => 'text-number-md',
    };
@endphp

<span {{ $attributes->merge(['class' => "{$colorClass} {$sizeClass}"]) }}>
    {{ $sign }}{{ $formatted }} {{ $currency }}
</span>
