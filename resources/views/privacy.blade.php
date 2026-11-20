<x-public-layout title="Privacy Policy">
    <article class="bg-white rounded-2xl shadow-sm p-6 space-y-4 text-sm leading-relaxed text-gray-700">
        <h1 class="text-2xl font-bold text-gray-900">Privacy Policy</h1>
        <p class="text-gray-500">Last updated: {{ \Illuminate\Support\Carbon::parse('2026-11-20')->format('j F Y') }}</p>

        <p>{{ config('app.name') }} ("we") lets car owners receive anonymous alerts when someone scans the QR sticker on their car. This policy explains what we collect and why.</p>

        <h2 class="font-semibold text-gray-900 text-base">What we collect</h2>
        <ul class="list-disc pl-5 space-y-1">
            <li><strong>Car owners:</strong> name, email address, password (stored encrypted), car name and optional colour/plate, and, if you turn them on, your mobile number (for SMS), WhatsApp number, Telegram chat ID, or browser push subscription.</li>
            <li><strong>People who scan a sticker:</strong> the reason and optional note you send, and a one-way hashed (non-reversible) version of your IP address, used only to prevent spam and abuse. We do not ask for your name or contact details.</li>
        </ul>

        <h2 class="font-semibold text-gray-900 text-base">How we use it</h2>
        <ul class="list-disc pl-5 space-y-1">
            <li>To deliver alerts to the car owner and show the owner's reply to the person who scanned.</li>
            <li>To prevent abuse (rate limits and blocking).</li>
            <li>We never show the owner's phone number, WhatsApp number or email to the person who scans, and we never sell personal data.</li>
        </ul>

        <h2 class="font-semibold text-gray-900 text-base">Services we use to deliver alerts</h2>
        <p>Depending on the channels an owner turns on, alerts are sent through: Meta (WhatsApp Business Platform), BulkSMS (SMS), Telegram, your browser's push service (e.g. Google, Apple, Mozilla), and our email provider. They receive only what is needed to deliver the message.</p>

        <h2 class="font-semibold text-gray-900 text-base">How long we keep it</h2>
        <p>Alerts are deleted automatically after 90 days. Account data is kept until you delete your account, which you can do at any time from your profile page.</p>

        <h2 class="font-semibold text-gray-900 text-base">Your choices</h2>
        <p>You can turn off any alert channel, disconnect WhatsApp (send STOP) or Telegram (send /stop), change your QR code, or delete your account at any time.</p>

        <h2 class="font-semibold text-gray-900 text-base">Contact</h2>
        <p>Questions or requests: <a class="underline" href="mailto:support@techittechnologies.com">support@techittechnologies.com</a></p>
    </article>
</x-public-layout>
