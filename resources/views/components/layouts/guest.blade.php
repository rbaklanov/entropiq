<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Entropiq') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @fluxAppearance
    @fluxScripts
</head>
<body class="min-h-screen bg-white font-sans text-gray-900 antialiased">

    <header class="fixed top-0 z-50 w-full border-b border-gray-100/80 bg-white/80 backdrop-blur-lg">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
            <a href="/" class="text-xl font-extrabold tracking-tight text-primary-600">Entropiq</a>
            <nav class="hidden items-center gap-6 sm:flex">
                <a href="#features" class="text-sm font-medium text-gray-600 transition hover:text-gray-900">{{ __('landing.features_title') }}</a>
                <a href="#pricing" class="text-sm font-medium text-gray-600 transition hover:text-gray-900">{{ __('landing.pricing_title') }}</a>
            </nav>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1 rounded-lg border border-gray-200 p-0.5">
                    <a href="{{ route('locale.switch', 'ru') }}" @class([
                        'rounded-md px-2.5 py-1 text-xs font-medium transition',
                        'bg-primary-600 text-white' => app()->getLocale() === 'ru',
                        'text-gray-500 hover:text-gray-700' => app()->getLocale() !== 'ru',
                    ])>RU</a>
                    <a href="{{ route('locale.switch', 'en') }}" @class([
                        'rounded-md px-2.5 py-1 text-xs font-medium transition',
                        'bg-primary-600 text-white' => app()->getLocale() === 'en',
                        'text-gray-500 hover:text-gray-700' => app()->getLocale() !== 'en',
                    ])>EN</a>
                </div>
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 hover:shadow-md">
                        {{ __('common.nav_dashboard') }}
                    </a>
                @else
                    <a href="/login" class="inline-flex items-center rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 hover:shadow-md">
                        {{ __('auth.send_code') }}
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main class="pt-14">
        {{ $slot }}
    </main>

    <footer class="border-t border-gray-100 bg-gray-50">
        <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
            <div class="grid gap-8 sm:grid-cols-3">
                <div>
                    <a href="/" class="text-lg font-extrabold tracking-tight text-primary-600">Entropiq</a>
                    <p class="mt-2 text-sm text-gray-500">{{ __('common.tagline') }}</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900">{{ __('landing.footer_support') }}</h4>
                    <nav class="mt-3 flex flex-col gap-2">
                        <a href="#" class="text-sm text-gray-500 transition hover:text-gray-700">{{ __('landing.footer_faq') }}</a>
                        <a href="mailto:support@entropiq.ru" class="text-sm text-gray-500 transition hover:text-gray-700">support@entropiq.ru</a>
                    </nav>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900">{{ __('landing.footer_terms') }}</h4>
                    <nav class="mt-3 flex flex-col gap-2">
                        <a href="{{ route('privacy') }}" class="text-sm text-gray-500 transition hover:text-gray-700">{{ __('landing.footer_privacy') }}</a>
                        <a href="{{ route('terms') }}" class="text-sm text-gray-500 transition hover:text-gray-700">{{ __('landing.footer_terms') }}</a>
                    </nav>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-200 pt-6 text-center">
                <p class="text-sm text-gray-400">&copy; {{ date('Y') }} Entropiq. All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>
</html>
