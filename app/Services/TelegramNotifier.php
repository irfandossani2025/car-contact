<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    public function isConfigured(): bool
    {
        return filled(config('contactscan.telegram.bot_token')) && filled(config('contactscan.telegram.bot_username'));
    }

    public function sendAlert(User $user, Alert $alert): bool
    {
        if (! $this->isConfigured() || ! $user->hasTelegram()) {
            return false;
        }

        $text = $alert->reasonEmoji().' <b>'.e($alert->vehicle->name).': '.e($alert->reasonLabel()).'</b>';
        if ($alert->message) {
            $text .= "\n\n“".e($alert->message).'”';
        }
        $text .= "\n\nSomeone scanned your car's QR code. Tap a reply — they'll see it instantly.";

        return $this->sendMessage($user->telegram_chat_id, $text, $this->replyKeyboard($alert));
    }

    public function replyKeyboard(Alert $alert): array
    {
        $buttons = [];
        foreach (Alert::REPLIES as $key => $label) {
            $buttons[] = [['text' => $label, 'callback_data' => "r:{$alert->id}:{$key}"]];
        }

        return ['inline_keyboard' => $buttons];
    }

    public function sendMessage(string $chatId, string $html, ?array $replyMarkup = null): bool
    {
        $body = ['chat_id' => $chatId, 'text' => $html, 'parse_mode' => 'HTML', 'disable_web_page_preview' => true];
        if ($replyMarkup) {
            $body['reply_markup'] = $replyMarkup;
        }

        return $this->call('sendMessage', $body);
    }

    public function call(string $method, array $body): bool
    {
        try {
            $response = Http::timeout(8)->post($this->apiUrl($method), $body);
            if (! $response->successful()) {
                Log::warning('Telegram API error', ['method' => $method, 'status' => $response->status(), 'body' => $response->body()]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('Telegram API exception', ['method' => $method, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function apiUrl(string $method): string
    {
        return 'https://api.telegram.org/bot'.config('contactscan.telegram.bot_token').'/'.$method;
    }
}
