<x-layouts.onboarding>
    <x-onboarding-slide
        :step="$step"
        :total-steps="$totalSteps"
        :title="__('onboarding.step2_title')"
        :text="__('onboarding.step2_text')"
    >
        <x-slot:illustration>
            <svg viewBox="0 0 200 160" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-full w-full font-sans" aria-hidden="true">
                <text x="100" y="52" text-anchor="middle" font-size="22" font-weight="600" class="fill-gray-900 font-serif">65%</text>

                <rect x="20" y="66" width="160" height="10" rx="2" class="fill-gray-100" />
                <rect x="20" y="66" width="104" height="10" rx="2" class="ink-grow fill-primary-600" />

                <line x1="180" y1="44" x2="180" y2="66" class="stroke-gray-900" stroke-width="1.5" />
                <polygon points="180,44 180,54 169,49" class="fill-warning-600" />

                <text x="20" y="94" font-size="10" class="fill-gray-500">0 ₽</text>
                <text x="180" y="94" text-anchor="end" font-size="10" class="fill-gray-500">200 000 ₽</text>

                <path pathLength="1" d="M52 132 C 80 126, 120 138, 148 130" class="pen-draw stroke-warning-600 [animation-delay:.8s]" stroke-width="2" stroke-linecap="round" fill="none" />
                <text x="100" y="122" text-anchor="middle" font-size="10" class="fill-gray-900">{{ __('onboarding.step2_hint') }}</text>
            </svg>
        </x-slot:illustration>

        <form action="{{ route('onboarding.complete') }}" method="POST">
            @csrf
            <input type="hidden" name="redirect" value="goals.create">
            <button type="submit"
                    class="inline-flex min-h-12 w-full items-center justify-center rounded-md bg-primary-600 px-6 text-sm font-semibold text-white transition-colors hover:bg-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-2">
                {{ __('onboarding.step2_cta') }}
            </button>
        </form>

        <a href="{{ route('onboarding.step', 3) }}" class="inline-flex min-h-11 items-center justify-center text-sm text-gray-500 transition-colors hover:text-gray-900">
            {{ __('onboarding.later') }}
        </a>
    </x-onboarding-slide>
</x-layouts.onboarding>
