<x-layouts.onboarding>
    <x-onboarding-slide
        :step="$step"
        :total-steps="$totalSteps"
        :title="__('onboarding.step2_title')"
        :text="__('onboarding.step2_text')"
    >
        <x-slot:illustration>
            <svg viewBox="0 0 200 160" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-full w-full">
                {{-- Progress bar background --}}
                <rect x="20" y="65" width="160" height="14" rx="7" fill="#E5E7EB"/>

                {{-- Progress bar fill (65%) --}}
                <rect x="20" y="65" width="104" height="14" rx="7" fill="url(#goalGradient)"/>

                {{-- Progress bar shine --}}
                <rect x="20" y="65" width="104" height="7" rx="5" fill="white" fill-opacity="0.2"/>

                {{-- Percentage text --}}
                <text x="100" y="58" text-anchor="middle" font-size="14" font-weight="600" fill="#4F46E5" font-family="Inter, sans-serif">65%</text>

                {{-- Amount labels --}}
                <text x="20" y="98" font-size="10" fill="#9CA3AF" font-family="Inter, sans-serif">0 ₽</text>
                <text x="180" y="98" text-anchor="end" font-size="10" fill="#9CA3AF" font-family="Inter, sans-serif">200 000 ₽</text>

                {{-- Flag at the end --}}
                <line x1="180" y1="42" x2="180" y2="65" stroke="#4F46E5" stroke-width="2"/>
                <polygon points="180,42 180,52 168,47" fill="#4F46E5"/>

                {{-- Current position marker --}}
                <circle cx="124" cy="72" r="5" fill="white" stroke="#4F46E5" stroke-width="2"/>

                {{-- Sparkles --}}
                <circle cx="40" cy="45" r="2" fill="#FCD34D"/>
                <circle cx="70" cy="40" r="1.5" fill="#FCD34D"/>
                <circle cx="110" cy="38" r="2.5" fill="#FCD34D"/>

                {{-- Monthly payment hint --}}
                <rect x="50" y="110" width="100" height="28" rx="8" fill="#F0F0FF"/>
                <text x="100" y="128" text-anchor="middle" font-size="10" fill="#4F46E5" font-family="Inter, sans-serif">{{ __('onboarding.step2_hint') }}</text>

                <defs>
                    <linearGradient id="goalGradient" x1="20" y1="72" x2="124" y2="72">
                        <stop offset="0%" stop-color="#818CF8"/>
                        <stop offset="100%" stop-color="#4F46E5"/>
                    </linearGradient>
                </defs>
            </svg>
        </x-slot:illustration>

        <form action="{{ route('onboarding.complete') }}" method="POST">
            @csrf
            <input type="hidden" name="redirect" value="goals.create">
            <button type="submit"
                    class="w-full inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                {{ __('onboarding.step2_cta') }}
            </button>
        </form>

        <a href="{{ route('onboarding.step', 3) }}" class="text-sm text-gray-400 transition hover:text-gray-600">
            {{ __('onboarding.later') }}
        </a>
    </x-onboarding-slide>
</x-layouts.onboarding>
