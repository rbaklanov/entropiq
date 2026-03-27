@props(['goal', 'monthlyPayment' => 0, 'completionDate' => null, 'realProgress' => null])

@php
    $progress = $goal->progressPercent();
    $currentRubles = $goal->current_amount / 100;
    $targetRubles = $goal->target_amount / 100;
    $monthlyRubles = $monthlyPayment / 100;

    $statusColor = match($goal->status->value) {
        'achieved' => 'border-success-500 bg-success-50',
        'cancelled' => 'border-gray-300 bg-gray-50 opacity-60',
        default => 'border-gray-200 bg-white',
    };

    $typeIcons = [
        'safety_net' => '🛡️',
        'large_purchase' => '🛒',
        'travel' => '✈️',
        'car' => '🚗',
        'apartment' => '🏠',
        'education' => '🎓',
        'other' => '🎯',
    ];

    $icon = $goal->icon ?? ($typeIcons[$goal->type->value] ?? '🎯');
@endphp

<a
    href="{{ route('goals.show', $goal) }}"
    {{ $attributes->merge(['class' => "block rounded-2xl border {$statusColor} p-5 shadow-sm transition hover:shadow-md"]) }}
>
    <div class="flex items-start justify-between">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-xl">
                {{ $icon }}
            </span>
            <div>
                <h3 class="text-sm font-semibold text-gray-900">{{ $goal->name }}</h3>
                <p class="text-xs text-gray-500">{{ __("goals.type_{$goal->type->value}") }}</p>
            </div>
        </div>

        @if($goal->isAchieved())
            <span class="inline-flex items-center rounded-full bg-success-100 px-2 py-0.5 text-xs font-medium text-success-700">
                ✓ {{ __('goals.status_achieved') }}
            </span>
        @endif
    </div>

    <div class="mt-4">
        <div class="mb-2 flex items-baseline justify-between">
            <span class="text-lg font-bold text-gray-900">
                {{ number_format($currentRubles, 0, '.', ' ') }} ₽
            </span>
            <span class="text-sm text-gray-500">
                / {{ number_format($targetRubles, 0, '.', ' ') }} ₽
            </span>
        </div>

        <x-dual-progress-bar :nominal="$progress" :real="$realProgress" height="h-2.5" />
    </div>

    <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
        <span>{{ $progress }}%</span>

        @if($monthlyPayment > 0 && !$goal->isAchieved())
            <span>{{ number_format($monthlyRubles, 0, '.', ' ') }} ₽/мес</span>
        @endif

        @if($completionDate && !$goal->isAchieved())
            <span>{{ \Illuminate\Support\Carbon::parse($completionDate)->translatedFormat('M Y') }}</span>
        @endif
    </div>
</a>
