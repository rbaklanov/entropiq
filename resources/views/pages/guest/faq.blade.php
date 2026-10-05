<x-layouts.guest :title="__('landing.faq_title')">

    <section class="py-20 sm:py-24">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h1 class="text-center text-h1">{{ __('landing.faq_title') }}</h1>

            <div class="mt-12 space-y-10">
                @foreach(__('faq.sections') as $section)
                    <div>
                        <h2 class="text-h3">{{ $section['title'] }}</h2>

                        <div class="mt-4 divide-y divide-gray-100 rounded-xl border border-gray-100 bg-white">
                            @foreach($section['items'] as $item)
                                <details class="group px-5 py-4">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left font-medium text-gray-900">
                                        <span>{{ $item['q'] }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-gray-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </summary>
                                    <p class="mt-3 text-sm leading-relaxed text-gray-600">{{ $item['a'] }}</p>
                                </details>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="mt-12 text-center text-sm text-gray-500">
                {{ __('landing.faq_support') }}
                <a href="mailto:support@entropiq.ru" class="text-primary-600 hover:text-primary-700">support@entropiq.ru</a>
            </p>
        </div>
    </section>

</x-layouts.guest>
