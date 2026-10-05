<x-layouts.guest :title="__('landing.faq_title')">

    <section class="py-20 sm:py-24">
        <div class="mx-auto max-w-3xl px-4 text-center sm:px-6">
            <h1 class="text-h1">{{ __('landing.faq_title') }}</h1>
            <p class="mt-4 text-gray-500">{{ __('landing.faq_empty') }}</p>
            <p class="mt-6 text-sm text-gray-500">
                {{ __('landing.faq_support') }}
                <a href="mailto:support@entropiq.ru" class="text-primary-600 hover:text-primary-700">support@entropiq.ru</a>
            </p>
        </div>
    </section>

</x-layouts.guest>
