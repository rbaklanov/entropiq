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
    <div class="space-y-1">
        <div class="relative {{ $height }} overflow-hidden rounded-sm bg-gray-100">
            <div
                class="{{ $height }} ink-grow absolute inset-y-0 left-0 rounded-sm bg-primary-600"
                style="width: {{ $nominalPercent }}%"
            ></div>
        </div>

        @if($realPercent !== null)
            <div class="relative {{ $height }} overflow-hidden rounded-sm bg-gray-100">
                <div
                    class="{{ $height }} ink-grow absolute inset-y-0 left-0 rounded-sm bg-warning-600 [animation-delay:.15s]"
                    style="width: {{ $realPercent }}%"
                ></div>
            </div>
        @endif
    </div>

    <div class="flex items-center gap-3 text-xs text-gray-500">
        <span class="flex items-center gap-1">
            <span class="inline-block h-2 w-2 rounded-full bg-primary-600"></span>
            {{ __('goals.nominal') }}
        </span>
        @if($realPercent !== null)
            <span class="flex items-center gap-1">
                <span class="inline-block h-2 w-2 rounded-full bg-warning-600"></span>
                {{ __('goals.real') }}
            </span>
        @endif
    </div>
</div>
