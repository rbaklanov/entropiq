<div class="mx-auto max-w-lg space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('goals.index') }}" class="flex h-11 w-11 items-center justify-center rounded-md text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-900">
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
            aria-label="{{ __('goals.delete_confirm') }}"
            class="flex h-11 w-11 items-center justify-center rounded-md text-gray-500 transition-colors hover:bg-danger-50 hover:text-danger-600"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="rounded-md border border-success-200 bg-success-50 p-3 text-sm font-medium text-success-700" role="status">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-md border border-danger-200 bg-danger-50 p-3 text-sm font-medium text-danger-700" role="alert">
            {{ session('error') }}
        </div>
    @endif

    {{-- Progress card --}}
    <div class="rounded-md border border-gray-200 bg-white p-6">
        <div class="mb-3 flex items-baseline justify-between gap-3">
            <span class="font-serif text-[2rem] font-semibold leading-none text-gray-900 tabular-nums">
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
                    'ahead' => ['color' => 'text-success-600', 'label' => __('goals.pace_ahead')],
                    'behind' => ['color' => 'text-warning-600', 'label' => __('goals.pace_behind')],
                    'achieved' => ['color' => 'text-success-600', 'label' => __('goals.status_achieved')],
                    default => ['color' => 'text-primary-600', 'label' => __('goals.pace_on_track')],
                };
            @endphp
            <span class="{{ $paceConfig['color'] }} font-medium">{{ $paceConfig['label'] }}</span>
        </div>
    </div>

    {{-- Metric cards --}}
    <dl class="grid grid-cols-2 gap-3">
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <dt class="text-xs text-gray-500">{{ __('goals.remaining') }}</dt>
            <dd class="mt-1 font-serif text-lg font-semibold text-gray-900 tabular-nums">
                {{ number_format($remaining / 100, 0, '.', ' ') }} ₽
            </dd>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <dt class="text-xs text-gray-500">{{ __('goals.target_date') }}</dt>
            <dd class="mt-1 font-serif text-lg font-semibold text-gray-900 tabular-nums">
                @if($goal->target_date)
                    {{ $goal->target_date->translatedFormat('d M Y') }}
                @else
                    —
                @endif
            </dd>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <dt class="text-xs text-gray-500">{{ __('goals.without_inflation') }}</dt>
            <dd class="mt-1 font-serif text-lg font-semibold text-gray-900 tabular-nums">
                {{ number_format($this->monthlyPayment / 100, 0, '.', ' ') }} ₽/{{ __('goals.per_month') }}
            </dd>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm">
            <dt class="text-xs text-gray-500">{{ __('goals.with_inflation') }}</dt>
            <dd class="mt-1 font-serif text-lg font-semibold text-gray-900 tabular-nums">
                {{ number_format($this->monthlyPaymentInflation / 100, 0, '.', ' ') }} ₽/{{ __('goals.per_month') }}
            </dd>
        </div>
    </dl>

    {{-- Contribute button --}}
    @if(!$goal->isAchieved())
        @if($showContributeForm)
            <div class="space-y-4 rounded-md border border-gray-200 bg-white p-6">
                <h3 class="text-[15px] font-semibold text-gray-900">{{ __('goals.add_contribution') }}</h3>
                <div class="relative">
                    <input
                        type="text"
                        inputmode="decimal"
                        wire:model.live="contributeAmountDisplay"
                        placeholder="0"
                        autofocus
                        class="w-full rounded-md border border-gray-300 bg-white px-4 py-3 pr-14 font-serif text-2xl font-semibold text-gray-900 outline-none transition-all duration-200 focus:border-gray-900 focus:ring-2 focus:ring-primary-600/30"
                    />
                    <span class="absolute right-5 top-1/2 -translate-y-1/2 text-lg text-gray-500" aria-hidden="true">₽</span>
                </div>
                @error('contributeAmount')
                    <p class="text-sm text-danger-600">{{ $message }}</p>
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
                        class="rounded-md border border-gray-300 px-4 py-3 text-sm font-medium text-gray-900 transition-colors hover:border-gray-900"
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
        @if($premiumLocked)
            <x-premium-lock :message="__('subscription.feature_scenarios')">
                @include('livewire.goals.partials.scenarios-placeholder')
            </x-premium-lock>
        @else
            @include('livewire.goals.partials.scenarios', ['scenarios' => $scenarios])
        @endif
    @endif

    {{-- What-if slider --}}
    @if(!$goal->isAchieved())
        @if($premiumLocked)
            <x-premium-lock :message="__('goals.what_if')">
                @include('livewire.goals.partials.what-if-placeholder')
            </x-premium-lock>
        @else
            @include('livewire.goals.partials.what-if', ['whatIfAmount' => $whatIfAmount, 'whatIf' => $whatIf])
        @endif
    @endif

    {{-- Contributions list --}}
    @if($contributions->isNotEmpty())
        <div class="space-y-3">
            <h2 class="text-[15px] font-semibold text-gray-900">{{ __('goals.contributions') }}</h2>
            <div class="divide-y divide-gray-200 border-y border-gray-200">
                @foreach($contributions as $contribution)
                    <div class="flex items-center justify-between py-3">
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-500">{{ $contribution->date->translatedFormat('d M Y') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-success-600 tabular-nums">
                            +{{ number_format($contribution->amount / 100, $contribution->amount % 100 !== 0 ? 2 : 0, '.', ' ') }} ₽
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
