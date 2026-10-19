<div class="space-y-4 rounded-md border border-gray-200 bg-white p-6">
    <h2 class="text-[15px] font-semibold text-gray-900">{{ __('goals.what_if') }}</h2>

    <div>
        <label for="what-if-amount" class="text-sm text-gray-500">
            {{ __('goals.what_if_extra') }}
            <span class="font-serif text-lg font-semibold text-primary-600 tabular-nums">
                {{ number_format($whatIfAmount / 100, 0, '.', ' ') }} ₽
            </span>
        </label>
        <input
            id="what-if-amount"
            type="range"
            wire:model.live.debounce.300ms="whatIfAmount"
            min="100000"
            max="5000000"
            step="50000"
            class="mt-3 w-full accent-primary-600"
        />
        <div class="flex justify-between text-xs text-gray-500">
            <span>1 000 ₽</span>
            <span>50 000 ₽</span>
        </div>
    </div>

    @if($whatIf['days_saved'] > 0)
        <div class="rounded-md border border-primary-200 bg-primary-50 p-4">
            @php
                $days = $whatIf['days_saved'];
                if ($days >= 30) {
                    $savedText = __('goals.what_if_result_months', ['months' => intdiv($days, 30)]);
                } else {
                    $savedText = __('goals.what_if_result_days', ['days' => $days]);
                }
            @endphp
            <p class="text-[15px] text-gray-900">{{ $savedText }}</p>
            @if($whatIf['new_completion'])
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('goals.what_if_new_date') }}:
                    {{ \Illuminate\Support\Carbon::parse($whatIf['new_completion'])->translatedFormat('d M Y') }}
                </p>
            @endif
        </div>
    @endif
</div>
