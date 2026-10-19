<div class="mx-auto max-w-2xl space-y-6">

    {{-- Header --}}
    <div>
        <a href="{{ route('settings.index') }}" class="-ml-1 inline-flex min-h-11 items-center gap-1.5 text-[15px] text-gray-500 hover:text-gray-900">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            {{ __('common.settings_title') }}
        </a>
        <h1 class="mt-2 text-h1">{{ __('common.settings_profile') }}</h1>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="rounded-md border border-success-200 bg-success-50 p-4 text-sm font-medium text-success-700" role="status">
            {{ session('success') }}
        </div>
    @endif

    {{-- Avatar --}}
    <div class="flex justify-center">
        @php
            $initials = $user->name
                ? collect(explode(' ', $user->name))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->join('')
                : null;
        @endphp
        <div class="flex h-24 w-24 items-center justify-center rounded-full bg-gray-900 font-serif text-3xl font-semibold text-white" aria-hidden="true">
            @if($initials)
                {{ $initials }}
            @else
                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="8" r="4" /><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6" /></svg>
            @endif
        </div>
    </div>

    {{-- Profile form --}}
    <form wire:submit="save" class="rounded-xl bg-white p-5 shadow-sm">
        <div class="space-y-4">
            {{-- Name --}}
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-gray-700">{{ __('settings.name') }}</label>
                <input
                    id="name"
                    type="text"
                    wire:model="name"
                    class="w-full rounded-md border border-gray-300 bg-white px-4 py-3 text-sm text-gray-700 outline-none transition-all duration-200 focus:border-gray-900 focus:ring-2 focus:ring-primary-600/30"
                    placeholder="{{ __('settings.name_placeholder') }}"
                >
                @error('name')
                    <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-gray-700">{{ __('settings.email') }}</label>
                <input
                    id="email"
                    type="email"
                    wire:model="email"
                    class="w-full rounded-md border border-gray-300 bg-white px-4 py-3 text-sm text-gray-700 outline-none transition-all duration-200 focus:border-gray-900 focus:ring-2 focus:ring-primary-600/30"
                    placeholder="{{ __('settings.email_placeholder') }}"
                >
                @error('email')
                    <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                @enderror

                @if(filled($user->email))
                    @if($user->hasVerifiedEmail())
                        <p class="mt-1 text-xs text-success-700" data-testid="email-status">{{ __('settings.email_verified') }}</p>
                    @else
                        <p class="mt-1 text-xs text-warning-700" data-testid="email-status">
                            {{ __('settings.email_unverified') }}
                            <button type="button" wire:click="resendVerification" class="font-medium text-primary-600 hover:text-primary-700">
                                {{ __('settings.email_resend') }}
                            </button>
                        </p>
                    @endif
                @endif
            </div>

            {{-- Phone (read-only) --}}
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('settings.phone') }}</label>
                <input
                    type="text"
                    value="{{ $user->phone }}"
                    disabled
                    class="w-full rounded-md border border-gray-200 bg-gray-100 px-4 py-3 text-sm text-gray-400"
                >
            </div>

            {{-- Save button --}}
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full rounded-xl bg-primary-600 py-3 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="save">{{ __('common.save') }}</span>
                <span wire:loading wire:target="save">{{ __('common.loading') }}</span>
            </button>
        </div>
    </form>

</div>
