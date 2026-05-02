<x-layouts.onboarding>
    <x-onboarding-slide
        :step="$step"
        :total-steps="$totalSteps"
        :title="__('onboarding.step3_title')"
        :text="__('onboarding.step3_text')"
    >
        <x-slot:illustration>
            <svg viewBox="0 0 200 160" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-full w-full">
                {{-- Step 1 circle --}}
                <circle cx="40" cy="80" r="22" fill="#EEF2FF" stroke="#4F46E5" stroke-width="2"/>
                <text x="40" y="76" text-anchor="middle" font-size="11" font-weight="600" fill="#4F46E5" font-family="Inter, sans-serif">1</text>
                <text x="40" y="88" text-anchor="middle" font-size="7" fill="#6366F1" font-family="Inter, sans-serif">{{ __('onboarding.step3_amount') }}</text>

                {{-- Connector 1→2 --}}
                <line x1="62" y1="80" x2="78" y2="80" stroke="#C7D2FE" stroke-width="2" stroke-dasharray="4 2"/>
                <polygon points="76,77 82,80 76,83" fill="#C7D2FE"/>

                {{-- Step 2 circle --}}
                <circle cx="100" cy="80" r="22" fill="#EEF2FF" stroke="#4F46E5" stroke-width="2"/>
                <text x="100" y="76" text-anchor="middle" font-size="11" font-weight="600" fill="#4F46E5" font-family="Inter, sans-serif">2</text>
                <text x="100" y="88" text-anchor="middle" font-size="7" fill="#6366F1" font-family="Inter, sans-serif">{{ __('onboarding.step3_category') }}</text>

                {{-- Connector 2→3 --}}
                <line x1="122" y1="80" x2="138" y2="80" stroke="#C7D2FE" stroke-width="2" stroke-dasharray="4 2"/>
                <polygon points="136,77 142,80 136,83" fill="#C7D2FE"/>

                {{-- Step 3 circle (checkmark) --}}
                <circle cx="160" cy="80" r="22" fill="#4F46E5"/>
                <polyline points="150,80 157,87 170,74" stroke="white" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>

                {{-- Timer badge --}}
                <rect x="65" y="120" width="70" height="24" rx="12" fill="#F0FDF4"/>
                <text x="100" y="136" text-anchor="middle" font-size="10" font-weight="500" fill="#10B981" font-family="Inter, sans-serif">~ 3 {{ __('onboarding.step3_seconds') }}</text>

                {{-- Decorative sparkles --}}
                <circle cx="160" cy="52" r="3" fill="#FCD34D"/>
                <circle cx="175" cy="60" r="2" fill="#FCD34D"/>
                <circle cx="148" cy="56" r="1.5" fill="#FCD34D"/>
            </svg>
        </x-slot:illustration>

        <form action="{{ route('onboarding.complete') }}" method="POST">
            @csrf
            <input type="hidden" name="redirect" value="transactions.create">
            <button type="submit"
                    class="w-full inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                {{ __('onboarding.step3_cta') }}
            </button>
        </form>

        <form action="{{ route('onboarding.complete') }}" method="POST">
            @csrf
            <button type="submit" class="w-full text-sm text-gray-400 transition hover:text-gray-600">
                {{ __('onboarding.start_fresh') }}
            </button>
        </form>
    </x-onboarding-slide>
</x-layouts.onboarding>
