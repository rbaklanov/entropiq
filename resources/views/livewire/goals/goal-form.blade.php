<div class="mx-auto max-w-lg space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        @if($step > 1)
            <button wire:click="prevStep" class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </button>
        @else
            <a href="{{ route('goals.index') }}" class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </a>
        @endif
        <h1 class="text-h1">{{ __('goals.create') }}</h1>
    </div>

    {{-- Step indicator --}}
    <div class="flex gap-1.5">
        @for($i = 1; $i <= $totalSteps; $i++)
            <div
                class="h-1.5 flex-1 rounded-full transition-all duration-300"
                style="background-color: {{ $i < $step ? '#6366F1' : ($i === $step ? 'rgba(99,102,241,0.45)' : '#E5E7EB') }};{{ $i < $step ? ' cursor: pointer;' : '' }}"
                @if($i < $step) wire:click="goToStep({{ $i }})" @endif
            ></div>
        @endfor
    </div>

    {{-- Step 1: Type --}}
    @if($step === 1)
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('goals.step_type') }}</h2>

            <div class="grid grid-cols-2 gap-3">
                @php
                    $typeIcons = [
                        'safety_net' => '🛡️',
                        'large_purchase' => '🛒',
                        'travel' => '✈️',
                        'car' => '🚗',
                        'apartment' => '🏠',
                        'education' => '🎓',
                        'other' => '🎯',
                    ];
                @endphp

                @foreach($goalTypes as $goalType)
                    <button
                        wire:click="selectType('{{ $goalType->value }}')"
                        class="flex items-center gap-3 rounded-xl border-2 p-4 text-left transition
                            {{ $type === $goalType->value
                                ? 'border-primary-500 bg-primary-50'
                                : 'border-gray-200 bg-white hover:border-gray-300' }}"
                    >
                        <span class="text-2xl">{{ $typeIcons[$goalType->value] ?? '🎯' }}</span>
                        <span class="text-sm font-medium text-gray-900">{{ __("goals.type_{$goalType->value}") }}</span>
                    </button>
                @endforeach
            </div>

            @error('type')
                <p class="text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>
    @endif

    {{-- Step 2: Name --}}
    @if($step === 2)
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('goals.step_name') }}</h2>

            <input
                type="text"
                wire:model="name"
                placeholder="{{ __('goals.name_placeholder') }}"
                autofocus
                class="w-full rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 text-base text-gray-700 outline-none transition-all duration-200 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20"
            />

            @error('name')
                <p class="text-sm text-red-500">{{ $message }}</p>
            @enderror

            <button
                wire:click="nextStep"
                class="w-full rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-primary-700"
            >
                {{ __('goals.next') }}
            </button>
        </div>
    @endif

    {{-- Step 3: Amounts --}}
    @if($step === 3)
        <div class="space-y-6">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('goals.step_amount') }}</h2>

            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-600">{{ __('goals.target_amount') }}</label>
                <div class="relative">
                    <input
                        type="text"
                        inputmode="decimal"
                        wire:model.live="targetAmountDisplay"
                        placeholder="0"
                        autofocus
                        class="w-full rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 pr-14 text-xl font-bold text-gray-900 outline-none transition-all duration-200 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20"
                    />
                    <span class="absolute right-5 top-1/2 -translate-y-1/2 text-lg text-gray-400">₽</span>
                </div>
                @error('targetAmount')
                    <p class="text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-600">{{ __('goals.current_amount') }}</label>
                <div class="relative">
                    <input
                        type="text"
                        inputmode="decimal"
                        wire:model.live="initialAmountDisplay"
                        placeholder="0"
                        class="w-full rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 pr-14 text-base text-gray-700 outline-none transition-all duration-200 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20"
                    />
                    <span class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400">₽</span>
                </div>
            </div>

            <button
                wire:click="nextStep"
                class="mt-2 w-full rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-primary-700"
            >
                {{ __('goals.next') }}
            </button>
        </div>
    @endif

    {{-- Step 4: Deadline + Summary --}}
    @if($step === 4)
        <div class="space-y-5">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('goals.step_deadline') }}</h2>

            {{-- Preset buttons --}}
            <div class="flex flex-wrap gap-2">
                @foreach([3, 6, 12, 24, 36] as $months)
                    <button
                        wire:click="setPresetMonths({{ $months }})"
                        class="rounded-full px-4 py-1.5 text-sm font-medium transition
                            {{ $presetMonths === $months
                                ? 'bg-primary-600 text-white'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                    >
                        {{ __('goals.preset_months', ['count' => $months]) }}
                    </button>
                @endforeach
            </div>

            {{-- Date picker --}}
            <div wire:ignore>
                <div
                    x-data="{
                        fp: null,
                        init() {
                            const initial = $wire.get('targetDate');
                            this.fp = flatpickr(this.$refs.dateInput, {
                                dateFormat: 'd-m-Y',
                                minDate: 'today',
                                defaultDate: initial || undefined,
                                locale: 'ru',
                                disableMobile: true,
                                parseDate(datestr) {
                                    if (/^\d{4}-\d{2}-\d{2}$/.test(datestr)) return new Date(datestr + 'T00:00:00');
                                    const [d, m, y] = datestr.split('-');
                                    return new Date(y + '-' + m + '-' + d + 'T00:00:00');
                                },
                                onChange: (sel) => {
                                    if (sel.length) {
                                        const d = sel[0];
                                        const iso = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
                                        $wire.set('targetDate', iso);
                                        $wire.set('presetMonths', 0);
                                    }
                                },
                            });
                        },
                        destroy() { this.fp?.destroy(); },
                    }"
                >
                    <label class="text-sm font-medium text-gray-600">{{ __('goals.target_date') }}</label>
                    <input
                        type="text"
                        x-ref="dateInput"
                        readonly
                        placeholder="{{ __('goals.date_placeholder') }}"
                        class="mt-1 w-full cursor-pointer rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 text-base text-gray-700 outline-none transition-all duration-200 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20"
                    />
                </div>
            </div>

            {{-- Monthly payment preview --}}
            @if($monthlyPayment > 0)
                <div class="rounded-xl border border-primary-200 bg-primary-50 p-4">
                    <p class="text-sm text-gray-600">{{ __('goals.monthly_contribution') }}</p>
                    <p class="mt-1 text-xl font-bold text-primary-700">
                        {{ number_format($monthlyPayment / 100, 0, '.', ' ') }} ₽/{{ __('goals.per_month') }}
                    </p>
                </div>
            @endif

            {{-- Summary --}}
            <div class="rounded-xl bg-gray-50 p-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">{{ __('goals.type') }}</span>
                    <span class="font-medium text-gray-900">{{ __("goals.type_{$type}") }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">{{ __('goals.name') }}</span>
                    <span class="font-medium text-gray-900">{{ $name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">{{ __('goals.target_amount') }}</span>
                    <span class="font-medium text-gray-900">{{ $targetAmountDisplay }} ₽</span>
                </div>
                @if($initialAmount > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">{{ __('goals.current_amount') }}</span>
                        <span class="font-medium text-gray-900">{{ $initialAmountDisplay }} ₽</span>
                    </div>
                @endif
            </div>

            <button
                wire:click="save"
                class="w-full rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-primary-700"
            >
                {{ __('goals.create_button') }}
            </button>
        </div>
    @endif

</div>
