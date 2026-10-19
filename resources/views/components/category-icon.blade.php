@props([
    'icon' => '📦',
    'color' => null,
    'size' => 'md',
    'inverse' => false,
])

@php
    // The stored category colour is no longer shown: icons are drawn in ink like the rest of the interface.
    [$box, $glyph] = match($size) {
        'lg' => ['h-12 w-12', 'h-6 w-6 text-xl'],
        'md' => ['h-10 w-10', 'h-5 w-5 text-lg'],
        'sm' => ['h-8 w-8', 'h-[18px] w-[18px] text-sm'],
        'xs' => ['h-6 w-6', 'h-4 w-4 text-xs'],
        default => ['h-10 w-10', 'h-5 w-5 text-lg'],
    };

    $tone = $inverse ? 'bg-white/15 text-white' : 'bg-gray-100 text-gray-900';
@endphp

<span
    {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center rounded-md {$box} {$tone}"]) }}
    aria-hidden="true"
>
    <x-category-glyph :icon="$icon" :class="$glyph" />
</span>
