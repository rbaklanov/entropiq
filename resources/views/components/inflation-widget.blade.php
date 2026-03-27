@props([
    'personalInflation',
    'nationalInflation',
])

@php
    $personalFormatted = number_format($personalInflation * 100, 1, ',', '');
    $nationalFormatted = number_format($nationalInflation * 100, 1, ',', '');
    $diff = abs($personalInflation - $nationalInflation);
    $isEqual = $diff < 0.001;
    $isAboveNational = !$isEqual && $personalInflation > $nationalInflation;
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl bg-white p-4 shadow-sm']) }}>
    <div class="flex items-start justify-between">
        <div class="flex items-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-warning-50 text-base">📊</span>
            <p class="text-sm font-semibold text-gray-900">{{ __('dashboard.personal_inflation') }}</p>
        </div>
        <div x-data="{ show: false }" class="relative">
            <button
                @mouseenter="show = true"
                @mouseleave="show = false"
                @click="show = !show"
                class="flex h-5 w-5 items-center justify-center rounded-full text-gray-400 transition hover:text-gray-600"
                type="button"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </button>
            <div
                x-show="show"
                x-transition.opacity
                class="absolute right-0 top-6 z-10 w-56 rounded-lg bg-gray-900 p-3 text-xs text-white shadow-lg"
            >
                {{ __('dashboard.personal_inflation_tooltip') }}
            </div>
        </div>
    </div>

    <div class="mt-3 flex items-baseline gap-1.5">
        <span class="text-number-md {{ $isAboveNational ? 'text-warning-600' : ($isEqual ? 'text-gray-700' : 'text-success-600') }}">
            {{ $personalFormatted }}%
        </span>
        <span class="text-sm text-gray-400">
            ({{ __('dashboard.national_avg') }}: {{ $nationalFormatted }}%)
        </span>
    </div>

    @if($isEqual)
        <p class="mt-2 text-xs text-gray-500">
            {{ __('dashboard.inflation_equal_national') }}
        </p>
    @elseif($isAboveNational)
        <p class="mt-2 text-xs text-warning-600">
            {{ __('dashboard.inflation_above_national') }}
        </p>
    @else
        <p class="mt-2 text-xs text-success-600">
            {{ __('dashboard.inflation_below_national') }}
        </p>
    @endif
</div>
