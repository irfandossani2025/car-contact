<?php

use App\Services\TelegramNotifier;
use Illuminate\Support\Facades\Artisan;
use Minishlink\WebPush\VAPID;

// Generate free web push keys. Run once (locally is fine) and paste the output into .env.
Artisan::command('contactscan:vapid', function () {
    $keys = VAPID::createVapidKeys();
    $this->info('Add these lines to your .env file:');
    $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
    $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
})->purpose('Generate VAPID keys for web push notifications');

// Point your Telegram bot at this site. Run from Plesk > Laravel Toolkit > Artisan after deploying.
Artisan::command('contactscan:telegram-webhook', function (TelegramNotifier $telegram) {
    if (! $telegram->isConfigured() || blank(config('contactscan.telegram.webhook_secret'))) {
        $this->error('Set TELEGRAM_BOT_TOKEN, TELEGRAM_BOT_USERNAME and TELEGRAM_WEBHOOK_SECRET in .env first.');

        return 1;
    }

    $url = route('telegram.webhook');
    $ok = $telegram->call('setWebhook', [
        'url' => $url,
        'secret_token' => config('contactscan.telegram.webhook_secret'),
        'allowed_updates' => ['message', 'callback_query'],
        'drop_pending_updates' => true,
    ]);

    $ok ? $this->info("Telegram webhook set to {$url}") : $this->error('Telegram refused the webhook. Check the bot token and that the site is on HTTPS.');

    return $ok ? 0 : 1;
})->purpose('Register the Telegram bot webhook');

// Housekeeping: delete alerts older than 90 days. Runs from the Plesk scheduled task.
Artisan::command('contactscan:prune', function () {
    $n = \App\Models\Alert::where('created_at', '<', now()->subDays(90))->delete();
    $this->info("Deleted {$n} old alerts.");
})->purpose('Delete alerts older than 90 days');

\Illuminate\Support\Facades\Schedule::command('contactscan:prune')->daily();
