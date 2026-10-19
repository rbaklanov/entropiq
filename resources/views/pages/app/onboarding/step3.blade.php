<x-layouts.onboarding>
    <x-onboarding-slide
        :step="$step"
        :total-steps="$totalSteps"
        :title="__('onboarding.step3_title')"
        :text="__('onboarding.step3_text')"
    >
        <x-slot:illustration>
            <svg viewBox="0 0 200 160" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-full w-full font-sans" aria-hidden="true">
                <circle cx="40" cy="78" r="22" class="fill-white stroke-gray-900" stroke-width="1.5" />
                <text x="40" y="75" text-anchor="middle" font-size="13" font-weight="600" class="fill-gray-900 font-serif">1</text>
                <text x="40" y="88" text-anchor="middle" font-size="7" class="fill-gray-500">{{ __('onboarding.step3_amount') }}</text>

                <line x1="64" y1="78" x2="76" y2="78" class="stroke-gray-300" stroke-width="1.5" stroke-dasharray="3 3" />

                <circle cx="100" cy="78" r="22" class="fill-white stroke-gray-900" stroke-width="1.5" />
                <text x="100" y="75" text-anchor="middle" font-size="13" font-weight="600" class="fill-gray-900 font-serif">2</text>
                <text x="100" y="88" text-anchor="middle" font-size="7" class="fill-gray-500">{{ __('onboarding.step3_category') }}</text>

                <line x1="124" y1="78" x2="136" y2="78" class="stroke-gray-300" stroke-width="1.5" stroke-dasharray="3 3" />

                <circle cx="160" cy="78" r="22" class="fill-primary-600" />
                <polyline pathLength="1" points="150,78 157,85 170,72" class="pen-draw stroke-white [animation-delay:.4s]" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" />

                <text x="100" y="132" text-anchor="middle" font-size="11" class="fill-gray-900">~ 3 {{ __('onboarding.step3_seconds') }}</text>
                <path pathLength="1" d="M70 138 C 90 134, 112 142, 130 136" class="pen-draw stroke-warning-600 [animation-delay:.9s]" stroke-width="2" stroke-linecap="round" fill="none" />
            </svg>
        </x-slot:illustration>

        <form action="{{ route('onboarding.complete') }}" method="POST">
            @csrf
            <input type="hidden" name="redirect" value="transactions.create">
            <button type="submit"
                    class="inline-flex min-h-12 w-full items-center justify-center rounded-md bg-primary-600 px-6 text-sm font-semibold text-white transition-colors hover:bg-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-2">
                {{ __('onboarding.step3_cta') }}
            </button>
        </form>

        <form action="{{ route('onboarding.complete') }}" method="POST">
            @csrf
            <button type="submit" class="min-h-11 w-full text-sm text-gray-500 transition-colors hover:text-gray-900">
                {{ __('onboarding.start_fresh') }}
            </button>
        </form>
    </x-onboarding-slide>
</x-layouts.onboarding>
