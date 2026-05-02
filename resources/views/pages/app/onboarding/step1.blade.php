<x-layouts.onboarding>
    <x-onboarding-slide
        :step="$step"
        :total-steps="$totalSteps"
        :title="__('onboarding.step1_title')"
        :text="__('onboarding.step1_text')"
    >
        <x-slot:illustration>
            <svg viewBox="0 0 200 160" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-full w-full">
                {{-- Background grid --}}
                <line x1="30" y1="20" x2="30" y2="140" stroke="#E5E7EB" stroke-width="0.5"/>
                <line x1="72" y1="20" x2="72" y2="140" stroke="#E5E7EB" stroke-width="0.5"/>
                <line x1="114" y1="20" x2="114" y2="140" stroke="#E5E7EB" stroke-width="0.5"/>
                <line x1="156" y1="20" x2="156" y2="140" stroke="#E5E7EB" stroke-width="0.5"/>
                <line x1="20" y1="40" x2="180" y2="40" stroke="#E5E7EB" stroke-width="0.5"/>
                <line x1="20" y1="70" x2="180" y2="70" stroke="#E5E7EB" stroke-width="0.5"/>
                <line x1="20" y1="100" x2="180" y2="100" stroke="#E5E7EB" stroke-width="0.5"/>
                <line x1="20" y1="130" x2="180" y2="130" stroke="#E5E7EB" stroke-width="0.5"/>

                {{-- Nominal line (indigo) --}}
                <polyline points="30,120 55,105 80,95 105,80 130,65 156,50 180,35"
                          stroke="#4F46E5" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                {{-- Nominal area --}}
                <polygon points="30,120 55,105 80,95 105,80 130,65 156,50 180,35 180,140 30,140"
                         fill="#4F46E5" fill-opacity="0.08"/>

                {{-- Real line (red/danger) --}}
                <polyline points="30,120 55,112 80,108 105,102 130,98 156,96 180,94"
                          stroke="#EF4444" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="6 3"/>
                {{-- Real area --}}
                <polygon points="30,120 55,112 80,108 105,102 130,98 156,96 180,94 180,140 30,140"
                         fill="#EF4444" fill-opacity="0.05"/>

                {{-- Legend --}}
                <line x1="40" y1="152" x2="56" y2="152" stroke="#4F46E5" stroke-width="2"/>
                <text x="60" y="155" font-size="9" fill="#6B7280" font-family="Inter, sans-serif">{{ __('onboarding.legend_nominal') }}</text>
                <line x1="120" y1="152" x2="136" y2="152" stroke="#EF4444" stroke-width="2" stroke-dasharray="4 2"/>
                <text x="140" y="155" font-size="9" fill="#6B7280" font-family="Inter, sans-serif">{{ __('onboarding.legend_real') }}</text>

                {{-- Dot on nominal --}}
                <circle cx="180" cy="35" r="4" fill="#4F46E5"/>
                {{-- Dot on real --}}
                <circle cx="180" cy="94" r="4" fill="#EF4444"/>

                {{-- Gap arrow --}}
                <line x1="188" y1="42" x2="188" y2="87" stroke="#9CA3AF" stroke-width="1" stroke-dasharray="3 2"/>
                <polygon points="185,85 188,91 191,85" fill="#9CA3AF"/>
                <polygon points="185,44 188,38 191,44" fill="#9CA3AF"/>
            </svg>
        </x-slot:illustration>

        <a href="{{ route('onboarding.step', 2) }}"
           class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            {{ __('onboarding.next') }}
        </a>

        <form action="{{ route('onboarding.skip') }}" method="POST">
            @csrf
            <button type="submit" class="w-full text-sm text-gray-400 transition hover:text-gray-600">
                {{ __('onboarding.skip_all') }}
            </button>
        </form>
    </x-onboarding-slide>
</x-layouts.onboarding>
