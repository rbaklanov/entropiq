<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Entropiq') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @fluxAppearance
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">

    <div class="flex min-h-screen">

        {{-- Desktop sidebar (lg+) --}}
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-gray-200 bg-white lg:flex">
            <div class="flex h-16 items-center px-6">
                <a href="/dashboard" class="text-h3 text-primary-600">Entropiq</a>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-4">
                <x-layouts.app.nav-link href="/dashboard" icon="home" :active="request()->is('dashboard')">
                    {{ __('Дашборд') }}
                </x-layouts.app.nav-link>
                <x-layouts.app.nav-link href="/transactions" icon="list" :active="request()->is('transactions*')">
                    {{ __('Операции') }}
                </x-layouts.app.nav-link>
                <x-layouts.app.nav-link href="/transactions/create" icon="plus-circle" :active="request()->is('transactions/create')">
                    {{ __('Добавить') }}
                </x-layouts.app.nav-link>
                <x-layouts.app.nav-link href="/goals" icon="target" :active="request()->is('goals*')">
                    {{ __('Цели') }}
                </x-layouts.app.nav-link>
                <x-layouts.app.nav-link href="/analytics" icon="bar-chart" :active="request()->is('analytics*')">
                    {{ __('Аналитика') }}
                </x-layouts.app.nav-link>
                <x-layouts.app.nav-link href="/advice" icon="lightbulb" :active="request()->is('advice*')">
                    {{ __('Советы') }}
                </x-layouts.app.nav-link>
            </nav>

            <div class="border-t border-gray-200 p-3">
                <x-layouts.app.nav-link href="/settings" icon="settings" :active="request()->is('settings*')">
                    {{ __('Настройки') }}
                </x-layouts.app.nav-link>
            </div>
        </aside>

        {{-- Main content --}}
        <div class="flex flex-1 flex-col lg:pl-64">

            {{-- Top bar (mobile) --}}
            <header class="sticky top-0 z-20 flex h-14 items-center justify-between border-b border-gray-200 bg-white px-4 lg:hidden">
                <a href="/dashboard" class="text-h3 text-primary-600">Entropiq</a>
            </header>

            {{-- Page content --}}
            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8 pb-20 lg:pb-6">
                {{ $slot }}
            </main>

        </div>

    </div>

    {{-- Mobile bottom navigation (<lg) --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white lg:hidden">
        <div class="flex h-16 items-center justify-around">
            <x-layouts.app.bottom-nav-item href="/dashboard" icon="home" :active="request()->is('dashboard')">
                {{ __('Главная') }}
            </x-layouts.app.bottom-nav-item>
            <x-layouts.app.bottom-nav-item href="/transactions" icon="list" :active="request()->is('transactions') || request()->is('transactions/list*')">
                {{ __('Операции') }}
            </x-layouts.app.bottom-nav-item>
            <x-layouts.app.bottom-nav-item href="/transactions/create" icon="plus-circle" :active="request()->is('transactions/create')" accent>
                {{ __('Добавить') }}
            </x-layouts.app.bottom-nav-item>
            <x-layouts.app.bottom-nav-item href="/goals" icon="target" :active="request()->is('goals*')">
                {{ __('Цели') }}
            </x-layouts.app.bottom-nav-item>
            <x-layouts.app.bottom-nav-item href="/settings" icon="menu" :active="request()->is('settings*') || request()->is('analytics*') || request()->is('advice*')">
                {{ __('Ещё') }}
            </x-layouts.app.bottom-nav-item>
        </div>
    </nav>

</body>
</html>
