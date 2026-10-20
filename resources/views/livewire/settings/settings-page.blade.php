<div class="mx-auto max-w-2xl space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-h1">{{ __('common.settings_title') }}</h1>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="rounded-md border border-success-200 bg-success-50 p-4 text-sm font-medium text-success-700" role="status">
            {{ session('success') }}
        </div>
    @endif

    {{-- Profile card --}}
    <a href="{{ route('settings.profile') }}" class="block rounded-md border border-gray-200 bg-white p-5 transition-colors hover:border-gray-400">
        <div class="flex items-center gap-4">
            @php
                $initials = $user->name
                    ? collect(explode(' ', $user->name))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->join('')
                    : null;
            @endphp
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-900 font-serif text-lg font-semibold text-white" aria-hidden="true">
                @if($initials)
                    {{ $initials }}
                @else
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="8" r="4" /><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6" /></svg>
                @endif
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
    <a href="{{ route('settings.subscription') }}" class="block rounded-md border border-gray-200 bg-white p-5 transition-colors hover:border-gray-400">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span @class([
                    'flex h-10 w-10 items-center justify-center rounded-md',
                    'bg-gray-100 text-gray-500' => $currentPlan->value === 'free',
                    'bg-primary-600 text-white' => $currentPlan->value !== 'free',
                ]) aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4 6.8 19.1l1-5.8L3.5 9.2l5.9-.9z" /></svg>
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
        <h2 class="mb-4 text-[15px] font-semibold text-gray-900">{{ __('common.settings_notifications') }}</h2>

        <div class="space-y-4">
            @if($user->hasVerifiedEmail())
                <label class="flex items-center justify-between">
                    <span class="text-[15px] text-gray-900">{{ __('settings.email_weekly') }}</span>
                    <input type="checkbox" wire:model.live="emailWeekly" class="h-5 w-5 rounded border-gray-300 accent-primary-600">
                </label>
            @else
                <div class="text-sm text-gray-500" data-testid="email-weekly-hint">
                    {{ __('settings.email_weekly') }}.
                    <a href="{{ route('settings.profile') }}" class="font-medium text-primary-600 hover:text-primary-700">{{ __('settings.email_weekly_needs_email') }}</a>
                </div>
            @endif

            <label class="flex items-center justify-between">
                <span class="text-[15px] text-gray-900">{{ __('settings.push_goals') }}</span>
                <input type="checkbox" wire:model.live="pushGoals" class="h-5 w-5 rounded border-gray-300 accent-primary-600">
            </label>

            <label class="flex items-center justify-between">
                <span class="text-[15px] text-gray-900">{{ __('settings.push_ai_advice') }}</span>
                <input type="checkbox" wire:model.live="pushAiAdvice" class="h-5 w-5 rounded border-gray-300 accent-primary-600">
            </label>
        </div>
    </div>

    {{-- Language & Currency --}}
    <div class="rounded-xl bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-[15px] font-semibold text-gray-900">{{ __('settings.preferences') }}</h2>

        <div class="space-y-4">
            {{-- Appearance: Flux keeps the choice in the browser and follows the system by default --}}
            <div class="flex flex-wrap items-center justify-between gap-3" x-data>
                <span class="text-[15px] text-gray-900" id="appearance-label">{{ __('settings.appearance') }}</span>
                <div role="group" aria-labelledby="appearance-label" class="inline-flex rounded-md border border-gray-200 bg-paper p-0.5">
                    @foreach(['system', 'light', 'dark'] as $mode)
                        <button
                            type="button"
                            x-on:click="$flux.appearance = '{{ $mode }}'"
                            :aria-pressed="($flux.appearance === '{{ $mode }}').toString()"
                            :class="$flux.appearance === '{{ $mode }}' ? 'bg-gray-900 text-white' : 'text-gray-500 hover:text-gray-900'"
                            class="min-h-10 rounded px-3 text-sm transition-colors"
                        >{{ __("settings.appearance_{$mode}") }}</button>
                    @endforeach
                </div>
            </div>

            {{-- Language --}}
            <div class="flex items-center justify-between">
                <span class="text-[15px] text-gray-900">{{ __('common.settings_language') }}</span>
                <select
                    wire:model="locale"
                    wire:change="updateLocale"
                    class="min-h-10 rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm text-gray-900 outline-none transition-colors hover:border-gray-900 focus:border-gray-900 focus:ring-2 focus:ring-primary-600/30"
                >
                    <option value="ru">Русский</option>
                    <option value="en">English</option>
                </select>
            </div>

            {{-- Currency --}}
            <div class="flex items-center justify-between">
                <span class="text-[15px] text-gray-900">{{ __('common.settings_currency') }}</span>
                <select
                    wire:model="currencyCode"
                    wire:change="updateCurrency"
                    class="min-h-10 rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm text-gray-900 outline-none transition-colors hover:border-gray-900 focus:border-gray-900 focus:ring-2 focus:ring-primary-600/30"
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
        <h2 class="mb-4 text-[15px] font-semibold text-gray-900">{{ __('common.settings_data') }}</h2>

        <div class="space-y-3">
            <button
                wire:click="exportCsv"
                class="flex min-h-12 w-full items-center justify-between rounded-md border border-gray-200 px-4 text-sm text-gray-900 transition-colors hover:border-gray-900"
            >
                <span>{{ __('common.settings_export_csv') }}</span>
                <span class="text-gray-500" aria-hidden="true">↓</span>
            </button>

            <button
                wire:click="deleteAccount"
                wire:confirm="{{ __('settings.delete_confirm') }}"
                class="flex min-h-12 w-full items-center justify-between rounded-md border border-danger-200 px-4 text-sm text-danger-600 transition-colors hover:bg-danger-50"
            >
                <span>{{ __('common.settings_delete_account') }}</span>
                <span class="text-danger-600" aria-hidden="true">✕</span>
            </button>
        </div>
    </div>

    {{-- About --}}
    <div class="rounded-xl bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-[15px] font-semibold text-gray-900">{{ __('common.settings_about') }}</h2>

        <div class="space-y-2 text-sm text-gray-500">
            <p>{{ __('settings.version', ['version' => config('app.version', '1.0.0')]) }}</p>
            <p>{{ __('settings.made_with_love') }}</p>
        </div>
    </div>

    {{-- Logout --}}
    <form action="{{ route('auth.logout') }}" method="POST">
        @csrf
        <button type="submit" class="min-h-12 w-full rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-900 transition-colors hover:border-gray-900">
            {{ __('settings.logout') }}
        </button>
    </form>

</div>
