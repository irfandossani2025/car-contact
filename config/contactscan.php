<?php

return [

    // Minimum seconds between two alerts for the same car (stops spamming one owner).
    'vehicle_cooldown_seconds' => (int) env('CS_VEHICLE_COOLDOWN', 120),

    // Max alerts a single sender (IP) can send per hour, across all cars.
    'sender_hourly_limit' => (int) env('CS_SENDER_HOURLY_LIMIT', 6),

    // Free web push (VAPID). Generate with: php artisan contactscan:vapid
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', env('APP_URL')),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    // Free Telegram bot. Create one with @BotFather.
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    // Free Cloudflare Turnstile (bot check). Leave blank to disable.
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],
];
