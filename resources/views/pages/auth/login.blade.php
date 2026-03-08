<x-layouts.guest>
    <div class="mx-auto max-w-md px-4 py-20">
        <div class="rounded-2xl bg-white p-8 shadow-sm">
            <h1 class="text-center text-h2">{{ __('Вход в Entropiq') }}</h1>
            <p class="mt-2 text-center text-caption text-gray-500">
                {{ __('Введите номер телефона для входа или регистрации') }}
            </p>

            <div class="mt-8">
                <x-phone-input />
            </div>

            <button
                type="button"
                disabled
                class="mt-6 w-full rounded-lg bg-primary-600 px-4 py-3 text-sm font-medium text-white opacity-50"
            >
                {{ __('Получить код') }}
            </button>

            <p class="mt-4 text-center text-small text-gray-400">
                {{ __('Нажимая «Получить код», вы соглашаетесь с') }}
                <a href="#" class="underline">{{ __('условиями использования') }}</a>
            </p>
        </div>
    </div>
</x-layouts.guest>
