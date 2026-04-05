<div class="space-y-6">

    {{-- Header --}}
    <div>
        <a href="{{ route('settings.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            {{ __('common.settings_title') }}
        </a>
        <h1 class="mt-2 text-h1">{{ __('subscription.heading') }}</h1>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="rounded-xl bg-success-50 p-4 text-sm font-medium text-success-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Current plan badge --}}
    @if($isPremium && $activeSubscription)
        <div class="rounded-xl bg-premium-50 p-5">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-premium-100 text-xl">👑</span>
                <div>
                    <p class="text-sm font-semibold text-premium-700">{{ __('subscription.current_plan') }}: Premium</p>
                    <p class="text-xs text-premium-600">
                        {{ __('subscription.expires_at', ['date' => $activeSubscription->ends_at->translatedFormat('j F Y')]) }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Comparison table --}}
    <div class="rounded-xl bg-white p-5 shadow-sm">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="pb-3 text-left font-medium text-gray-500"></th>
                    <th class="pb-3 text-center font-medium text-gray-500">Free</th>
                    <th class="pb-3 text-center font-medium text-premium-600">Premium</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @php
                    $comparisonRows = [
                        ['feature' => __('subscription.feature_unlimited_transactions'), 'free' => '50/' . __('common.month_short')],
                        ['feature' => __('subscription.feature_multiple_goals'), 'free' => '1'],
                        ['feature' => __('subscription.feature_daily_advice'), 'free' => '1/' . __('common.week_short')],
                        ['feature' => __('subscription.feature_full_inflation'), 'free' => false],
                        ['feature' => __('subscription.feature_scenarios'), 'free' => false],
                        ['feature' => __('subscription.feature_advanced_analytics'), 'free' => '1 ' . __('common.month_short')],
                        ['feature' => __('subscription.feature_export'), 'free' => false],
                        ['feature' => __('subscription.feature_no_ads'), 'free' => false],
                    ];
                @endphp
                @foreach($comparisonRows as $row)
                    <tr>
                        <td class="py-3 text-gray-700">{{ $row['feature'] }}</td>
                        <td class="py-3 text-center">
                            @if($row['free'] === true)
                                <span class="text-success-500">✓</span>
                            @elseif($row['free'] === false)
                                <span class="text-gray-300">✗</span>
                            @else
                                <span class="text-xs text-gray-400">{{ $row['free'] }}</span>
                            @endif
                        </td>
                        <td class="py-3 text-center">
                            <span class="text-premium-500">✓</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Plan cards --}}
    @if(!$isPremium)
        <div class="grid gap-4 sm:grid-cols-2">

            {{-- Monthly --}}
            <button
                wire:click="selectPlan('monthly')"
                class="rounded-xl border-2 p-5 text-left transition
                    {{ $selectedPlan === 'monthly' ? 'border-premium-500 bg-premium-50' : 'border-gray-200 bg-white hover:border-gray-300' }}"
            >
                <p class="text-sm font-medium text-gray-500">{{ __('subscription.plan_monthly') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ __('subscription.price_monthly') }}</p>
            </button>

            {{-- Yearly --}}
            <button
                wire:click="selectPlan('yearly')"
                class="relative rounded-xl border-2 p-5 text-left transition
                    {{ $selectedPlan === 'yearly' ? 'border-premium-500 bg-premium-50' : 'border-gray-200 bg-white hover:border-gray-300' }}"
            >
                <span class="absolute -top-2.5 right-4 rounded-full bg-premium-500 px-3 py-0.5 text-xs font-semibold text-white">
                    {{ __('subscription.recommended') }}
                </span>
                <p class="text-sm font-medium text-gray-500">{{ __('subscription.plan_yearly') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ __('subscription.price_yearly') }}</p>
                <p class="mt-1 text-xs font-medium text-success-600">{{ __('subscription.yearly_savings') }}</p>
            </button>

        </div>

        {{-- Guarantee --}}
        <p class="text-center text-xs text-gray-400">🛡️ {{ __('subscription.guarantee') }}</p>

        {{-- Subscribe button --}}
        <button
            wire:click="subscribe"
            wire:loading.attr="disabled"
            class="w-full rounded-xl bg-premium-500 py-3.5 text-sm font-semibold text-white transition hover:bg-premium-600 disabled:opacity-50"
        >
            <span wire:loading.remove wire:target="subscribe">{{ __('subscription.subscribe') }}</span>
            <span wire:loading wire:target="subscribe">...</span>
        </button>
    @else
        {{-- Cancel button for premium users --}}
        <div class="rounded-xl bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-600">{{ __('subscription.cancel_confirm') }}</p>
            <button
                wire:click="cancel"
                wire:confirm="{{ __('subscription.cancel_confirm') }}"
                class="mt-4 rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
            >
                {{ __('subscription.cancel') }}
            </button>
        </div>
    @endif

</div>
