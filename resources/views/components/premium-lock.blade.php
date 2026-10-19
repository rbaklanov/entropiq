@props(['message' => __('subscription.premium_lock'), 'actionUrl' => route('settings.subscription')])

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-md']) }}>
    <div class="pointer-events-none select-none blur-sm" aria-hidden="true" inert>
        {{ $slot }}
    </div>

    <div class="absolute inset-0 flex flex-col items-center justify-center bg-paper/80 px-4 text-center">
        <svg class="h-6 w-6 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="5" y="11" width="14" height="10" rx="1.5" />
            <path d="M8 11V7a4 4 0 0 1 8 0v4" />
        </svg>
        <p class="mt-2 text-sm font-medium text-gray-900">{{ $message }}</p>
        <a href="{{ $actionUrl }}"
           class="pointer-events-auto mt-3 inline-flex min-h-11 items-center rounded-md bg-primary-600 px-5 text-sm font-semibold text-white transition-colors hover:bg-primary-700">
            {{ __('subscription.unlock') }}
        </a>
    </div>
</div>
