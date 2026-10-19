@props(['type' => 'other'])

@php
    $paths = [
        'safety_net' => 'M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6z',
        'large_purchase' => 'M3 4h2l2.4 11h10.2L20 8H6.2M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2zM17 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2z',
        'travel' => 'M3 14l18-7-7 18-2.5-8.5z',
        'car' => 'M2 16.5V13l2.5-5h10l4.5 5h2a1 1 0 0 1 1 1v2.5h-2.5M9 16.5h6M2 16.5h3M5 16.5a2 2 0 1 0 4 0 2 2 0 1 0-4 0M15 16.5a2 2 0 1 0 4 0 2 2 0 1 0-4 0M8 8v5h10',
        'apartment' => 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z',
        'education' => 'M2 9l10-5 10 5-10 5zM6 11v5c3 2 9 2 12 0v-5M22 9v6',
        'other' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 17a5 5 0 1 0 0-10 5 5 0 0 0 0 10zM12 13a1 1 0 1 0 0-2 1 1 0 0 0 0 2z',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="{{ $paths[$type] ?? $paths['other'] }}" />
</svg>
