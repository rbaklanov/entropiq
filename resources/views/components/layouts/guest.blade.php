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
    @fluxScripts
</head>
<body class="min-h-screen bg-white font-sans text-gray-900 antialiased">

    <header class="border-b border-gray-100">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="/" class="text-h3 text-primary-600">Entropiq</a>
            <a href="/login" class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-700">
                {{ __('Войти') }}
            </a>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="border-t border-gray-100 bg-gray-50">
        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <p class="text-caption text-gray-500">&copy; {{ date('Y') }} Entropiq</p>
                <nav class="flex gap-6">
                    <a href="#" class="text-caption text-gray-500 transition hover:text-gray-700">{{ __('Политика конфиденциальности') }}</a>
                    <a href="#" class="text-caption text-gray-500 transition hover:text-gray-700">{{ __('Пользовательское соглашение') }}</a>
                    <a href="#" class="text-caption text-gray-500 transition hover:text-gray-700">{{ __('FAQ') }}</a>
                </nav>
            </div>
        </div>
    </footer>

</body>
</html>
