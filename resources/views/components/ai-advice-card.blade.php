@props(['advice', 'blurred' => false])

@if($blurred)
    <x-premium-lock>
        <div class="rounded-xl bg-white p-5">
            <div class="mb-2 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-warning-50 text-base">✨</span>
                <span class="text-xs text-gray-400">{{ $advice->generated_at->translatedFormat('j M, H:i') }}</span>
            </div>
            <h3 class="text-sm font-semibold text-gray-900">{{ $advice->title }}</h3>
            <p class="mt-1 line-clamp-2 text-sm text-gray-600">{{ $advice->body }}</p>
        </div>
    </x-premium-lock>
@else
    <div {{ $attributes->merge(['class' => 'rounded-xl bg-white p-5 shadow-sm transition hover:shadow-md']) }}>
        <div class="mb-2 flex items-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-warning-50 text-base">✨</span>
            <span class="text-xs text-gray-400">{{ $advice->generated_at->translatedFormat('j M, H:i') }}</span>
            @if(!$advice->is_read)
                <span class="ml-auto h-2 w-2 rounded-full bg-primary-500"></span>
            @endif
        </div>

        <h3 class="text-sm font-semibold text-gray-900">{{ $advice->title }}</h3>
        <p class="mt-1 line-clamp-2 text-sm text-gray-600">{{ $advice->body }}</p>

        <div class="mt-3 flex items-center justify-between">
            <a href="{{ route('advice.detail', $advice) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                {{ __('advice.read_more') }} →
            </a>

            @if($advice->rating !== null)
                <span class="text-sm">
                    {{ $advice->rating === 1 ? '👍' : '👎' }}
                </span>
            @endif
        </div>
    </div>
@endif
