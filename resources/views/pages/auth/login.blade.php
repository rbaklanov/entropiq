<x-layouts.guest>
    <div class="mx-auto max-w-md px-4 py-20">
        <div class="rounded-2xl bg-white p-8 shadow-sm">
            <h1 class="text-center text-h2">{{ __('auth.login_title') }}</h1>
            <p class="mt-2 text-center text-caption text-gray-500">
                {{ __('auth.login_subtitle') }}
            </p>

            <form method="POST" action="{{ route('auth.sendCode') }}" class="mt-8">
                @csrf

                <x-phone-input :error="$errors->first('phone')" />

                <button
                    type="submit"
                    class="mt-6 w-full rounded-lg bg-primary-600 px-4 py-3 text-sm font-medium text-white transition hover:bg-primary-700"
                >
                    {{ __('auth.send_code') }}
                </button>
            </form>

            <p class="mt-4 text-center text-small text-gray-400">
                {{ __('auth.terms_agreement') }}
                <a href="#" class="underline">{{ __('auth.terms_link') }}</a>
            </p>
        </div>
    </div>
</x-layouts.guest>
