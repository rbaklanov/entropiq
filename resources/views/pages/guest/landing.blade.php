<x-layouts.guest title="Entropiq — финансовый помощник с учётом инфляции">

    {{-- Hero --}}
    <section class="bg-white py-16 sm:py-24">
        <div class="mx-auto max-w-5xl px-4 text-center sm:px-6">
            <h1 class="text-h1 text-gray-900 sm:text-4xl sm:leading-tight">
                {{ __('Знайте реальную стоимость своих денег') }}
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-gray-500">
                {{ __('Финансовый помощник, который учитывает инфляцию. Ведите учёт, ставьте цели и получайте AI-советы.') }}
            </p>
            <div class="mt-8">
                <a href="/login" class="inline-flex items-center rounded-lg bg-primary-600 px-6 py-3 text-base font-semibold text-white transition hover:bg-primary-700">
                    {{ __('Начать бесплатно') }}
                </a>
            </div>
        </div>
    </section>

    {{-- Problem --}}
    <section class="bg-gray-50 py-16">
        <div class="mx-auto max-w-5xl px-4 text-center sm:px-6">
            <h2 class="text-h2 text-gray-900">{{ __('500 000 ₽ через год — это уже не 500 000 ₽') }}</h2>
            <p class="mx-auto mt-4 max-w-2xl text-gray-500">
                {{ __('Инфляция незаметно обесценивает ваши сбережения. Entropiq показывает реальную стоимость ваших денег и помогает принимать взвешенные финансовые решения.') }}
            </p>
        </div>
    </section>

    {{-- Features --}}
    <section class="bg-white py-16">
        <div class="mx-auto max-w-5xl px-4 sm:px-6">
            <h2 class="text-center text-h2 text-gray-900">{{ __('Возможности') }}</h2>
            <div class="mt-12 grid gap-8 sm:grid-cols-3">

                <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-warning-50 text-2xl">📊</div>
                    <h3 class="mt-4 text-h3 text-gray-900">{{ __('Учёт с инфляцией') }}</h3>
                    <p class="mt-2 text-caption text-gray-500">
                        {{ __('Два баланса: номинальный и реальный. Видите, сколько ваши деньги стоят на самом деле.') }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-50 text-2xl">🤖</div>
                    <h3 class="mt-4 text-h3 text-gray-900">{{ __('AI-советы') }}</h3>
                    <p class="mt-2 text-caption text-gray-500">
                        {{ __('Персональные рекомендации на основе ваших расходов и инфляционных трендов.') }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-50 text-2xl">🎯</div>
                    <h3 class="mt-4 text-h3 text-gray-900">{{ __('Финансовые цели') }}</h3>
                    <p class="mt-2 text-caption text-gray-500">
                        {{ __('Ставьте цели с учётом инфляции. Видите реальный прогресс и необходимые взносы.') }}
                    </p>
                </div>

            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section class="bg-gray-50 py-16">
        <div class="mx-auto max-w-5xl px-4 sm:px-6">
            <h2 class="text-center text-h2 text-gray-900">{{ __('Тарифы') }}</h2>
            <div class="mt-12 grid gap-8 sm:grid-cols-2 sm:mx-auto sm:max-w-2xl">

                <div class="rounded-xl border border-gray-200 bg-white p-6">
                    <h3 class="text-h3 text-gray-900">Free</h3>
                    <p class="mt-1 text-number-md text-gray-900">0 ₽<span class="text-caption text-gray-500"> / {{ __('навсегда') }}</span></p>
                    <ul class="mt-6 space-y-3 text-sm text-gray-600">
                        <li class="flex items-start gap-2"><span class="text-success-500">✓</span> {{ __('50 операций в месяц') }}</li>
                        <li class="flex items-start gap-2"><span class="text-success-500">✓</span> {{ __('1 финансовая цель') }}</li>
                        <li class="flex items-start gap-2"><span class="text-success-500">✓</span> {{ __('Базовая аналитика') }}</li>
                        <li class="flex items-start gap-2"><span class="text-gray-300">—</span> {{ __('AI-советы') }}</li>
                        <li class="flex items-start gap-2"><span class="text-gray-300">—</span> {{ __('Экспорт PDF/Excel') }}</li>
                    </ul>
                    <div class="mt-6">
                        <a href="/login" class="block rounded-lg border border-primary-600 py-2 text-center text-sm font-medium text-primary-600 transition hover:bg-primary-50">
                            {{ __('Начать бесплатно') }}
                        </a>
                    </div>
                </div>

                <div class="relative rounded-xl border-2 border-premium-500 bg-white p-6">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-premium-500 px-3 py-0.5 text-small font-medium text-white">
                        Premium
                    </span>
                    <h3 class="text-h3 text-gray-900">Premium</h3>
                    <p class="mt-1 text-number-md text-gray-900">299 ₽<span class="text-caption text-gray-500"> / {{ __('мес') }}</span></p>
                    <ul class="mt-6 space-y-3 text-sm text-gray-600">
                        <li class="flex items-start gap-2"><span class="text-success-500">✓</span> {{ __('Безлимитные операции') }}</li>
                        <li class="flex items-start gap-2"><span class="text-success-500">✓</span> {{ __('До 10 целей') }}</li>
                        <li class="flex items-start gap-2"><span class="text-success-500">✓</span> {{ __('Полная аналитика') }}</li>
                        <li class="flex items-start gap-2"><span class="text-success-500">✓</span> {{ __('AI-советы ежедневно') }}</li>
                        <li class="flex items-start gap-2"><span class="text-success-500">✓</span> {{ __('Экспорт PDF/Excel') }}</li>
                    </ul>
                    <div class="mt-6">
                        <a href="/login" class="block rounded-lg bg-premium-500 py-2 text-center text-sm font-medium text-white transition hover:bg-premium-600">
                            {{ __('Попробовать Premium') }}
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="bg-primary-600 py-16">
        <div class="mx-auto max-w-5xl px-4 text-center sm:px-6">
            <h2 class="text-h2 text-white">{{ __('Начните контролировать свои финансы') }}</h2>
            <p class="mx-auto mt-4 max-w-xl text-primary-100">
                {{ __('Бесплатно. Без банковских карт. Регистрация за 30 секунд.') }}
            </p>
            <div class="mt-8">
                <a href="/login" class="inline-flex items-center rounded-lg bg-white px-6 py-3 text-base font-semibold text-primary-600 transition hover:bg-primary-50">
                    {{ __('Начать бесплатно') }}
                </a>
            </div>
        </div>
    </section>

</x-layouts.guest>
