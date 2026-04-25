<div class="space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-h1">{{ __('common.settings_title') }}</h1>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="rounded-xl bg-success-50 p-4 text-sm font-medium text-success-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Profile card --}}
    <a href="{{ route('settings.profile') }}" class="block rounded-xl bg-white p-5 shadow-sm transition hover:shadow-md">
        <div class="flex items-center gap-4">
            @php
                $initials = $user->name
                    ? collect(explode(' ', $user->name))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->join('')
                    : '👤';
            @endphp
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-primary-100 text-lg font-bold text-primary-600">
                {{ $initials }}
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-gray-900">{{ $user->name ?? __('settings.no_name') }}</p>
                <p class="text-sm text-gray-500">{{ $user->phone }}</p>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </div>
    </a>

    {{-- Subscription --}}
    <a href="{{ route('settings.subscription') }}" class="block rounded-xl bg-white p-5 shadow-sm transition hover:shadow-md">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-premium-100 text-xl">
                    {{ $currentPlan->value === 'free' ? '⭐' : '👑' }}
                </span>
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ __('common.settings_subscription') }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $currentPlan->value === 'free' ? __('subscription.plan_free') : 'Premium' }}
                    </p>
                </div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </div>
    </a>

    {{-- Notifications --}}
    <div class="rounded-xl bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-sm font-semibold text-gray-900">{{ __('common.settings_notifications') }}</h2>

        <div class="space-y-4">
            <label class="flex items-center justify-between">
                <span class="text-sm text-gray-700">{{ __('settings.email_weekly') }}</span>
                <input type="checkbox" wire:model.live="emailWeekly" class="h-5 w-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
            </label>

            <label class="flex items-center justify-between">
                <span class="text-sm text-gray-700">{{ __('settings.push_goals') }}</span>
                <input type="checkbox" wire:model.live="pushGoals" class="h-5 w-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
            </label>

            <label class="flex items-center justify-between">
                <span class="text-sm text-gray-700">{{ __('settings.push_ai_advice') }}</span>
                <input type="checkbox" wire:model.live="pushAiAdvice" class="h-5 w-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
            </label>
        </div>
    </div>

    {{-- Language & Currency --}}
    <div class="rounded-xl bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-sm font-semibold text-gray-900">{{ __('settings.preferences') }}</h2>

        <div class="space-y-4">
            {{-- Language --}}
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-700">{{ __('common.settings_language') }}</span>
                <select
                    wire:model="locale"
                    wire:change="updateLocale"
                    class="rounded-xl border-2 border-gray-200 bg-gray-50 py-2 pl-3 pr-8 text-sm text-gray-700 outline-none transition-all duration-200 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 focus:shadow-md"
                >
                    <option value="ru">Русский</option>
                    <option value="en">English</option>
                </select>
            </div>

            {{-- Currency --}}
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-700">{{ __('common.settings_currency') }}</span>
                <select
                    wire:model="currencyCode"
                    wire:change="updateCurrency"
                    class="rounded-xl border-2 border-gray-200 bg-gray-50 py-2 pl-3 pr-8 text-sm text-gray-700 outline-none transition-all duration-200 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 focus:shadow-md"
                >
                    @forelse($currencies as $currency)
                        <option value="{{ $currency->code }}">{{ $currency->code }} ({{ $currency->symbol }})</option>
                    @empty
                        <option value="{{ $user->currency_code }}">{{ $user->currency_code }}</option>
                    @endforelse
                </select>
            </div>
        </div>
    </div>

    {{-- Data --}}
    <div class="rounded-xl bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-sm font-semibold text-gray-900">{{ __('common.settings_data') }}</h2>

        <div class="space-y-3">
            <button
                wire:click="exportCsv"
                class="flex w-full items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm text-gray-700 transition hover:bg-gray-50"
            >
                <span>{{ __('common.settings_export_csv') }}</span>
                <span class="text-gray-400">↓</span>
            </button>

            <button
                wire:click="deleteAccount"
                wire:confirm="{{ __('settings.delete_confirm') }}"
                class="flex w-full items-center justify-between rounded-lg border border-danger-200 px-4 py-3 text-sm text-danger-600 transition hover:bg-danger-50"
            >
                <span>{{ __('common.settings_delete_account') }}</span>
                <span class="text-danger-400">✕</span>
            </button>
        </div>
    </div>

    {{-- About --}}
    <div class="rounded-xl bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-sm font-semibold text-gray-900">{{ __('common.settings_about') }}</h2>

        <div class="space-y-2 text-sm text-gray-500">
            <p>{{ __('settings.version', ['version' => config('app.version', '1.0.0')]) }}</p>
            <p>{{ __('settings.made_with_love') }}</p>
        </div>
    </div>

    {{-- Logout --}}
    <form action="{{ route('auth.logout') }}" method="POST">
        @csrf
        <button type="submit" class="w-full rounded-xl border border-gray-200 bg-white py-3.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
            {{ __('settings.logout') }}
        </button>
    </form>

</div>
