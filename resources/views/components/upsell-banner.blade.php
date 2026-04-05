@props(['message', 'cta' => __('subscription.upgrade'), 'icon' => '⚡'])

<div {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-xl bg-gradient-to-r from-premium-50 to-indigo-50 p-4']) }}>
    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-premium-100 text-lg">{{ $icon }}</span>
    <p class="flex-1 text-sm text-gray-700">{{ $message }}</p>
    <a
        href="{{ route('settings.subscription') }}"
        class="flex-shrink-0 rounded-lg bg-premium-500 px-4 py-2 text-xs font-semibold text-white transition hover:bg-premium-600"
    >
        {{ $cta }}
    </a>
</div>
