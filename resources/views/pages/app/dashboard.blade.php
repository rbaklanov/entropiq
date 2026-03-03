<x-layouts.app title="Дашборд — Entropiq">

    <div class="space-y-6">
        <h1 class="text-h1">{{ __('Дашборд') }}</h1>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

            {{-- Balance card --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-caption text-gray-500">{{ __('Номинальный баланс') }}</p>
                <p class="mt-1 text-number-lg text-gray-900">0 ₽</p>
                <p class="mt-2 text-caption text-warning-500">{{ __('Реальный') }}: 0 ₽</p>
            </div>

            {{-- Income card --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-caption text-gray-500">{{ __('Доходы за месяц') }}</p>
                <p class="mt-1 text-number-md text-income">+0 ₽</p>
            </div>

            {{-- Expense card --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-caption text-gray-500">{{ __('Расходы за месяц') }}</p>
                <p class="mt-1 text-number-md text-expense">−0 ₽</p>
            </div>

        </div>

        {{-- Placeholder sections --}}
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="flex h-48 items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white">
                <p class="text-caption text-gray-400">{{ __('Последние операции') }}</p>
            </div>
            <div class="flex h-48 items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white">
                <p class="text-caption text-gray-400">{{ __('Финансовые цели') }}</p>
            </div>
        </div>
    </div>

</x-layouts.app>
