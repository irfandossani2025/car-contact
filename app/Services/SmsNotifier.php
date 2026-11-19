<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/** Sends SMS through BulkSMS (https://www.bulksms.com/developer/json/v1/). */
class SmsNotifier
{
    private const API_URL = 'https://api.bulksms.com/v1/messages';

    public function isConfigured(): bool
    {
        return $this->authHeader() !== null;
    }

    public function sendAlert(User $user, Alert $alert): bool
    {
        if (! $this->isConfigured() || ! $user->canReceiveSms()) {
            return false;
        }

        // Cost guard: cap SMS per owner per day.
        $key = 'sms-owner:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, (int) config('contactscan.sms.daily_limit_per_user'))) {
            Log::info('SMS daily limit reached', ['user' => $user->id]);

            return false;
        }
        RateLimiter::hit($key, 86400);

        $car = $this->plain($alert->vehicle->name, 25);
        $text = config('app.name').": someone needs you to move {$car} ({$alert->reasonLabel()}).";
        if ($alert->message) {
            $text .= ' "'.$this->plain($alert->message, 60).'"';
        }
        $text .= ' Reply: '.$alert->shortOwnerUrl();

        return $this->send($user->phone, $text);
    }

    public function sendVerificationCode(string $phone, string $code): bool
    {
        return $this->send($phone, config('app.name')." code: {$code}. It expires in 10 minutes.");
    }

    public function send(string $to, string $body): bool
    {
        $auth = $this->authHeader();
        if (! $auth) {
            return false;
        }

        $payload = ['to' => $to, 'body' => $body];
        if ($from = config('contactscan.sms.from')) {
            $payload['from'] = $from;
        }
        // Non-GSM characters (e.g. Arabic, emoji) need Unicode encoding.
        if (preg_match('/[^\x0A\x0D\x20-\x7E]/', $body)) {
            $payload['encoding'] = 'UNICODE';
        }

        try {
            $response = Http::withHeaders(['Authorization' => $auth])
                ->acceptJson()
                ->timeout(10)
                ->post(self::API_URL.'?auto-unicode=true', $payload);

            if (! $response->successful()) {
                Log::warning('BulkSMS error', ['status' => $response->status(), 'body' => $response->body()]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('BulkSMS exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /** Accepts either Token ID + Secret, or the ready-made Basic Auth string. */
    private function authHeader(): ?string
    {
        $id = config('contactscan.sms.token_id');
        $secret = config('contactscan.sms.token_secret');
        if (filled($id) && filled($secret)) {
            return 'Basic '.base64_encode($id.':'.$secret);
        }

        $basic = trim((string) config('contactscan.sms.basic_auth'));
        if ($basic !== '') {
            return str_starts_with(strtolower($basic), 'basic ') ? $basic : 'Basic '.$basic;
        }

        return null;
    }

    private function plain(string $text, int $max): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text));

        return mb_strlen($text) > $max ? mb_substr($text, 0, $max - 3).'...' : $text;
    }

    /** Normalise to +<digits>. Returns null if it doesn't look like an international number. */
    public static function normalizePhone(?string $input): ?string
    {
        $digits = preg_replace('/[^\d+]/', '', (string) $input);
        if (str_starts_with($digits, '00')) {
            $digits = '+'.substr($digits, 2);
        }
        if (! str_starts_with($digits, '+') && ($cc = config('contactscan.sms.default_country_code'))) {
            $digits = '+'.ltrim($cc, '+').ltrim($digits, '0');
        }

        return preg_match('/^\+[1-9]\d{7,14}$/', $digits) ? $digits : null;
    }
}
