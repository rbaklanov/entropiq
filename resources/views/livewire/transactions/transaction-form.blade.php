<div class="mx-auto max-w-lg space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('transactions.index') }}" class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
        </a>
        <h1 class="text-h2">
            {{ $isEditing ? __('transactions.edit') : __('transactions.add') }}
        </h1>
    </div>

    <form wire:submit="save" class="space-y-6">

        {{-- Type tabs --}}
        <div class="flex gap-2">
            <button
                type="button"
                wire:click="setType('expense')"
                @class([
                    'flex-1 rounded-xl py-3 text-center text-sm font-semibold transition-all duration-200',
                    'bg-danger-500 text-white shadow-md' => $type === 'expense',
                    'bg-white text-gray-600 hover:bg-gray-50' => $type !== 'expense',
                ])
            >
                {{ __('transactions.expense') }}
            </button>
            <button
                type="button"
                wire:click="setType('income')"
                @class([
                    'flex-1 rounded-xl py-3 text-center text-sm font-semibold transition-all duration-200',
                    'bg-success-500 text-white shadow-md' => $type === 'income',
                    'bg-white text-gray-600 hover:bg-gray-50' => $type !== 'income',
                ])
            >
                {{ __('transactions.income') }}
            </button>
        </div>

        {{-- Amount --}}
        <div>
            <div class="relative">
                <input
                    type="text"
                    inputmode="decimal"
                    wire:model.live="amountDisplay"
                    placeholder="0"
                    class="w-full rounded-xl border-2 border-gray-200 bg-gray-50 py-5 text-center text-number-lg outline-none transition-all duration-200 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 focus:shadow-md {{ $type === 'income' ? 'text-success-500' : 'text-danger-500' }}"
                />
                <span class="pointer-events-none absolute right-6 top-1/2 -translate-y-1/2 text-2xl font-semibold text-gray-400">₽</span>
            </div>
            @error('amount')
                <p class="mt-1 text-center text-sm text-danger-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Category grid --}}
        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">
                {{ __('transactions.category') }}
            </label>
            <x-category-grid :categories="$categories" :selected="$categoryId" />
            @error('categoryId')
                <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Date --}}
        <div
            wire:ignore
            x-data="{
                fp: null,
                formatDisplay(iso) {
                    if (!iso) return '';
                    const [y, m, d] = iso.split('-');
                    return d + '-' + m + '-' + y;
                },
                init() {
                    const initial = $wire.get('date');
                    this.$refs.dateDisplay.value = this.formatDisplay(initial);

                    this.fp = flatpickr(this.$refs.dateDisplay, {
                        dateFormat: 'd-m-Y',
                        defaultDate: initial,
                        locale: 'ru',
                        disableMobile: true,
                        parseDate(datestr, format) {
                            if (/^\d{4}-\d{2}-\d{2}$/.test(datestr)) {
                                return new Date(datestr + 'T00:00:00');
                            }
                            const [d, m, y] = datestr.split('-');
                            return new Date(y + '-' + m + '-' + d + 'T00:00:00');
                        },
                        onChange: (selectedDates) => {
                            if (selectedDates.length) {
                                const d = selectedDates[0];
                                const iso = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                                $wire.set('date', iso);
                            }
                        },
                    });
                },
                destroy() {
                    this.fp?.destroy();
                },
            }"
        >
            <label class="mb-1 block text-sm font-medium text-gray-700">
                {{ __('transactions.date') }}
            </label>
            <div class="relative">
                <input
                    type="text"
                    x-ref="dateDisplay"
                    readonly
                    class="w-full cursor-pointer rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 pl-10 text-sm text-gray-700 outline-none transition-all duration-200 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 focus:shadow-md"
                />
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-3 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                </svg>
            </div>
            @error('date')
                <p class="mt-1 text-sm text-danger-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Comment (toggle) --}}
        <div>
            @if(!$showComment)
                <button
                    type="button"
                    wire:click="$set('showComment', true)"
                    class="flex items-center gap-1.5 text-sm text-primary-600 transition hover:text-primary-700"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    {{ __('transactions.add_comment') }}
                </button>
            @else
                <div class="relative">
                    <textarea
                        wire:model="comment"
                        rows="2"
                        placeholder="{{ __('transactions.comment') }}"
                        class="w-full rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 pr-10 text-sm text-gray-700 outline-none transition-all duration-200 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 focus:shadow-md"
                    ></textarea>
                    <button
                        type="button"
                        wire:click="$set('showComment', false); $set('comment', '')"
                        class="absolute right-3 top-3 rounded-full p-0.5 text-gray-400 transition hover:bg-gray-200 hover:text-gray-600"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            @endif
        </div>

        {{-- Recurring (toggle) --}}
        @if(!$isEditing)
            <div>
                @if(!$showRecurring)
                    <button
                        type="button"
                        wire:click="$set('showRecurring', true)"
                        class="flex items-center gap-1.5 text-sm text-primary-600 transition hover:text-primary-700"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                        </svg>
                        {{ __('transactions.make_recurring') }}
                    </button>
                @else
                    <div class="space-y-3 rounded-xl bg-white p-4">
                        <label class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                wire:model.live="isRecurring"
                                class="h-5 w-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                            <span class="text-sm font-medium text-gray-700">{{ __('transactions.recurring') }}</span>
                        </label>

                        @if($isRecurring)
                            <select
                                wire:model="recurringInterval"
                                class="w-full rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 outline-none transition-all duration-200 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 focus:shadow-md"
                            >
                                @foreach($intervals as $interval)
                                    <option value="{{ $interval->value }}">
                                        {{ __("transactions.interval_{$interval->value}") }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        {{-- Submit --}}
        <button
            type="submit"
            class="w-full rounded-xl bg-primary-600 py-4 text-center text-sm font-semibold text-white shadow-md transition hover:bg-primary-700 disabled:opacity-50"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="save">{{ __('transactions.save') }}</span>
            <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                {{ __('common.loading') }}
            </span>
        </button>

    </form>

</div>
