<x-layouts.guest :title="__('landing.meta_title')">

    @php
        $nominalLine = [[0, 150], [53, 140], [106, 132], [160, 126], [213, 116], [266, 106], [320, 96], [373, 86], [426, 72], [480, 58]];
        $realLine = [[0, 150], [53, 142], [106, 136], [160, 132], [213, 126], [266, 120], [320, 114], [373, 108], [426, 100], [480, 90]];
        $toPath = fn (array $points) => 'M'.collect($points)->map(fn ($p) => "{$p[0]} {$p[1]}")->implode(' L');
        $lossBand = collect($nominalLine)->concat(array_reverse($realLine))->map(fn ($p) => "{$p[0]},{$p[1]}")->implode(' ');
        $prices = App\Enums\SubscriptionPlan::priceReplacements();
    @endphp

    {{-- Hero --}}
    <section class="py-16 sm:py-24">
        <div class="mx-auto grid max-w-6xl items-center gap-14 px-4 sm:px-6 lg:grid-cols-[1.1fr_1fr]">
            <div>
                <h1 class="font-serif text-[2.5rem] font-semibold leading-[1.08] tracking-tight text-gray-900 sm:text-6xl">
                    {{ __('landing.hero_title_before') }}
                    <span class="relative whitespace-nowrap">{{ __('landing.hero_title_accent') }}<svg aria-hidden="true" viewBox="0 0 300 20" preserveAspectRatio="none" class="absolute inset-x-0 -bottom-2.5 h-4 w-full text-warning-600"><path pathLength="1" class="pen-draw [animation-delay:.6s]" d="M4 12 C 60 4, 130 18, 200 9 S 280 6, 296 11" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" /></svg></span>
                    {{ __('landing.hero_title_after') }}
                </h1>
                <p class="mt-7 max-w-lg text-lg text-gray-500 sm:text-[1.1875rem]">
                    {{ __('landing.hero_subtitle') }}
                </p>
                <div class="mt-9 flex flex-wrap items-center gap-6">
                    <a href="/login" class="inline-flex min-h-12 items-center rounded-md bg-primary-600 px-7 text-base font-semibold text-white transition-colors hover:bg-primary-700">
                        {{ __('landing.hero_cta') }}
                    </a>
                    <a href="#features" class="font-medium text-primary-600 underline-offset-4 hover:text-primary-700 hover:underline">
                        {{ __('landing.hero_cta_secondary') }}
                    </a>
                </div>
                <p class="mt-4 text-sm text-gray-500">{{ __('landing.hero_no_card') }}</p>
            </div>

            <figure class="rounded-md border border-gray-200 bg-white p-6 sm:p-7">
                <div class="flex flex-wrap items-baseline justify-between gap-3">
                    <figcaption class="text-sm text-gray-500">{{ __('landing.hero_balance_label') }}</figcaption>
                    <span class="text-[13px] text-gray-500">{{ __('landing.hero_example') }}</span>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[13px] text-gray-500">{{ __('landing.hero_nominal') }}</p>
                        <p class="font-serif text-2xl font-semibold tabular-nums sm:text-[2rem]">412 000 ₽</p>
                    </div>
                    <div>
                        <p class="text-[13px] text-gray-500">{{ __('landing.hero_real') }}</p>
                        <p class="font-serif text-2xl font-semibold text-primary-600 tabular-nums sm:text-[2rem]">376 900 ₽</p>
                    </div>
                </div>
                <svg role="img" aria-label="{{ __('landing.hero_chart_label') }}" viewBox="0 0 480 190" class="mt-4 block h-auto w-full">
                    <g class="text-gray-200" stroke="currentColor" stroke-width="1"><line x1="0" y1="40" x2="480" y2="40" /><line x1="0" y1="95" x2="480" y2="95" /><line x1="0" y1="150" x2="480" y2="150" /></g>
                    <polygon points="{{ $lossBand }}" class="ink-fade fill-warning-600/15 [animation-delay:1.6s]" />
                    <path pathLength="1" class="pen-draw text-gray-900 [animation-duration:1.8s]" d="{{ $toPath($nominalLine) }}" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" />
                    <path pathLength="1" class="pen-draw text-primary-600 [animation-delay:.4s] [animation-duration:1.8s]" d="{{ $toPath($realLine) }}" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round" />
                </svg>
                <div class="mt-3 flex flex-wrap justify-between gap-3 border-t border-dashed border-gray-200 pt-3 text-[15px]">
                    <span class="text-gray-500">{{ __('landing.hero_inflation_ate') }}</span>
                    <span class="relative inline-block font-semibold text-warning-600 tabular-nums">
                        −35 100 ₽
                        <svg aria-hidden="true" viewBox="0 0 200 56" preserveAspectRatio="none" class="pointer-events-none absolute -inset-x-3.5 -inset-y-2.5 h-[calc(100%+20px)] w-[calc(100%+28px)] overflow-visible">
                            <path pathLength="1" class="pen-draw [animation-delay:2.2s]" d="M14 30 C 10 8, 186 2, 194 26 S 124 54, 44 48 S 4 24, 34 9" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                        </svg>
                    </span>
                </div>
            </figure>
        </div>
    </section>

    {{-- Problem --}}
    <section class="bg-gray-100 py-16 sm:py-24">
        <div class="reveal mx-auto max-w-6xl px-4 sm:px-6">
            <h2 class="max-w-3xl font-serif text-3xl font-semibold leading-tight tracking-tight sm:text-[2.625rem]">
                {{ __('landing.problem_title') }}
            </h2>
            <p class="mt-4 max-w-2xl text-gray-500">{{ __('landing.problem_subtitle') }}</p>

            <div aria-hidden="true" class="mt-11 grid max-w-3xl grid-cols-10 gap-1.5 sm:gap-2">
                @for($i = 0; $i < 9; $i++)
                    <span class="h-11 rounded-sm bg-gray-900 sm:h-16"></span>
                @endfor
                <span class="ink-melt h-11 rounded-sm bg-warning-600 sm:h-16"></span>
            </div>

            <div class="mt-10 grid max-w-3xl gap-8 sm:grid-cols-2">
                <div class="border-t border-gray-900 pt-3.5">
                    <p class="font-serif text-4xl font-semibold">{{ __('landing.problem_stat_1_value') }}</p>
                    <p class="mt-1 text-[15px] text-gray-500">{{ __('landing.problem_stat_1_label') }}</p>
                </div>
                <div class="border-t border-gray-900 pt-3.5">
                    <p class="font-serif text-4xl font-semibold text-warning-600">{{ __('landing.problem_stat_2_value') }}</p>
                    <p class="mt-1 text-[15px] text-gray-500">{{ __('landing.problem_stat_2_label') }}</p>
                </div>
            </div>

            <p class="mt-10 max-w-2xl text-lg">{{ __('landing.problem_entropiq') }}</p>
        </div>
    </section>

    {{-- Inflation Calculator (ENQ-100) --}}
    <section class="py-16 sm:py-24">
        <div class="mx-auto grid max-w-6xl items-start gap-12 px-4 sm:px-6 lg:grid-cols-[1fr_1.4fr]">
            <div>
                <h2 class="font-serif text-3xl font-semibold leading-tight sm:text-[2.375rem]">{{ __('landing.calculator_title') }}</h2>
                <p class="mt-4 text-gray-500">{{ __('landing.calculator_subtitle') }}</p>
                <a href="/login" class="mt-6 inline-block font-medium text-primary-600 underline-offset-4 hover:text-primary-700 hover:underline">
                    {{ __('landing.calculator_cta') }}
                </a>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-6 sm:p-8">
                <livewire:inflation-calculator />
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="border-t border-gray-200 py-16 sm:py-24">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <h2 class="font-serif text-3xl font-semibold sm:text-[2.625rem]">{{ __('landing.features_title') }}</h2>
            <p class="mt-3 text-gray-500">{{ __('landing.features_subtitle') }}</p>

            <ol class="reveal mt-12 grid gap-x-14 sm:grid-cols-2">
                @foreach(['inflation', 'ai', 'goals', 'analytics', 'scenarios', 'security'] as $feature)
                    <li class="group flex gap-5 border-t border-gray-200 py-6">
                        <span class="min-w-9 font-serif text-[1.375rem] text-gray-500 transition-colors group-hover:text-primary-600">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <div>
                            <h3 class="text-[1.1875rem] font-semibold text-gray-900">{{ __("landing.feature_{$feature}_title") }}</h3>
                            <p class="mt-1.5 text-gray-500">{{ __("landing.feature_{$feature}_desc") }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Competitor Comparison --}}
    <section class="border-t border-gray-200 py-16 sm:py-24">
        <div class="mx-auto max-w-4xl px-4 sm:px-6">
            <h2 class="font-serif text-3xl font-semibold sm:text-[2.625rem]">{{ __('landing.comparison_title') }}</h2>
            <p class="mt-3 text-gray-500">{{ __('landing.comparison_subtitle') }}</p>

            <div class="mt-10 overflow-x-auto">
                <table class="w-full min-w-[520px] text-[15px]">
                    <thead>
                        <tr class="border-b border-gray-900">
                            <th scope="col" class="py-3 pr-4 text-left font-medium text-gray-500">{{ __('landing.comparison_feature') }}</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold text-primary-600">Entropiq</th>
                            <th scope="col" class="px-4 py-3 text-center font-medium text-gray-500">Дзен-Мани</th>
                            <th scope="col" class="px-4 py-3 text-center font-medium text-gray-500">CoinKeeper</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $rows = [
                                ['key' => 'inflation_tracking', 'entropiq' => true, 'zen' => false, 'coin' => false],
                                ['key' => 'personal_inflation', 'entropiq' => true, 'zen' => false, 'coin' => false],
                                ['key' => 'real_balance', 'entropiq' => true, 'zen' => false, 'coin' => false],
                                ['key' => 'ai_advice', 'entropiq' => true, 'zen' => false, 'coin' => false],
                                ['key' => 'goal_inflation', 'entropiq' => true, 'zen' => false, 'coin' => false],
                                ['key' => 'free_plan', 'entropiq' => true, 'zen' => true, 'coin' => false],
                                ['key' => 'no_bank', 'entropiq' => true, 'zen' => false, 'coin' => true],
                            ];
                        @endphp

                        @foreach($rows as $row)
                            <tr class="border-b border-gray-200">
                                <th scope="row" class="py-3.5 pr-4 text-left font-normal">{{ __("landing.comparison_{$row['key']}") }}</th>
                                @foreach(['entropiq', 'zen', 'coin'] as $product)
                                    <td class="px-4 py-3.5 text-center">
                                        @if($row[$product])
                                            <span @class(['font-semibold', 'text-primary-600' => $product === 'entropiq', 'text-gray-900' => $product !== 'entropiq'])>✓</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="bg-gray-100 py-16 sm:py-24">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <h2 class="font-serif text-3xl font-semibold sm:text-[2.625rem]">{{ __('landing.pricing_title') }}</h2>
            <p class="mt-3 text-gray-500">{{ __('landing.pricing_subtitle') }}</p>

            <div class="reveal mt-11 grid max-w-4xl gap-6 sm:grid-cols-2">
                {{-- Free --}}
                <div class="flex flex-col gap-5 rounded-md border border-gray-200 bg-white p-8">
                    <div>
                        <h3 class="text-lg font-semibold">{{ __('landing.pricing_free') }}</h3>
                        <p class="mt-2 font-serif text-[2.75rem] font-semibold leading-none">{{ __('landing.pricing_free_price') }} <span class="font-sans text-base font-normal text-gray-500">{{ __('landing.pricing_forever') }}</span></p>
                    </div>
                    <ul class="space-y-2.5">
                        <li>{{ __('landing.pricing_free_transactions') }}</li>
                        <li>{{ __('landing.pricing_free_goal') }}</li>
                        <li>{{ __('landing.pricing_free_analytics') }}</li>
                        <li>{{ __('landing.pricing_free_inflation') }}</li>
                        <li class="text-gray-500 line-through">{{ __('landing.pricing_no_ai') }}</li>
                        <li class="text-gray-500 line-through">{{ __('landing.pricing_no_export') }}</li>
                    </ul>
                    <a href="/login" class="mt-auto flex min-h-12 items-center justify-center rounded-md border border-gray-900 font-semibold text-gray-900 transition-colors hover:bg-gray-100">
                        {{ __('landing.pricing_free_cta') }}
                    </a>
                </div>

                {{-- Premium --}}
                <div class="flex flex-col gap-5 rounded-md border-2 border-primary-600 bg-white p-[31px]">
                    <div>
                        <h3 class="text-lg font-semibold text-primary-600">{{ __('landing.pricing_premium') }}</h3>
                        <p class="mt-2 font-serif text-[2.75rem] font-semibold leading-none">{{ __('landing.pricing_premium_price', $prices) }} <span class="font-sans text-base font-normal text-gray-500">/ {{ __('landing.pricing_month') }}</span></p>
                        <p class="mt-1.5 text-[15px] text-gray-500">{{ __('landing.pricing_premium_yearly', $prices) }}</p>
                    </div>
                    <ul class="space-y-2.5">
                        <li>{{ __('landing.pricing_premium_transactions') }}</li>
                        <li>{{ __('landing.pricing_premium_goals') }}</li>
                        <li>{{ __('landing.pricing_premium_analytics') }}</li>
                        <li>{{ __('landing.pricing_premium_ai') }}</li>
                        <li>{{ __('landing.pricing_premium_export') }}</li>
                        <li>{{ __('landing.pricing_premium_scenarios') }}</li>
                    </ul>
                    <a href="/login" class="mt-auto flex min-h-12 items-center justify-center rounded-md bg-primary-600 font-semibold text-white transition-colors hover:bg-primary-700">
                        {{ __('landing.pricing_premium_cta') }}
                    </a>
                </div>
            </div>
            <p class="mt-5 text-[15px] text-gray-500">{{ __('landing.pricing_guarantee') }}</p>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="py-20 text-center sm:py-28">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="font-serif text-3xl font-semibold leading-tight sm:text-[2.875rem]">{{ __('landing.final_cta_title') }}</h2>
            <p class="mt-4 text-gray-500">{{ __('landing.final_cta_subtitle') }}</p>
            <a href="/login" class="mt-8 inline-flex min-h-13 items-center rounded-md bg-primary-600 px-8 font-semibold text-white transition-colors hover:bg-primary-700">
                {{ __('landing.final_cta_button') }}
            </a>
        </div>
    </section>

</x-layouts.guest>
