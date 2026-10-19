@props(['message', 'cta' => __('subscription.upgrade'), 'icon' => null])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-3 rounded-md border border-premium-200 bg-premium-50 px-4 py-3']) }}>
    <svg class="h-5 w-5 flex-shrink-0 text-premium-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4 6.8 19.1l1-5.8L3.5 9.2l5.9-.9z" />
    </svg>
    <p class="min-w-0 flex-1 text-sm text-gray-900">{{ $message }}</p>
    <a
        href="{{ route('settings.subscription') }}"
        class="inline-flex min-h-10 flex-shrink-0 items-center rounded-md bg-primary-600 px-4 text-sm font-semibold text-white transition-colors hover:bg-primary-700"
    >
        {{ $cta }}
    </a>
</div>
