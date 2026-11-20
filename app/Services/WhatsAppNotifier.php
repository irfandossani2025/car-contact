<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * WhatsApp via Meta's Cloud API.
 * Alerts use an approved "utility" template with three quick-reply buttons;
 * the owner's tap comes back through the webhook (see WhatsAppWebhookController).
 */
class WhatsAppNotifier
{
    /** Order must match the quick-reply buttons in the approved template. */
    public const TEMPLATE_REPLIES = ['coming_5', 'moved', 'cant'];

    public function isConfigured(): bool
    {
        return filled(config('contactscan.whatsapp.access_token'))
            && filled(config('contactscan.whatsapp.phone_number_id'))
            && filled(config('contactscan.whatsapp.business_number'));
    }

    public function sendAlert(User $user, Alert $alert): bool
    {
        if (! $this->isConfigured() || ! $user->canReceiveWhatsApp()) {
            return false;
        }

        $key = 'wa-owner:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, (int) config('contactscan.whatsapp.daily_limit_per_user'))) {
            Log::info('WhatsApp daily limit reached', ['user' => $user->id]);

            return false;
        }
        RateLimiter::hit($key, 86400);

        return $this->sendTemplate($user->whatsapp_number, [
            $this->param($alert->vehicle->name, 60),
            $this->param($alert->reasonLabel(), 60),
            $this->param($alert->message ?: '-', 200),
        ], array_map(fn ($r) => "r:{$alert->id}:{$r}", self::TEMPLATE_REPLIES));
    }

    public function sendTemplate(string $to, array $bodyParams, array $buttonPayloads = []): bool
    {
        $components = [[
            'type' => 'body',
            'parameters' => array_map(fn ($t) => ['type' => 'text', 'text' => $t], $bodyParams),
        ]];
        foreach ($buttonPayloads as $i => $payload) {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'quick_reply',
                'index' => (string) $i,
                'parameters' => [['type' => 'payload', 'payload' => $payload]],
            ];
        }

        return $this->post([
            'messaging_product' => 'whatsapp',
            'to' => ltrim($to, '+'),
            'type' => 'template',
            'template' => [
                'name' => config('contactscan.whatsapp.template_name'),
                'language' => ['code' => config('contactscan.whatsapp.template_language')],
                'components' => $components,
            ],
        ]);
    }

    /** Free-form text. Only delivered within 24h of the owner's last message/tap. */
    public function sendText(string $to, string $text): bool
    {
        return $this->post([
            'messaging_product' => 'whatsapp',
            'to' => ltrim($to, '+'),
            'type' => 'text',
            'text' => ['body' => $text, 'preview_url' => false],
        ]);
    }

    public function connectUrl(string $code): string
    {
        $number = preg_replace('/\D/', '', (string) config('contactscan.whatsapp.business_number'));

        return 'https://wa.me/'.$number.'?text='.rawurlencode('CONNECT '.$code);
    }

    private function post(array $payload): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            config('contactscan.whatsapp.graph_version'),
            config('contactscan.whatsapp.phone_number_id')
        );

        try {
            $response = Http::withToken(config('contactscan.whatsapp.access_token'))
                ->acceptJson()->timeout(10)->post($url, $payload);

            if (! $response->successful()) {
                Log::warning('WhatsApp API error', ['status' => $response->status(), 'body' => $response->body()]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('WhatsApp API exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /** Template parameters may not contain newlines, tabs or 4+ spaces in a row. */
    private function param(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));

        return mb_strlen($text) > $max ? mb_substr($text, 0, $max - 3).'...' : $text;
    }
}
