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
    @fluxAppearance
    @fluxScripts
</head>
<body class="min-h-screen bg-paper font-sans text-gray-900 antialiased">

    <header class="fixed top-0 z-50 w-full border-b border-gray-200 bg-paper">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="/" aria-label="Entropiq"><x-wordmark class="text-2xl" /></a>
            <nav aria-label="{{ __('landing.features_title') }}" class="hidden items-center gap-7 sm:flex">
                <a href="{{ route('landing') }}#features" class="text-[15px] text-gray-500 transition-colors hover:text-gray-900">{{ __('landing.features_title') }}</a>
                <a href="{{ route('landing') }}#pricing" class="text-[15px] text-gray-500 transition-colors hover:text-gray-900">{{ __('landing.nav_pricing') }}</a>
                <a href="{{ route('faq') }}" class="text-[15px] text-gray-500 transition-colors hover:text-gray-900">{{ __('landing.footer_faq') }}</a>
            </nav>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-0.5 rounded-md border border-gray-200 p-0.5">
                    <a href="{{ route('locale.switch', 'ru') }}" @class([
                        'rounded px-2.5 py-1 text-xs font-medium transition-colors',
                        'bg-gray-900 text-white' => app()->getLocale() === 'ru',
                        'text-gray-500 hover:text-gray-900' => app()->getLocale() !== 'ru',
                    ])>RU</a>
                    <a href="{{ route('locale.switch', 'en') }}" @class([
                        'rounded px-2.5 py-1 text-xs font-medium transition-colors',
                        'bg-gray-900 text-white' => app()->getLocale() === 'en',
                        'text-gray-500 hover:text-gray-900' => app()->getLocale() !== 'en',
                    ])>EN</a>
                </div>
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex min-h-11 items-center rounded-md bg-primary-600 px-5 text-sm font-semibold text-white transition-colors hover:bg-primary-700">
                        {{ __('common.nav_dashboard') }}
                    </a>
                @else
                    <a href="/login" class="inline-flex min-h-11 items-center rounded-md bg-primary-600 px-5 text-sm font-semibold text-white transition-colors hover:bg-primary-700">
                        {{ __('auth.send_code') }}
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main class="pt-[69px]">
        {{ $slot }}
    </main>

    <footer class="border-t border-gray-200 bg-paper">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
            <div class="grid gap-8 sm:grid-cols-3">
                <div>
                    <a href="/" aria-label="Entropiq"><x-wordmark class="text-xl" /></a>
                    <p class="mt-2 text-sm text-gray-500">{{ __('common.tagline') }}</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900">{{ __('landing.footer_support') }}</h4>
                    <nav class="mt-3 flex flex-col gap-2">
                        <a href="{{ route('faq') }}" class="text-sm text-gray-500 transition-colors hover:text-gray-900">{{ __('landing.footer_faq') }}</a>
                        <a href="mailto:{{ App\Support\Seller::email() }}" class="text-sm text-gray-500 transition-colors hover:text-gray-900">{{ App\Support\Seller::email() }}</a>
                        @if(filled(config('app.seller.phone')))
                            <a href="tel:{{ preg_replace('/[^+\d]/', '', config('app.seller.phone')) }}" class="text-sm text-gray-500 transition-colors hover:text-gray-900">{{ config('app.seller.phone') }}</a>
                        @endif
                    </nav>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900">{{ __('landing.footer_terms') }}</h4>
                    <nav class="mt-3 flex flex-col gap-2">
                        <a href="{{ route('offer') }}" class="text-sm text-gray-500 transition-colors hover:text-gray-900">{{ __('landing.footer_offer') }}</a>
                        <a href="{{ route('terms') }}" class="text-sm text-gray-500 transition-colors hover:text-gray-900">{{ __('landing.footer_terms') }}</a>
                        <a href="{{ route('privacy') }}" class="text-sm text-gray-500 transition-colors hover:text-gray-900">{{ __('landing.footer_privacy') }}</a>
                        <a href="{{ route('consent') }}" class="text-sm text-gray-500 transition-colors hover:text-gray-900">{{ __('landing.footer_consent') }}</a>
                    </nav>
                </div>
            </div>
            <div class="mt-10 space-y-2 border-t border-gray-200 pt-6">
                @if(App\Support\Seller::hasRequisites())
                    <p class="text-sm text-gray-500">{{ App\Support\Seller::requisites() }}</p>
                @endif
                <p class="text-sm text-gray-500">&copy; {{ date('Y') }} Entropiq. All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>
</html>
