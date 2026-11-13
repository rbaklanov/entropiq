<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Entropiq') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=golos-text:400,500,600,700|literata:400,600,700" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js"></script>
    @fluxAppearance
    @fluxScripts
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">

    <div class="flex min-h-screen">

        {{-- Desktop sidebar (lg+) --}}
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-gray-200 bg-paper lg:flex">
            <div class="flex h-16 items-center px-6">
                <a href="/dashboard" aria-label="Entropiq"><x-wordmark class="text-2xl" /></a>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-4">
                <x-layouts.app.nav-link href="/dashboard" icon="home" :active="request()->is('dashboard')">
                    {{ __('common.nav_dashboard') }}
                </x-layouts.app.nav-link>
                <x-layouts.app.nav-link href="/transactions" icon="list" :active="request()->is('transactions*')">
                    {{ __('common.nav_transactions') }}
                </x-layouts.app.nav-link>
                <x-layouts.app.nav-link href="/transactions/create" icon="plus-circle" :active="request()->is('transactions/create')">
                    {{ __('common.nav_add') }}
                </x-layouts.app.nav-link>
                <x-layouts.app.nav-link href="/goals" icon="target" :active="request()->is('goals*')">
                    {{ __('common.nav_goals') }}
                </x-layouts.app.nav-link>
                <x-layouts.app.nav-link href="/analytics" icon="bar-chart" :active="request()->is('analytics*')">
                    {{ __('common.nav_analytics') }}
                </x-layouts.app.nav-link>
                <x-layouts.app.nav-link href="/advice" icon="lightbulb" :active="request()->is('advice*')">
                    {{ __('common.nav_advice') }}
                </x-layouts.app.nav-link>
            </nav>

            <div class="border-t border-gray-200 p-3">
                <x-layouts.app.nav-link href="/settings" icon="settings" :active="request()->is('settings*')">
                    {{ __('common.nav_settings') }}
                </x-layouts.app.nav-link>
            </div>
        </aside>

        {{-- Main content --}}
        <div class="flex min-w-0 flex-1 flex-col lg:pl-64">

            {{-- Top bar (mobile) --}}
            <header class="sticky top-0 z-20 flex h-14 items-center justify-between border-b border-gray-200 bg-paper px-4 lg:hidden">
                <a href="/dashboard" aria-label="Entropiq"><x-wordmark class="text-2xl" /></a>
            </header>

            {{-- Page content --}}
            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8 pb-20 lg:pb-6">
                {{ $slot }}
            </main>

        </div>

    </div>

    {{-- Mobile bottom navigation (<lg) --}}
    @php
        $moreLinks = [
            ['href' => route('analytics'), 'icon' => 'bar-chart', 'label' => __('common.nav_analytics'), 'active' => request()->is('analytics*')],
            ['href' => route('advice.index'), 'icon' => 'lightbulb', 'label' => __('common.nav_advice'), 'active' => request()->is('advice*')],
            ['href' => route('settings.subscription'), 'icon' => 'credit-card', 'label' => __('common.settings_subscription'), 'active' => request()->is('settings/subscription*')],
            ['href' => route('settings.index'), 'icon' => 'settings', 'label' => __('common.nav_settings'), 'active' => request()->is('settings') || request()->is('settings/profile*')],
        ];
        $moreActive = collect($moreLinks)->contains('active', true);
    @endphp
    <div
        x-data="{ moreOpen: false }"
        x-on:keydown.escape.window="moreOpen = false"
        x-on:open-more-menu.window="moreOpen = true"
        class="lg:hidden"
    >
        <div
            x-show="moreOpen"
            x-cloak
            x-transition.opacity
            x-on:click="moreOpen = false"
            class="fixed inset-0 z-40 bg-black/50"
        ></div>

        <div
            id="mobile-more-menu"
            x-show="moreOpen"
            x-cloak
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-full"
            x-transition:enter-end="translate-y-0"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="translate-y-0"
            x-transition:leave-end="translate-y-full"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('common.nav_more') }}"
            class="fixed inset-x-0 bottom-0 z-50 rounded-t-2xl border-t border-gray-200 bg-white px-3 pb-[calc(env(safe-area-inset-bottom)+1rem)] pt-2"
        >
            <div class="mx-auto mb-2 h-1 w-10 rounded-full bg-gray-200" aria-hidden="true"></div>
            <nav class="space-y-1">
                @foreach($moreLinks as $link)
                    <a
                        href="{{ $link['href'] }}"
                        @if($link['active']) aria-current="page" @endif
                        @class([
                            'flex min-h-12 items-center gap-3 rounded-md px-3 text-[15px] transition-colors hover:bg-gray-100',
                            'font-semibold text-gray-900' => $link['active'],
                            'text-gray-700' => ! $link['active'],
                        ])
                    >
                        <x-dynamic-component :component="'icons.' . $link['icon']" class="h-5 w-5 text-gray-500" />
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>
            <button
                type="button"
                x-on:click="moreOpen = false"
                class="mt-2 flex min-h-12 w-full items-center justify-center rounded-md text-[15px] text-gray-500 transition-colors hover:bg-gray-100"
            >
                {{ __('common.close') }}
            </button>
        </div>
    </div>

    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white lg:hidden">
        <div class="flex h-16 items-center justify-around">
            <x-layouts.app.bottom-nav-item href="/dashboard" icon="home" :active="request()->is('dashboard')">
                {{ __('common.nav_dashboard') }}
            </x-layouts.app.bottom-nav-item>
            <x-layouts.app.bottom-nav-item href="/transactions" icon="list" :active="request()->is('transactions') || request()->is('transactions/list*')">
                {{ __('common.nav_transactions') }}
            </x-layouts.app.bottom-nav-item>
            <x-layouts.app.bottom-nav-item href="/transactions/create" icon="plus-circle" :active="request()->is('transactions/create')" accent>
                {{ __('common.nav_add') }}
            </x-layouts.app.bottom-nav-item>
            <x-layouts.app.bottom-nav-item href="/goals" icon="target" :active="request()->is('goals*')">
                {{ __('common.nav_goals') }}
            </x-layouts.app.bottom-nav-item>
            <button
                type="button"
                x-data
                x-on:click="$dispatch('open-more-menu')"
                aria-haspopup="dialog"
                aria-controls="mobile-more-menu"
                @class([
                    'flex min-h-14 min-w-14 flex-col items-center justify-center gap-0.5 text-[11px] transition-colors',
                    'font-semibold text-gray-900' => $moreActive,
                    'text-gray-500' => ! $moreActive,
                ])
            >
                <x-icons.menu class="h-5 w-5" />
                <span>{{ __('common.nav_more') }}</span>
            </button>
        </div>
    </nav>

</body>
</html>
