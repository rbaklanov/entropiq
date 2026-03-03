@props([
    'icon' => '📦',
    'color' => '#6366F1',
    'size' => 'md',
])

@php
    $dimensions = match($size) {
        'lg' => 'h-12 w-12 text-xl',
        'md' => 'h-10 w-10 text-lg',
        'sm' => 'h-8 w-8 text-sm',
        'xs' => 'h-6 w-6 text-xs',
        default => 'h-10 w-10 text-lg',
    };
@endphp

<span
    {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-xl {$dimensions}"]) }}
    style="background-color: {{ $color }}1A;"
>
    {{ $icon }}
</span>
