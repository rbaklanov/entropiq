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

    $scale = max($personalInflation, $nationalInflation, 0.0001) * 1.35;
    $personalWidth = round(max(0, $personalInflation) / $scale * 100, 1);
    $nationalLeft = round(max(0, $nationalInflation) / $scale * 100, 1);
@endphp

<section {{ $attributes->merge(['class' => 'rounded-md border border-gray-200 bg-white p-6']) }}>
    <div class="flex items-start justify-between gap-3">
        <h2 class="text-[15px] font-semibold text-gray-900">{{ __('dashboard.personal_inflation') }}</h2>
        <div x-data="{ show: false }" class="relative">
            <button
                @mouseenter="show = true"
                @mouseleave="show = false"
                @click="show = !show"
                class="-m-3 flex h-11 w-11 items-center justify-center rounded-full text-gray-500 transition-colors hover:text-gray-900"
                type="button"
                aria-label="{{ __('dashboard.personal_inflation_tooltip') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </button>
            <div
                x-show="show"
                x-transition.opacity
                x-cloak
                class="absolute right-0 top-8 z-10 w-60 rounded-md bg-gray-900 p-3 text-xs text-white"
            >
                {{ __('dashboard.personal_inflation_tooltip') }}
            </div>
        </div>
    </div>

    <p @class([
        'mt-2 font-serif text-[2.75rem] font-semibold leading-none tabular-nums',
        'text-warning-600' => $isAboveNational,
        'text-gray-900' => $isEqual,
        'text-success-600' => ! $isEqual && ! $isAboveNational,
    ])>{{ $personalFormatted }}%</p>

    <p class="mt-3 text-sm text-gray-500">
        {{ __('dashboard.national_avg') }} {{ $nationalFormatted }}%.
        @if($isEqual)
            {{ __('dashboard.inflation_equal_national') }}
        @elseif($isAboveNational)
            {{ __('dashboard.inflation_above_national') }}
        @else
            {{ __('dashboard.inflation_below_national') }}
        @endif
    </p>

    <div aria-hidden="true" class="relative mt-4 h-2 rounded-sm bg-gray-100">
        <div @class([
            'ink-grow absolute inset-y-0 left-0 rounded-sm',
            'bg-warning-600' => $isAboveNational,
            'bg-gray-900' => $isEqual,
            'bg-success-600' => ! $isEqual && ! $isAboveNational,
        ]) style="width: {{ $personalWidth }}%"></div>
        <div class="absolute -top-1.5 h-5 w-0.5 bg-gray-900" style="left: {{ $nationalLeft }}%"></div>
    </div>
</section>
