@props(['goal', 'monthlyPayment' => 0, 'completionDate' => null, 'realProgress' => null])

@php
    $progress = $goal->progressPercent();
    $currentRubles = $goal->current_amount / 100;
    $targetRubles = $goal->target_amount / 100;
    $monthlyRubles = $monthlyPayment / 100;

    $statusColor = match($goal->status->value) {
        'achieved' => 'border-success-600 bg-white',
        'cancelled' => 'border-gray-200 bg-gray-50 opacity-60',
        default => 'border-gray-200 bg-white hover:border-gray-300',
    };
@endphp

<a
    href="{{ route('goals.show', $goal) }}"
    {{ $attributes->merge(['class' => "block rounded-md border {$statusColor} p-5 transition-colors"]) }}
>
    <div class="flex items-start justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-gray-100 text-xl text-gray-900">
                @if($goal->icon)
                    <span aria-hidden="true">{{ $goal->icon }}</span>
                @else
                    <x-goal-type-icon :type="$goal->type->value" />
                @endif
            </span>
            <div class="min-w-0">
                <h3 class="truncate text-[15px] font-semibold text-gray-900">{{ $goal->name }}</h3>
                <p class="text-xs text-gray-500">{{ __("goals.type_{$goal->type->value}") }}</p>
            </div>
        </div>

        @if($goal->isAchieved())
            <span class="shrink-0 text-xs font-semibold text-success-600">
                ✓ {{ __('goals.status_achieved') }}
            </span>
        @endif
    </div>

    <div class="mt-4">
        <div class="mb-2 flex items-baseline justify-between gap-2">
            <span class="font-serif text-xl font-semibold text-gray-900 tabular-nums">
                {{ number_format($currentRubles, 0, '.', ' ') }} ₽
            </span>
            <span class="text-sm text-gray-500 tabular-nums">
                / {{ number_format($targetRubles, 0, '.', ' ') }} ₽
            </span>
        </div>

        <x-dual-progress-bar :nominal="$progress" :real="$realProgress" height="h-2" />
    </div>

    <div class="mt-3 flex items-center justify-between gap-2 text-xs text-gray-500">
        <span>{{ $progress }}%</span>

        @if($monthlyPayment > 0 && !$goal->isAchieved())
            <span>{{ number_format($monthlyRubles, 0, '.', ' ') }} ₽/мес</span>
        @endif

        @if($completionDate && !$goal->isAchieved())
            <span>{{ \Illuminate\Support\Carbon::parse($completionDate)->translatedFormat('M Y') }}</span>
        @endif
    </div>
</a>
