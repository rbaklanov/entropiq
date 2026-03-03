@props(['message' => __('Доступно с Premium'), 'actionUrl' => '/settings/subscription'])

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-xl']) }}>
    <div class="pointer-events-none select-none blur-sm">
        {{ $slot }}
    </div>

    <div class="absolute inset-0 flex flex-col items-center justify-center bg-white/70 backdrop-blur-[2px]">
        <span class="text-2xl">🔒</span>
        <p class="mt-2 text-sm font-medium text-gray-700">{{ $message }}</p>
        <a href="{{ $actionUrl }}"
           class="pointer-events-auto mt-3 inline-flex items-center rounded-lg bg-premium-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-premium-600">
            {{ __('Разблокировать с Premium') }}
        </a>
    </div>
</div>
