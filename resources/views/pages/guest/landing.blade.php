<x-layouts.guest :title="__('landing.meta_title')">

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-primary-50 via-white to-premium-50 py-20 sm:py-32">
        <div class="absolute inset-0 opacity-30">
            <div class="absolute -top-24 left-1/4 h-96 w-96 rounded-full bg-primary-200 blur-3xl"></div>
            <div class="absolute -bottom-24 right-1/4 h-96 w-96 rounded-full bg-premium-200 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl sm:leading-tight">
                        {{ __('landing.hero_title') }}
                    </h1>
                    <p class="mt-6 text-lg leading-relaxed text-gray-600">
                        {{ __('landing.hero_subtitle') }}
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <a href="/login" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-primary-600/25 transition hover:bg-primary-700 hover:shadow-xl hover:shadow-primary-600/30">
                            {{ __('landing.hero_cta') }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </a>
                        <a href="#features" class="inline-flex items-center gap-1 text-sm font-medium text-gray-600 transition hover:text-primary-600">
                            {{ __('landing.hero_cta_secondary') }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                    <p class="mt-4 text-sm text-gray-400">{{ __('landing.hero_no_card') }}</p>
                </div>

                {{-- Hero visual: mock dashboard card --}}
                <div class="relative hidden lg:block">
                    <div class="relative mx-auto w-full max-w-sm">
                        <div class="rounded-2xl border border-gray-200/60 bg-white p-6 shadow-2xl shadow-gray-900/10">
                            <p class="text-sm font-medium text-gray-500">{{ __('landing.hero_balance_label') }}</p>
                            <div class="mt-3 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500">{{ __('landing.hero_nominal') }}</span>
                                    <span class="text-xl font-bold text-gray-900">+42 500 ₽</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500">{{ __('landing.hero_real') }}</span>
                                    <span class="text-xl font-bold text-warning-600">+38 340 ₽</span>
                                </div>
                                <div class="h-px bg-gray-100"></div>
                                <div class="flex items-center gap-2 text-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-danger-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="font-medium text-danger-500">{{ __('landing.hero_inflation_ate') }}: −4 160 ₽</span>
                                </div>
                            </div>

                            {{-- Mini chart bars --}}
                            <div class="mt-5 flex items-end gap-1.5">
                                @foreach([40, 65, 55, 80, 70, 90, 60, 75, 85, 50, 70, 95] as $h)
                                    <div class="flex-1 rounded-t bg-gradient-to-t from-primary-500 to-primary-400 transition-all duration-500" style="height: {{ $h }}px; opacity: {{ 0.4 + $h / 150 }}"></div>
                                @endforeach
                            </div>
                        </div>

                        <div class="absolute -bottom-4 -right-4 rounded-xl border border-gray-200/60 bg-white p-4 shadow-lg">
                            <div class="flex items-center gap-2">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-success-50 text-lg">🎯</span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">82%</p>
                                    <p class="text-xs text-gray-500">{{ __('goals.progress') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Problem --}}
    <section class="bg-gray-950 py-20 sm:py-24">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                    {{ __('landing.problem_title') }}
                </h2>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-gray-400">
                    {{ __('landing.problem_subtitle') }}
                </p>
            </div>

            <div class="mt-14 grid gap-6 sm:grid-cols-3">
                <div class="rounded-2xl border border-gray-800 bg-gray-900 p-8 text-center">
                    <p class="text-4xl font-extrabold text-warning-400">{{ __('landing.problem_stat_1_value') }}</p>
                    <p class="mt-2 text-sm text-gray-400">{{ __('landing.problem_stat_1_label') }}</p>
                </div>
                <div class="rounded-2xl border border-gray-800 bg-gray-900 p-8 text-center">
                    <p class="text-4xl font-extrabold text-danger-400">{{ __('landing.problem_stat_2_value') }}</p>
                    <p class="mt-2 text-sm text-gray-400">{{ __('landing.problem_stat_2_label') }}</p>
                </div>
                <div class="rounded-2xl border border-gray-800 bg-gray-900 p-8 text-center">
                    <p class="text-4xl font-extrabold text-gray-300">{{ __('landing.problem_stat_3_value') }}</p>
                    <p class="mt-2 text-sm text-gray-400">{{ __('landing.problem_stat_3_label') }}</p>
                </div>
            </div>

            <p class="mx-auto mt-10 max-w-2xl text-center text-lg font-medium text-primary-400">
                {{ __('landing.problem_entropiq') }}
            </p>
        </div>
    </section>

    {{-- Inflation Calculator (placeholder for ENQ-100) --}}
    <section class="bg-gradient-to-b from-white to-primary-50/30 py-20 sm:py-24">
        <div class="mx-auto max-w-3xl px-4 text-center sm:px-6">
            <h2 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                {{ __('landing.calculator_title') }}
            </h2>
            <p class="mt-4 text-lg text-gray-500">
                {{ __('landing.calculator_subtitle') }}
            </p>

            <div class="mt-10 rounded-2xl border border-gray-200 bg-white p-8 shadow-xl shadow-gray-900/5 sm:p-10">
                <div class="space-y-6">
                    <div>
                        <label class="mb-2 block text-left text-sm font-medium text-gray-700">{{ __('landing.calculator_amount') }}</label>
                        <input
                            type="text"
                            disabled
                            value="{{ __('landing.calculator_amount_placeholder') }}"
                            class="w-full rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3.5 text-lg font-semibold text-gray-400"
                        />
                    </div>
                    <div>
                        <label class="mb-2 block text-left text-sm font-medium text-gray-700">{{ __('landing.calculator_when') }}</label>
                        <div class="flex gap-3">
                            <button disabled class="flex-1 rounded-xl border-2 border-primary-500 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-700">
                                {{ __('landing.calculator_1y') }}
                            </button>
                            <button disabled class="flex-1 rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-500">
                                {{ __('landing.calculator_2y') }}
                            </button>
                            <button disabled class="flex-1 rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-500">
                                {{ __('landing.calculator_5y') }}
                            </button>
                        </div>
                    </div>

                    {{-- Static result preview --}}
                    <div class="rounded-xl bg-gradient-to-r from-warning-50 to-danger-50 p-6">
                        <div class="flex items-center justify-between">
                            <div class="text-left">
                                <p class="text-sm text-gray-500">{{ __('landing.calculator_result_now') }}</p>
                                <p class="text-2xl font-bold text-gray-900">456 621 ₽</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">{{ __('landing.calculator_result_lost') }}</p>
                                <p class="text-2xl font-bold text-danger-600">−43 379 ₽</p>
                            </div>
                        </div>
                        <div class="mt-4 h-3 overflow-hidden rounded-full bg-gray-200">
                            <div class="h-full rounded-full bg-gradient-to-r from-success-500 to-warning-500" style="width: 91.3%"></div>
                        </div>
                    </div>

                    <p class="text-sm text-gray-400">{{ __('landing.calculator_coming_soon') }}</p>
                </div>
            </div>

            <a href="/login" class="mt-8 inline-flex items-center gap-2 text-base font-medium text-primary-600 transition hover:text-primary-700">
                {{ __('landing.calculator_cta') }}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </a>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="bg-white py-20 sm:py-24">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    {{ __('landing.features_title') }}
                </h2>
                <p class="mt-4 text-lg text-gray-500">
                    {{ __('landing.features_subtitle') }}
                </p>
            </div>

            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $features = [
                        ['icon' => '📊', 'bg' => 'bg-warning-50', 'key' => 'inflation'],
                        ['icon' => '🤖', 'bg' => 'bg-primary-50', 'key' => 'ai'],
                        ['icon' => '🎯', 'bg' => 'bg-success-50', 'key' => 'goals'],
                        ['icon' => '📈', 'bg' => 'bg-premium-50', 'key' => 'analytics'],
                        ['icon' => '🔮', 'bg' => 'bg-primary-50', 'key' => 'scenarios'],
                        ['icon' => '🔒', 'bg' => 'bg-gray-100', 'key' => 'security'],
                    ];
                @endphp

                @foreach($features as $feature)
                    <div class="group rounded-2xl border border-gray-100 bg-white p-7 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl {{ $feature['bg'] }} text-2xl transition-transform duration-300 group-hover:scale-110">
                            {{ $feature['icon'] }}
                        </div>
                        <h3 class="mt-5 text-lg font-semibold text-gray-900">
                            {{ __("landing.feature_{$feature['key']}_title") }}
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-500">
                            {{ __("landing.feature_{$feature['key']}_desc") }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Competitor Comparison --}}
    <section class="bg-gray-50 py-20 sm:py-24">
        <div class="mx-auto max-w-4xl px-4 sm:px-6">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    {{ __('landing.comparison_title') }}
                </h2>
                <p class="mt-4 text-lg text-gray-500">
                    {{ __('landing.comparison_subtitle') }}
                </p>
            </div>

            <div class="mt-12 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50">
                                <th class="px-6 py-4 text-left font-medium text-gray-500">{{ __('landing.comparison_feature') }}</th>
                                <th class="px-6 py-4 text-center font-bold text-primary-600">Entropiq</th>
                                <th class="px-6 py-4 text-center font-medium text-gray-500">Дзен-Мани</th>
                                <th class="px-6 py-4 text-center font-medium text-gray-500">CoinKeeper</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
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
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-6 py-4 font-medium text-gray-700">{{ __("landing.comparison_{$row['key']}") }}</td>
                                    <td class="px-6 py-4 text-center">
                                        @if($row['entropiq'])
                                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-success-100 text-success-600">✓</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($row['zen'])
                                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-gray-600">✓</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($row['coin'])
                                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-gray-600">✓</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    {{-- Reviews --}}
    <section class="bg-white py-20 sm:py-24">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <h2 class="text-center text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                {{ __('landing.reviews_title') }}
            </h2>

            <div class="mt-14 grid gap-6 sm:grid-cols-3">
                @for($i = 1; $i <= 3; $i++)
                    <div class="rounded-2xl border border-gray-100 bg-white p-7 shadow-sm">
                        <div class="flex gap-1 text-warning-400">
                            @for($s = 0; $s < 5; $s++)
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            @endfor
                        </div>
                        <p class="mt-4 text-sm leading-relaxed text-gray-600">
                            &laquo;{{ __("landing.review_{$i}_text") }}&raquo;
                        </p>
                        <div class="mt-5 flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 text-sm font-bold text-primary-600">
                                {{ mb_substr(__("landing.review_{$i}_author"), 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ __("landing.review_{$i}_author") }}</p>
                                <p class="text-xs text-gray-500">{{ __("landing.review_{$i}_role") }}</p>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="bg-gray-50 py-20 sm:py-24">
        <div class="mx-auto max-w-5xl px-4 sm:px-6">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    {{ __('landing.pricing_title') }}
                </h2>
                <p class="mt-4 text-lg text-gray-500">
                    {{ __('landing.pricing_subtitle') }}
                </p>
            </div>

            <div class="mt-14 grid gap-8 sm:grid-cols-2 sm:mx-auto sm:max-w-3xl">
                {{-- Free --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-900">{{ __('landing.pricing_free') }}</h3>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-4xl font-extrabold text-gray-900">{{ __('landing.pricing_free_price') }}</span>
                        <span class="text-sm text-gray-500">/ {{ __('landing.pricing_forever') }}</span>
                    </div>
                    <ul class="mt-8 space-y-4 text-sm text-gray-600">
                        <li class="flex items-center gap-3">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-success-100 text-xs text-success-600">✓</span>
                            {{ __('landing.pricing_free_transactions') }}
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-success-100 text-xs text-success-600">✓</span>
                            {{ __('landing.pricing_free_goal') }}
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-success-100 text-xs text-success-600">✓</span>
                            {{ __('landing.pricing_free_analytics') }}
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-success-100 text-xs text-success-600">✓</span>
                            {{ __('landing.pricing_free_inflation') }}
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-gray-100 text-xs text-gray-400">—</span>
                            <span class="text-gray-400">{{ __('landing.pricing_no_ai') }}</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-gray-100 text-xs text-gray-400">—</span>
                            <span class="text-gray-400">{{ __('landing.pricing_no_export') }}</span>
                        </li>
                    </ul>
                    <div class="mt-8">
                        <a href="/login" class="block rounded-xl border-2 border-primary-600 py-3 text-center text-sm font-semibold text-primary-600 transition hover:bg-primary-50">
                            {{ __('landing.pricing_free_cta') }}
                        </a>
                    </div>
                </div>

                {{-- Premium --}}
                <div class="relative rounded-2xl border-2 border-premium-500 bg-white p-8 shadow-xl shadow-premium-500/10">
                    <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-premium-500 to-primary-500 px-4 py-1 text-xs font-bold text-white">
                        Premium
                    </span>
                    <h3 class="text-lg font-bold text-gray-900">{{ __('landing.pricing_premium') }}</h3>
                    <div class="mt-2 flex items-baseline gap-1">
                        <span class="text-4xl font-extrabold text-gray-900">{{ __('landing.pricing_premium_price') }}</span>
                        <span class="text-sm text-gray-500">/ {{ __('landing.pricing_month') }}</span>
                    </div>
                    <ul class="mt-8 space-y-4 text-sm text-gray-600">
                        <li class="flex items-center gap-3">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-premium-100 text-xs text-premium-600">✓</span>
                            {{ __('landing.pricing_premium_transactions') }}
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-premium-100 text-xs text-premium-600">✓</span>
                            {{ __('landing.pricing_premium_goals') }}
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-premium-100 text-xs text-premium-600">✓</span>
                            {{ __('landing.pricing_premium_analytics') }}
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-premium-100 text-xs text-premium-600">✓</span>
                            {{ __('landing.pricing_premium_ai') }}
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-premium-100 text-xs text-premium-600">✓</span>
                            {{ __('landing.pricing_premium_export') }}
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-premium-100 text-xs text-premium-600">✓</span>
                            {{ __('landing.pricing_premium_scenarios') }}
                        </li>
                    </ul>
                    <div class="mt-8">
                        <a href="/login" class="block rounded-xl bg-gradient-to-r from-premium-500 to-primary-500 py-3 text-center text-sm font-semibold text-white shadow-lg shadow-premium-500/25 transition hover:shadow-xl">
                            {{ __('landing.pricing_premium_cta') }}
                        </a>
                    </div>
                    <p class="mt-3 text-center text-xs text-gray-400">{{ __('landing.pricing_guarantee') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-primary-600 via-primary-700 to-premium-700 py-20 sm:py-24">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute left-0 top-0 h-96 w-96 rounded-full bg-white blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-96 w-96 rounded-full bg-premium-300 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-3xl px-4 text-center sm:px-6">
            <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                {{ __('landing.final_cta_title') }}
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-primary-100">
                {{ __('landing.final_cta_subtitle') }}
            </p>
            <div class="mt-8">
                <a href="/login" class="inline-flex items-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-bold text-primary-600 shadow-xl transition hover:bg-primary-50 hover:shadow-2xl">
                    {{ __('landing.final_cta_button') }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </a>
            </div>
        </div>
    </section>

</x-layouts.guest>
