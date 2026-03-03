@props(['icon' => null, 'title', 'description' => null, 'actionUrl' => null, 'actionLabel' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center']) }}>
    @if($icon)
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-2xl">
            {{ $icon }}
        </div>
    @endif

    <h3 class="mt-4 text-h3 text-gray-900">{{ $title }}</h3>

    @if($description)
        <p class="mt-2 max-w-sm text-caption text-gray-500">{{ $description }}</p>
    @endif

    @if($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}"
           class="mt-4 inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-700">
            {{ $actionLabel }}
        </a>
    @endif
</div>
