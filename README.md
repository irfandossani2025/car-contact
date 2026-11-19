# Contact Scan

A QR sticker for your car. When you're blocking someone (or left your lights on), they scan it and you get an alert instantly. Neither side ever sees the other's phone number or email.

**Free to run:** alerts go out by web push (VAPID), a Telegram bot, and email, with no paid SMS.

## How it works
- Every new account gets a car and a **printable sticker PDF** (big windshield sticker, 2 small ones, and an instructions page).
- The scanner opens `/t/{CODE}`. No app, no login. They pick a reason, add an optional note and send.
- The owner gets a phone notification, Telegram message and/or email, and replies in one tap ("On my way, 5 min", "Moved"…).
- The scanner's page updates live with the reply.
- Abuse protection: 2-minute cooldown per car, 6 alerts per hour per sender, honeypot, optional Cloudflare Turnstile, owner can block senders, pause alerts, or switch to a new QR code.

## Stack
Laravel 12 · Blade + Alpine + Tailwind (Breeze) · MySQL (SQLite locally) · `minishlink/web-push` · `barryvdh/laravel-dompdf` · `chillerlan/php-qrcode`

## Local development
```bash
composer install && npm install
```
```bash
cp .env.example .env && php artisan key:generate && php artisan contactscan:vapid
```
```bash
php artisan migrate && npm run build && php artisan serve
```
Emails go to `storage/logs/laravel.log` locally (`MAIL_MAILER=log`).

```bash
php artisan test
```

## Deploying
See **[DEPLOY.md](DEPLOY.md)** (Plesk, no SSH, Laravel Toolkit, git pull).

## Key files
- `app/Http/Controllers/ScanController.php`: public scan page, sending, live status
- `app/Http/Controllers/OwnerAlertController.php`: one-tap owner replies (signed links)
- `app/Http/Controllers/TelegramWebhookController.php`: Telegram linking and reply buttons
- `app/Services/AlertNotifier.php`: sends over push, Telegram and email
- `resources/views/pdf/sticker.blade.php`: printable sticker and instructions
- `public/sw.js`: service worker for push notifications
