@props([
    'icon' => '📦',
    'color' => null,
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

    $tint = $color ?: 'var(--color-gray-500)';
@endphp

<span
    {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center rounded-md {$dimensions}"]) }}
    style="background-color: color-mix(in srgb, {{ $tint }} 14%, transparent);"
    aria-hidden="true"
>
    {{ $icon }}
</span>
