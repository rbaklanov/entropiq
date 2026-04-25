<div class="space-y-6">

    {{-- Header --}}
    <div>
        <a href="{{ route('settings.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            {{ __('common.settings_title') }}
        </a>
        <h1 class="mt-2 text-h1">{{ __('common.settings_profile') }}</h1>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="rounded-xl bg-success-50 p-4 text-sm font-medium text-success-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Avatar --}}
    <div class="flex justify-center">
        @php
            $initials = $user->name
                ? collect(explode(' ', $user->name))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->join('')
                : '👤';
        @endphp
        <div class="flex h-24 w-24 items-center justify-center rounded-full bg-primary-100 text-3xl font-bold text-primary-600">
            {{ $initials }}
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
                    class="w-full rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 outline-none transition-all duration-200 focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20 focus:shadow-md"
                    placeholder="{{ __('settings.name_placeholder') }}"
                >
                @error('name')
                    <p class="mt-1 text-xs text-danger-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Phone (read-only) --}}
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('settings.phone') }}</label>
                <input
                    type="text"
                    value="{{ $user->phone }}"
                    disabled
                    class="w-full rounded-xl border-2 border-gray-200 bg-gray-100 px-4 py-3 text-sm text-gray-400"
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
