<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>{{ $title ?? config('app.name', 'Contact Scan') }}</title>
    @include('partials.pwa-head')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>
<body class="font-sans antialiased bg-gray-100 text-gray-900">
    <div class="min-h-screen flex flex-col">
        <header class="px-4 py-4">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 font-semibold text-gray-900">
                <x-application-logo class="w-8 h-8" />
                {{ config('app.name', 'Contact Scan') }}
            </a>
        </header>
        <main class="flex-1 w-full max-w-md mx-auto px-4 pb-10">
            {{ $slot }}
        </main>
        <footer class="px-4 py-6 text-center text-xs text-gray-500">
            The owner's phone number and email are never shown. · <a href="{{ route('privacy') }}" class="underline">Privacy</a>
        </footer>
    </div>
</body>
</html>
