<x-layouts.onboarding>
    <x-onboarding-slide
        :step="$step"
        :total-steps="$totalSteps"
        :title="__('onboarding.step1_title')"
        :text="__('onboarding.step1_text')"
    >
        <x-slot:illustration>
            <svg viewBox="0 0 200 160" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-full w-full font-sans" role="img" aria-label="{{ __('onboarding.legend_nominal') }}, {{ __('onboarding.legend_real') }}">
                <g class="stroke-gray-200" stroke-width="0.75">
                    <line x1="20" y1="40" x2="180" y2="40" />
                    <line x1="20" y1="80" x2="180" y2="80" />
                    <line x1="20" y1="120" x2="180" y2="120" />
                </g>

                <polygon points="30,120 55,105 80,95 105,80 130,65 156,50 180,35 180,94 156,96 130,98 105,102 80,108 55,112"
                         class="ink-fade fill-warning-600/15 [animation-delay:1.4s]" />

                <polyline pathLength="1" points="30,120 55,105 80,95 105,80 130,65 156,50 180,35"
                          class="pen-draw stroke-gray-900 [animation-duration:1.6s]" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" />
                <polyline pathLength="1" points="30,120 55,112 80,108 105,102 130,98 156,96 180,94"
                          class="pen-draw stroke-primary-600 [animation-delay:.3s] [animation-duration:1.6s]" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" />

                <circle cx="180" cy="35" r="4" class="fill-gray-900" />
                <circle cx="180" cy="94" r="4" class="fill-primary-600" />

                <line x1="40" y1="148" x2="56" y2="148" class="stroke-gray-900" stroke-width="2" />
                <text x="60" y="151" font-size="9" class="fill-gray-500">{{ __('onboarding.legend_nominal') }}</text>
                <line x1="120" y1="148" x2="136" y2="148" class="stroke-primary-600" stroke-width="2" />
                <text x="140" y="151" font-size="9" class="fill-gray-500">{{ __('onboarding.legend_real') }}</text>
            </svg>
        </x-slot:illustration>

        <a href="{{ route('onboarding.step', 2) }}"
           class="inline-flex min-h-12 items-center justify-center rounded-md bg-primary-600 px-6 text-sm font-semibold text-white transition-colors hover:bg-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-2">
            {{ __('onboarding.next') }}
        </a>

        <form action="{{ route('onboarding.skip') }}" method="POST">
            @csrf
            <button type="submit" class="min-h-11 w-full text-sm text-gray-500 transition-colors hover:text-gray-900">
                {{ __('onboarding.skip_all') }}
            </button>
        </form>
    </x-onboarding-slide>
</x-layouts.onboarding>
