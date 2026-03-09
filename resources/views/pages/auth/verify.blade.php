<x-layouts.guest>
    <div class="mx-auto max-w-md px-4 py-20">
        <div class="rounded-2xl bg-white p-8 shadow-sm">
            <h1 class="text-center text-h2">{{ __('auth.verify_title') }}</h1>
            <p class="mt-2 text-center text-caption text-gray-500">
                {{ __('auth.verify_subtitle', ['phone' => session('phone', '')]) }}
            </p>

            @if(session('success'))
                <div class="mt-4 rounded-lg bg-success-50 p-3 text-center text-sm text-success-700">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('auth.verifyCode') }}" class="mt-8">
                @csrf
                <input type="hidden" name="phone" value="{{ session('phone', '') }}" />

                <x-otp-input :error="$errors->first('code')" />

                <button
                    type="submit"
                    class="mt-6 w-full rounded-lg bg-primary-600 px-4 py-3 text-sm font-medium text-white transition hover:bg-primary-700"
                >
                    {{ __('auth.verify_button') }}
                </button>
            </form>

            <p class="mt-4 text-center text-caption text-gray-500">
                <a href="{{ route('auth.login') }}" class="text-primary-600 hover:underline">
                    {{ __('auth.change_number') }}
                </a>
            </p>
        </div>
    </div>
</x-layouts.guest>
