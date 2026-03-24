<div class="mx-auto max-w-lg space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('goals.index') }}" class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </a>
            <div>
                <h1 class="text-h2">{{ $goal->name }}</h1>
                <p class="text-sm text-gray-500">{{ __("goals.type_{$goal->type->value}") }}</p>
            </div>
        </div>

        <button
            wire:click="deleteGoal"
            wire:confirm="{{ __('goals.delete_confirm') }}"
            class="rounded-lg p-2 text-gray-400 transition hover:bg-red-50 hover:text-red-500"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="rounded-xl bg-green-50 p-3 text-sm font-medium text-green-700">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-xl bg-red-50 p-3 text-sm font-medium text-red-500">
            {{ session('error') }}
        </div>
    @endif

    {{-- Progress card --}}
    <div class="rounded-2xl bg-white p-5 shadow-sm">
        <div class="mb-3 flex items-baseline justify-between">
            <span class="text-2xl font-bold text-gray-900">
                {{ number_format($goal->current_amount / 100, 0, '.', ' ') }} ₽
            </span>
            <span class="text-sm text-gray-500">
                / {{ number_format($goal->target_amount / 100, 0, '.', ' ') }} ₽
            </span>
        </div>

        <x-dual-progress-bar :nominal="$progress" height="h-3" />

        <div class="mt-3 flex items-center justify-between text-sm">
            <span class="font-medium text-gray-900">{{ $progress }}%</span>

            @php
                $paceConfig = match($paceStatus) {
                    'ahead' => ['color' => 'text-green-600', 'label' => __('goals.pace_ahead')],
                    'behind' => ['color' => 'text-orange-500', 'label' => __('goals.pace_behind')],
                    'achieved' => ['color' => 'text-green-600', 'label' => __('goals.status_achieved')],
                    default => ['color' => 'text-blue-500', 'label' => __('goals.pace_on_track')],
                };
            @endphp
            <span class="{{ $paceConfig['color'] }} font-medium">{{ $paceConfig['label'] }}</span>
        </div>
    </div>

    {{-- Metric cards --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">{{ __('goals.remaining') }}</p>
            <p class="mt-1 text-lg font-bold text-gray-900">
                {{ number_format($remaining / 100, 0, '.', ' ') }} ₽
            </p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">{{ __('goals.target_date') }}</p>
            <p class="mt-1 text-lg font-bold text-gray-900">
                @if($goal->target_date)
                    {{ $goal->target_date->translatedFormat('d M Y') }}
                @else
                    —
                @endif
            </p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">{{ __('goals.without_inflation') }}</p>
            <p class="mt-1 text-lg font-bold text-gray-900">
                {{ number_format($this->monthlyPayment / 100, 0, '.', ' ') }} ₽/{{ __('goals.per_month') }}
            </p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-xs text-gray-500">{{ __('goals.with_inflation') }}</p>
            <p class="mt-1 text-lg font-bold text-gray-900">
                {{ number_format($this->monthlyPaymentInflation / 100, 0, '.', ' ') }} ₽/{{ __('goals.per_month') }}
            </p>
        </div>
    </div>

    {{-- Contribute button --}}
    @if(!$goal->isAchieved())
        @if($showContributeForm)
            <div class="rounded-2xl bg-white p-5 shadow-sm space-y-4">
                <h3 class="text-base font-semibold text-gray-900">{{ __('goals.add_contribution') }}</h3>
                <div class="relative">
                    <input
                        type="text"
                        inputmode="decimal"
                        wire:model.live="contributeAmountDisplay"
                        placeholder="0"
                        autofocus
                        class="w-full rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 pr-14 text-xl font-bold text-gray-900 outline-none transition-all duration-200 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20"
                    />
                    <span class="absolute right-5 top-1/2 -translate-y-1/2 text-lg text-gray-400">₽</span>
                </div>
                @error('contributeAmount')
                    <p class="text-sm text-red-500">{{ $message }}</p>
                @enderror
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        wire:model="createTransaction"
                        class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <span class="text-sm text-gray-600">{{ __('goals.create_transaction') }}</span>
                </label>
                <div class="flex gap-3">
                    <button
                        wire:click="contribute"
                        class="flex-1 rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-primary-700"
                    >
                        {{ __('goals.add_contribution') }}
                    </button>
                    <button
                        wire:click="$set('showContributeForm', false)"
                        class="rounded-xl bg-gray-100 px-4 py-3 text-sm font-medium text-gray-600 transition hover:bg-gray-200"
                    >
                        {{ __('goals.cancel') }}
                    </button>
                </div>
            </div>
        @else
            <button
                wire:click="$set('showContributeForm', true)"
                class="w-full rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-primary-700"
            >
                {{ __('goals.add_contribution') }}
            </button>
        @endif
    @endif

    {{-- Scenarios --}}
    @if(!$goal->isAchieved())
        <div class="space-y-3">
            <h2 class="text-base font-semibold text-gray-900">{{ __('goals.scenarios') }}</h2>
            <div class="grid grid-cols-3 gap-3">
                @php
                    $scenarioConfig = [
                        'optimistic' => ['label' => __('goals.scenario_optimistic'), 'border' => '#BBF7D0', 'bg' => '#F0FDF4', 'text' => '#15803D'],
                        'baseline' => ['label' => __('goals.scenario_base'), 'border' => '#BFDBFE', 'bg' => '#EFF6FF', 'text' => '#1D4ED8'],
                        'pessimistic' => ['label' => __('goals.scenario_pessimistic'), 'border' => '#FED7AA', 'bg' => '#FFF7ED', 'text' => '#C2410C'],
                    ];
                @endphp

                @foreach($scenarios as $key => $scenario)
                    @php $cfg = $scenarioConfig[$key]; @endphp
                    <div class="rounded-xl p-3 text-center" style="border: 1px solid {{ $cfg['border'] }}; background-color: {{ $cfg['bg'] }}">
                        <p class="text-xs font-medium" style="color: {{ $cfg['text'] }}">{{ $cfg['label'] }}</p>
                        <p class="mt-2 text-sm font-bold text-gray-900">
                            {{ number_format($scenario['monthly_payment'] / 100, 0, '.', ' ') }} ₽
                        </p>
                        <p class="text-xs text-gray-500">/ {{ __('goals.per_month') }}</p>
                        @if($scenario['completion_date'])
                            <p class="mt-1 text-xs text-gray-500">
                                {{ \Illuminate\Support\Carbon::parse($scenario['completion_date'])->translatedFormat('M Y') }}
                            </p>
                        @endif
                        <p class="mt-1 text-xs text-gray-400">
                            {{ number_format($scenario['inflation'] * 100, 1) }}%
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- What-if slider --}}
    @if(!$goal->isAchieved())
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
    @endif

    {{-- Contributions list --}}
    @if($contributions->isNotEmpty())
        <div class="space-y-3">
            <h2 class="text-base font-semibold text-gray-900">{{ __('goals.contributions') }}</h2>
            <div class="space-y-2">
                @foreach($contributions as $contribution)
                    <div class="flex items-center justify-between rounded-xl bg-white p-4 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-100 text-sm">💰</span>
                            <span class="text-sm text-gray-500">{{ $contribution->date->translatedFormat('d M Y') }}</span>
                        </div>
                        <span class="text-sm font-bold text-green-600">
                            +{{ number_format($contribution->amount / 100, $contribution->amount % 100 !== 0 ? 2 : 0, '.', ' ') }} ₽
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
