@props(['advice', 'teaser' => false, 'locked' => false])

@if($locked)
    <x-premium-lock>
        <div class="rounded-md border border-gray-200 bg-white p-6">
            <p class="text-sm text-gray-500">{{ __('advice.card_label') }} · {{ $advice->generated_at->translatedFormat('j M, H:i') }}</p>
            <div class="mt-3 h-5 w-2/3 rounded-sm bg-gray-200"></div>
            <div class="mt-3 h-3 w-full rounded-sm bg-gray-100"></div>
            <div class="mt-2 h-3 w-5/6 rounded-sm bg-gray-100"></div>
        </div>
    </x-premium-lock>
@else
    <article {{ $attributes->merge(['class' => 'flex flex-col rounded-md border border-gray-200 bg-white p-6 transition-colors hover:border-gray-300']) }}>
        <p class="flex items-center gap-2 text-sm text-gray-500">
            {{ __('advice.card_label') }} · {{ $advice->generated_at->translatedFormat('j M, H:i') }}
            @if(!$advice->is_read)
                <span class="ml-auto h-2 w-2 rounded-full bg-primary-600" aria-hidden="true"></span>
            @endif
        </p>

        <h3 class="mt-1.5 font-serif text-[1.375rem] font-semibold leading-snug text-gray-900">{{ $advice->title }}</h3>
        @unless($teaser)
            <p class="mt-2 line-clamp-2 text-[15px] text-gray-500">{{ $advice->body }}</p>
        @endunless

        <div class="mt-auto flex items-center justify-between pt-4">
            <a href="{{ route('advice.detail', $advice) }}" class="text-[15px] font-semibold text-primary-600 hover:text-primary-700">
                {{ __('advice.read_more') }}
            </a>

            @if($advice->rating !== null)
                <span class="text-gray-500" aria-label="{{ __('advice.rate_helpful') }}">
                    @if($advice->rating === 1)
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 11v9H4v-9zM7 11l4-8a2 2 0 0 1 2 2v4h5.5a2 2 0 0 1 2 2.3l-1.2 7A2 2 0 0 1 17.3 20H7" /></svg>
                    @else
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 13V4h3v9zM17 13l-4 8a2 2 0 0 1-2-2v-4H5.5a2 2 0 0 1-2-2.3l1.2-7A2 2 0 0 1 6.7 4H17" /></svg>
                    @endif
                </span>
            @endif
        </div>
    </article>
@endif
