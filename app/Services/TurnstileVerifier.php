<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TurnstileVerifier
{
    public function enabled(): bool
    {
        return filled(config('contactscan.turnstile.site_key')) && filled(config('contactscan.turnstile.secret_key'));
    }

    public function verify(?string $token, ?string $ip): bool
    {
        if (! $this->enabled()) {
            return true;
        }
        if (! $token) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(8)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('contactscan.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]);

            return (bool) $response->json('success');
        } catch (\Throwable) {
            // If Cloudflare is unreachable, don't block real people; rate limits still apply.
            return true;
        }
    }
}
