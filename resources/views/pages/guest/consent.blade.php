@php
    $replacements = App\Support\Seller::replacements();
    $version = Illuminate\Support\Carbon::parse(App\Enums\ConsentType::PersonalData->currentVersion())->format('d.m.Y');
@endphp

<x-layouts.guest :title="__('consent.title')">

    <section class="py-20 sm:py-24">
        <article class="prose prose-gray mx-auto max-w-3xl px-4 sm:px-6">
            <h1>{{ __('consent.title') }}</h1>
            <p class="text-sm text-gray-400">{{ __('consent.last_updated', ['date' => $version]) }}</p>

            <p>{{ __('consent.intro', $replacements) }}</p>

            @foreach(__('consent.sections', $replacements) as $section)
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
