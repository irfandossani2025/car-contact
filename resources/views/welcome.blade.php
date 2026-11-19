<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Contact Scan') }}: get told when to move your car</title>
    <meta name="description" content="A free QR sticker for your car. Anyone can scan it to tell you to move. They never see your phone number.">
    @include('partials.pwa-head')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900">
    <header class="max-w-5xl mx-auto px-4 py-5 flex items-center justify-between">
        <a href="/" class="inline-flex items-center gap-2 font-bold text-lg">
            <x-application-logo class="w-9 h-9" /> {{ config('app.name', 'Contact Scan') }}
        </a>
        <nav class="flex items-center gap-3 text-sm font-semibold">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-lg bg-gray-900 text-white px-4 py-2">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="px-3 py-2">Log in</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-gray-900 text-white px-4 py-2">Get your free sticker</a>
            @endauth
        </nav>
    </header>

    <main>
        <section class="max-w-5xl mx-auto px-4 pt-10 pb-16 grid md:grid-cols-2 gap-10 items-center">
            <div>
                <p class="inline-block rounded-full bg-amber-100 text-amber-900 text-xs font-semibold px-3 py-1">100% free · no app needed</p>
                <h1 class="mt-4 text-4xl md:text-5xl font-extrabold leading-tight">Blocked in? <br>Scan. Alert. Done.</h1>
                <p class="mt-4 text-lg text-gray-600">Put a QR sticker on your car. If you're blocking someone or left your lights on, they scan it and you get an alert on your phone right away. <strong>They never see your phone number.</strong></p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="rounded-xl bg-amber-400 text-gray-900 font-bold px-6 py-3 shadow-sm">Create free account</a>
                    <form method="POST" action="{{ route('scan.find') }}" class="flex">
                        @csrf
                        <input name="code" placeholder="Have a code? ABCD-EFGH" class="rounded-l-xl border-gray-300 w-48 focus:border-gray-900 focus:ring-gray-900" maxlength="12">
                        <button class="rounded-r-xl bg-gray-900 text-white px-4 font-semibold">Go</button>
                    </form>
                </div>
            </div>
            <div class="mx-auto">
                <div class="rounded-3xl bg-gray-900 text-center text-white p-8 w-72 shadow-xl rotate-2">
                    <p class="text-2xl font-extrabold text-amber-300 leading-tight">Blocking you?<br>Scan to alert me.</p>
                    <div class="mt-5 bg-white rounded-xl p-3 inline-block">
                        <x-application-logo class="w-40 h-40" />
                    </div>
                    <p class="mt-4 font-mono tracking-widest">ABCD-EFGH</p>
                </div>
            </div>
        </section>

        <section class="bg-white border-y">
            <div class="max-w-5xl mx-auto px-4 py-14 grid md:grid-cols-3 gap-8">
                <div>
                    <div class="text-3xl">🖨️</div>
                    <h3 class="mt-2 font-bold text-lg">1. Print your sticker</h3>
                    <p class="text-gray-600 mt-1">Sign up and download your QR sticker with instructions. Stick it inside your windshield.</p>
                </div>
                <div>
                    <div class="text-3xl">📱</div>
                    <h3 class="mt-2 font-bold text-lg">2. Someone scans it</h3>
                    <p class="text-gray-600 mt-1">They pick a reason, like "Blocking my car" or "Lights on". No app, no sign-up.</p>
                </div>
                <div>
                    <div class="text-3xl">🏃</div>
                    <h3 class="mt-2 font-bold text-lg">3. You reply in one tap</h3>
                    <p class="text-gray-600 mt-1">Get a phone notification, Telegram message or email. Tap "On my way" and they see it right away.</p>
                </div>
            </div>
        </section>

        <section class="max-w-3xl mx-auto px-4 py-14 text-center">
            <h2 class="text-2xl font-bold">Private by design</h2>
            <p class="mt-3 text-gray-600">Your sticker has a random code, not your plate or phone number. Alerts are rate-limited, you can block senders, and you can pause alerts or switch to a new code at any time.</p>
        </section>
    </main>

    <footer class="text-center text-sm text-gray-500 py-8">© {{ date('Y') }} {{ config('app.name', 'Contact Scan') }}</footer>
</body>
</html>
