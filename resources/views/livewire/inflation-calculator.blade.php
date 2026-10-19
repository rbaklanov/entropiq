<div class="space-y-6">
    <div>
        <label for="calc-amount" class="block text-sm text-gray-500">
            {{ __('landing.calculator_amount') }}
        </label>
        <div class="mt-1.5 flex items-baseline gap-2 border-b-2 border-gray-900 pb-1.5">
            <input
                id="calc-amount"
                type="text"
                inputmode="numeric"
                wire:model.live.debounce.400ms="amountInput"
                class="min-w-0 flex-1 border-0 bg-transparent p-0 font-serif text-3xl font-semibold text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-4 focus-visible:ring-offset-white sm:text-4xl"
                placeholder="{{ __('landing.calculator_amount_placeholder') }}"
            />
            <span class="font-serif text-2xl text-gray-500 sm:text-[1.75rem]" aria-hidden="true">₽</span>
        </div>
    </div>

    <fieldset>
        <legend class="text-sm text-gray-500">{{ __('landing.calculator_when') }}</legend>
        <div class="mt-2.5 grid grid-cols-3 gap-2 sm:flex sm:flex-wrap">
            @foreach(['1y', '2y', '5y'] as $option)
                <button
                    type="button"
                    wire:click="setPeriod('{{ $option }}')"
                    aria-pressed="{{ $period === $option ? 'true' : 'false' }}"
                    @class([
                        'min-h-11 rounded-md border px-4 text-[15px] transition-colors',
                        'border-gray-900 bg-gray-900 text-white' => $period === $option,
                        'border-gray-200 text-gray-900 hover:border-gray-900' => $period !== $option,
                    ])
                >
                    {{ __("landing.calculator_{$option}") }}
                </button>
            @endforeach
        </div>
    </fieldset>

    @if($realValue !== null)
        @php
            $melted = $loss > 0 ? max(1, min(10, (int) round((100 - $percentage) / 10))) : 0;
        @endphp
        <div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <p class="text-sm text-gray-500">{{ __('landing.calculator_result_now') }}</p>
                    <p class="font-serif text-[1.875rem] font-semibold text-primary-600 tabular-nums">
                        {{ number_format($realValue / 100, 0, '', ' ') }} ₽
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('landing.calculator_result_lost') }}</p>
                    <p class="font-serif text-[1.875rem] font-semibold text-warning-600 tabular-nums">
                        −{{ number_format($loss / 100, 0, '', ' ') }} ₽
                    </p>
                </div>
            </div>

            <div aria-hidden="true" class="mt-5 grid grid-cols-10 gap-1.5">
                @for($i = 0; $i < 10; $i++)
                    <span @class([
                        'h-7 rounded-sm transition-colors duration-700',
                        'bg-primary-600' => $i < 10 - $melted,
                        'ink-melt bg-warning-600' => $i >= 10 - $melted,
                    ])></span>
                @endfor
            </div>

            <p class="mt-2 text-[13px] text-gray-500">
                {{ __('landing.calculator_bar_kept') }} {{ $percentage }}%
            </p>

            @if($cpiPublishedMonth)
                <p class="mt-3 text-xs text-gray-500" data-testid="cpi-estimate-note">
                    {{ __('analytics.cpi_estimate_note', ['month' => $cpiPublishedMonth]) }}
                </p>
            @endif
        </div>
    @endif
</div>
