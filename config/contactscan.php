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

    // BulkSMS (paid per message). Use Token ID + Secret, or the Basic Auth string.
    'sms' => [
        'token_id' => env('BULKSMS_TOKEN_ID'),
        'token_secret' => env('BULKSMS_TOKEN_SECRET'),
        'basic_auth' => env('BULKSMS_BASIC_AUTH'),
        'from' => env('BULKSMS_FROM'),
        // Used when an owner types a local number without "+country code", e.g. 971
        'default_country_code' => env('CS_SMS_DEFAULT_COUNTRY_CODE'),
        'daily_limit_per_user' => (int) env('CS_SMS_DAILY_LIMIT', 10),
    ],

    // WhatsApp via Meta Cloud API (paid per alert; connecting and replies are free).
    'whatsapp' => [
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        // Your WhatsApp business number in international format, e.g. +97150xxxxxxx (for the Connect link)
        'business_number' => env('WHATSAPP_BUSINESS_NUMBER'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'template_name' => env('WHATSAPP_TEMPLATE', 'car_alert'),
        'template_language' => env('WHATSAPP_TEMPLATE_LANG', 'en'),
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v23.0'),
        'daily_limit_per_user' => (int) env('CS_WHATSAPP_DAILY_LIMIT', 10),
    ],

    // Free Cloudflare Turnstile (bot check). Leave blank to disable.
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],
];
