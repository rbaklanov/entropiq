<x-layouts.app>
    <div class="flex min-h-[60vh] items-center justify-center px-4">
        <div class="w-full max-w-md text-center">
            <div class="mb-6 text-sm text-gray-400">
                {{ __('onboarding.step_of', ['step' => $step, 'total' => $totalSteps]) }}
            </div>

            <div class="mb-6 text-5xl">💰</div>

            <h1 class="text-2xl font-bold text-gray-900">{{ __('onboarding.step3_title') }}</h1>
            <p class="mt-3 text-gray-500">{{ __('onboarding.step3_text') }}</p>

            <div class="mt-10 flex flex-col gap-3">
                <form action="{{ route('onboarding.complete') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center rounded-xl bg-primary-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                        {{ __('onboarding.complete') }}
                    </button>
                </form>

                <a href="{{ route('onboarding.step', 2) }}" class="text-sm text-gray-400 transition hover:text-gray-600">
                    {{ __('onboarding.back') }}
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
