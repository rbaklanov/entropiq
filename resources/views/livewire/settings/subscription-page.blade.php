<div class="mx-auto max-w-3xl space-y-6">

    {{-- Header --}}
    <div>
        <a href="{{ route('settings.index') }}" class="-ml-1 inline-flex min-h-11 items-center gap-1.5 text-[15px] text-gray-500 hover:text-gray-900">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            {{ __('common.settings_title') }}
        </a>
        <h1 class="mt-2 text-h1">{{ __('subscription.heading') }}</h1>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="rounded-md border border-success-200 bg-success-50 p-4 text-sm font-medium text-success-700" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-md border border-danger-200 bg-danger-50 p-4 text-sm font-medium text-danger-700" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if($renewsSoon)
        <div class="rounded-md border border-warning-200 bg-warning-50 p-4 text-sm font-medium text-warning-800" role="status">
            {{ __('payments.auto_renew_soon', ['date' => $activeSubscription->ends_at->translatedFormat('j F Y'), 'amount' => $renewalAmount]) }}
        </div>
    @endif

    {{-- Current plan badge --}}
    @if($isPremium && $activeSubscription)
        <div class="rounded-md border-2 border-primary-600 bg-white p-5">
            <div class="flex items-center gap-3">
                <div>
                    <p class="font-serif text-lg font-semibold text-primary-600">{{ __('subscription.current_plan') }}: Premium</p>
                    <p class="mt-0.5 text-sm text-gray-500">
                        {{ __('subscription.expires_at', ['date' => $activeSubscription->ends_at->translatedFormat('j F Y')]) }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Comparison table --}}
    <div class="overflow-x-auto rounded-md border border-gray-200 bg-white p-5">
        <table class="w-full min-w-[420px] text-[15px]">
            <thead>
                <tr class="border-b border-gray-900">
                    <th scope="col" class="pb-3 text-left font-medium text-gray-500"><span class="sr-only">{{ __('subscription.heading') }}</span></th>
                    <th scope="col" class="pb-3 text-center font-medium text-gray-500">Free</th>
                    <th scope="col" class="pb-3 text-center font-semibold text-primary-600">Premium</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
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
                        <th scope="row" class="py-3 text-left font-normal text-gray-900">{{ $row['feature'] }}</th>
                        <td class="py-3 text-center">
                            @if($row['free'] === true)
                                <span class="font-semibold text-gray-900">✓</span>
                            @elseif($row['free'] === false)
                                <span class="text-gray-400">—</span>
                            @else
                                <span class="text-sm text-gray-500">{{ $row['free'] }}</span>
                            @endif
                        </td>
                        <td class="py-3 text-center">
                            <span class="font-semibold text-primary-600">✓</span>
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
                type="button"
                wire:click="selectPlan('monthly')"
                aria-pressed="{{ $selectedPlan === 'monthly' ? 'true' : 'false' }}"
                class="rounded-md border-2 bg-white p-5 text-left transition-colors
                    {{ $selectedPlan === 'monthly' ? 'border-primary-600' : 'border-gray-200 hover:border-gray-400' }}"
            >
                <p class="text-sm font-medium text-gray-500">{{ __('subscription.plan_monthly') }}</p>
                <p class="mt-1 font-serif text-[1.75rem] font-semibold text-gray-900">{{ __('subscription.price_monthly', App\Enums\SubscriptionPlan::priceReplacements()) }}</p>
            </button>

            {{-- Yearly --}}
            <button
                type="button"
                wire:click="selectPlan('yearly')"
                aria-pressed="{{ $selectedPlan === 'yearly' ? 'true' : 'false' }}"
                class="relative rounded-md border-2 bg-white p-5 text-left transition-colors
                    {{ $selectedPlan === 'yearly' ? 'border-primary-600' : 'border-gray-200 hover:border-gray-400' }}"
            >
                <span class="absolute -top-3 right-4 rounded-sm bg-gray-900 px-2.5 py-0.5 text-xs font-semibold text-white">
                    {{ __('subscription.recommended') }}
                </span>
                <p class="text-sm font-medium text-gray-500">{{ __('subscription.plan_yearly') }}</p>
                <p class="mt-1 font-serif text-[1.75rem] font-semibold text-gray-900">{{ __('subscription.price_yearly', App\Enums\SubscriptionPlan::priceReplacements()) }}</p>
                <p class="mt-1 text-xs font-medium text-success-600">{{ __('subscription.yearly_savings', App\Enums\SubscriptionPlan::priceReplacements()) }}</p>
            </button>

        </div>

        {{-- Auto renewal consent --}}
        <label for="auto-renew" class="flex items-start gap-3 text-sm text-gray-900">
            <input
                type="checkbox"
                id="auto-renew"
                wire:model="autoRenew"
                class="mt-0.5 h-5 w-5 shrink-0 rounded border-gray-300 accent-primary-600"
            />
            <span>
                {{ __('payments.auto_renew_label') }}
                <a href="{{ route('offer') }}" class="underline" target="_blank">{{ __('payments.auto_renew_offer_link') }}</a>.
            </span>
        </label>

        {{-- Guarantee --}}
        <p class="text-center text-sm text-gray-500">{{ __('subscription.guarantee') }}</p>

        {{-- Subscribe button --}}
        <button
            wire:click="subscribe"
            wire:loading.attr="disabled"
            class="min-h-12 w-full rounded-md bg-primary-600 text-sm font-semibold text-white transition-colors hover:bg-primary-700 disabled:opacity-50"
        >
            <span wire:loading.remove wire:target="subscribe">{{ __('subscription.subscribe') }}</span>
            <span wire:loading wire:target="subscribe">...</span>
        </button>

        <p class="text-center text-xs text-gray-500">
            {{ __('payments.offer_acceptance') }}
            <a href="{{ route('offer') }}" class="underline" target="_blank">{{ __('payments.offer_link') }}</a>
        </p>
    @elseif($activeSubscription)
        {{-- Auto renewal for premium users --}}
        <div class="rounded-md border border-gray-200 bg-white p-5">
            @if($activeSubscription->auto_renew)
                <p class="text-sm text-gray-900">
                    {{ __('payments.auto_renew_on', ['date' => $activeSubscription->ends_at->translatedFormat('j F Y'), 'amount' => $renewalAmount]) }}
                </p>
                <button
                    wire:click="disableAutoRenew"
                    wire:confirm="{{ __('payments.disable_auto_renew_confirm') }}"
                    class="mt-4 inline-flex min-h-11 items-center rounded-md border border-gray-300 px-5 text-sm font-medium text-gray-900 transition-colors hover:border-gray-900"
                >
                    {{ __('payments.disable_auto_renew') }}
                </button>
            @else
                <p class="text-sm text-gray-900">
                    {{ __('payments.auto_renew_off', ['date' => $activeSubscription->ends_at->translatedFormat('j F Y')]) }}
                </p>
            @endif
        </div>
    @endif

</div>
