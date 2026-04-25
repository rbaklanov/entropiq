<div class="space-y-6">
    <div>
        <label for="calc-amount" class="mb-2 block text-left text-sm font-medium text-gray-700">
            {{ __('landing.calculator_amount') }}
        </label>
        <div class="relative">
            <input
                id="calc-amount"
                type="text"
                inputmode="numeric"
                wire:model.live.debounce.400ms="amountInput"
                class="w-full rounded-xl border-2 border-gray-200 bg-white px-4 py-3.5 pr-10 text-lg font-semibold text-gray-900 transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                placeholder="{{ __('landing.calculator_amount_placeholder') }}"
            />
            <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-lg text-gray-400">₽</span>
        </div>
    </div>

    <div>
        <label class="mb-2 block text-left text-sm font-medium text-gray-700">
            {{ __('landing.calculator_when') }}
        </label>
        <div class="flex gap-3">
            <button
                wire:click="setPeriod('1y')"
                @class([
                    'flex-1 rounded-xl border-2 px-4 py-3 text-sm font-medium transition',
                    'border-primary-500 bg-primary-50 text-primary-700' => $period === '1y',
                    'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50' => $period !== '1y',
                ])
            >
                {{ __('landing.calculator_1y') }}
            </button>
            <button
                wire:click="setPeriod('2y')"
                @class([
                    'flex-1 rounded-xl border-2 px-4 py-3 text-sm font-medium transition',
                    'border-primary-500 bg-primary-50 text-primary-700' => $period === '2y',
                    'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50' => $period !== '2y',
                ])
            >
                {{ __('landing.calculator_2y') }}
            </button>
            <button
                wire:click="setPeriod('5y')"
                @class([
                    'flex-1 rounded-xl border-2 px-4 py-3 text-sm font-medium transition',
                    'border-primary-500 bg-primary-50 text-primary-700' => $period === '5y',
                    'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50' => $period !== '5y',
                ])
            >
                {{ __('landing.calculator_5y') }}
            </button>
        </div>
    </div>

    @if($realValue !== null)
        <div
            class="rounded-xl bg-gradient-to-r from-warning-50 to-danger-50 p-6 transition-all duration-500"
            wire:transition
        >
            <div class="flex items-center justify-between">
                <div class="text-left">
                    <p class="text-sm text-gray-500">{{ __('landing.calculator_result_now') }}</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ number_format($realValue / 100, 0, '', ' ') }} ₽
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">{{ __('landing.calculator_result_lost') }}</p>
                    <p class="text-2xl font-bold text-danger-600">
                        −{{ number_format($loss / 100, 0, '', ' ') }} ₽
                    </p>
                </div>
            </div>

            <div class="mt-4 h-3 overflow-hidden rounded-full bg-gray-200">
                <div
                    class="h-full rounded-full bg-gradient-to-r from-success-500 to-warning-500 transition-all duration-700 ease-out"
                    style="width: {{ $percentage }}%"
                ></div>
            </div>

            <div class="mt-2 flex justify-between text-xs text-gray-400">
                <span>{{ __('landing.calculator_bar_kept') }}</span>
                <span>{{ $percentage }}%</span>
            </div>
        </div>
    @endif
</div>
