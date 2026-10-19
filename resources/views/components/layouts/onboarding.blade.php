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
</head>
<body class="min-h-screen bg-paper font-sans text-gray-900 antialiased lg:bg-gray-100">

    {{-- Mobile top bar --}}
    <header class="sticky top-0 z-20 flex h-14 items-center justify-center bg-paper lg:hidden">
        <x-wordmark class="text-xl" />
    </header>

    {{ $slot }}

</body>
</html>
