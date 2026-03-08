<x-layouts.guest>
    <div class="mx-auto max-w-md px-4 py-20">
        <div class="rounded-2xl bg-white p-8 shadow-sm">
            <h1 class="text-center text-h2">{{ __('Введите код') }}</h1>
            <p class="mt-2 text-center text-caption text-gray-500">
                {{ __('Мы отправили SMS-код на ваш номер') }}
            </p>

            <div class="mt-8">
                <x-otp-input />
            </div>

            <button
                type="button"
                disabled
                class="mt-6 w-full rounded-lg bg-primary-600 px-4 py-3 text-sm font-medium text-white opacity-50"
            >
                {{ __('Подтвердить') }}
            </button>

            <p class="mt-4 text-center text-caption text-gray-500">
                <a href="{{ route('auth.login') }}" class="text-primary-600 hover:underline">
                    {{ __('Изменить номер') }}
                </a>
            </p>
        </div>
    </div>
</x-layouts.guest>
