@php
    $replacements = [...App\Support\Seller::replacements(), ...App\Enums\SubscriptionPlan::priceReplacements()];
@endphp

<x-layouts.guest :title="__('offer.title')">

    <section class="py-20 sm:py-24">
        <article class="prose prose-gray mx-auto max-w-3xl px-4 sm:px-6">
            <h1>{{ __('offer.title') }}</h1>
            <p class="text-sm text-gray-500">{{ __('offer.last_updated', ['date' => '19.10.2026']) }}</p>

            @foreach(__('offer.sections', $replacements) as $section)
                <h2>{{ $section['title'] }}</h2>
                <ul>
                    @foreach($section['items'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endforeach
        </article>
    </section>

</x-layouts.guest>
