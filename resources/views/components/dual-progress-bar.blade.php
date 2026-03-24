@props([
    'nominal',
    'real' => null,
    'height' => 'h-3',
])

@php
    $nominalPercent = min(100, max(0, $nominal));
    $realPercent = $real !== null ? min(100, max(0, $real)) : null;
@endphp

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    <div class="relative {{ $height }} overflow-hidden rounded-full bg-gray-100">
        @if($realPercent !== null)
            <div
                class="{{ $height }} absolute inset-y-0 left-0 rounded-full bg-orange-400/50 transition-all duration-700"
                style="width: {{ $realPercent }}%"
            ></div>
        @endif
        <div
            class="{{ $height }} absolute inset-y-0 left-0 rounded-full bg-primary-500 transition-all duration-700"
            style="width: {{ $nominalPercent }}%"
        ></div>
    </div>

    <div class="flex items-center gap-3 text-xs text-gray-500">
        <span class="flex items-center gap-1">
            <span class="inline-block h-2 w-2 rounded-full bg-primary-500"></span>
            {{ __('goals.nominal') }}
        </span>
        @if($realPercent !== null)
            <span class="flex items-center gap-1">
                <span class="inline-block h-2 w-2 rounded-full bg-orange-400"></span>
                {{ __('goals.real') }}
            </span>
        @endif
    </div>
</div>
