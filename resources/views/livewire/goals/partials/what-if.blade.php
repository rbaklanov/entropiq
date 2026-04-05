<div class="rounded-2xl bg-white p-5 shadow-sm space-y-4">
    <h2 class="text-base font-semibold text-gray-900">{{ __('goals.what_if') }}</h2>

    <div>
        <p class="text-sm text-gray-600">
            {{ __('goals.what_if_extra') }}
            <span class="font-bold" style="color: #6366F1">
                {{ number_format($whatIfAmount / 100, 0, '.', ' ') }} ₽
            </span>
        </p>
        <input
            type="range"
            wire:model.live.debounce.300ms="whatIfAmount"
            min="100000"
            max="5000000"
            step="50000"
            class="mt-3 w-full"
            style="accent-color: #6366F1; outline: none;"
        />
        <div class="flex justify-between text-xs text-gray-400">
            <span>1 000 ₽</span>
            <span>50 000 ₽</span>
        </div>
    </div>

    @if($whatIf['days_saved'] > 0)
        <div class="rounded-xl p-3" style="border: 1px solid #C7D2FE; background-color: #EEF2FF">
            @php
                $days = $whatIf['days_saved'];
                if ($days >= 30) {
                    $savedText = __('goals.what_if_result_months', ['months' => intdiv($days, 30)]);
                } else {
                    $savedText = __('goals.what_if_result_days', ['days' => $days]);
                }
            @endphp
            <p class="text-sm text-gray-700">{{ $savedText }}</p>
            @if($whatIf['new_completion'])
                <p class="mt-1 text-xs text-gray-500">
                    {{ __('goals.what_if_new_date') }}:
                    {{ \Illuminate\Support\Carbon::parse($whatIf['new_completion'])->translatedFormat('d M Y') }}
                </p>
            @endif
        </div>
    @endif
</div>
