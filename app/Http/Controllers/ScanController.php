<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Vehicle;
use App\Services\AlertNotifier;
use App\Services\TurnstileVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Public pages used by the person who scans the QR code. No login. */
class ScanController extends Controller
{
    public function show(string $code, TurnstileVerifier $turnstile): View
    {
        $vehicle = $this->findVehicle($code);

        return view('scan.show', [
            'vehicle' => $vehicle,
            'reasons' => Alert::REASONS,
            'turnstileSiteKey' => $turnstile->enabled() ? config('contactscan.turnstile.site_key') : null,
        ]);
    }

    public function send(Request $request, string $code, TurnstileVerifier $turnstile, AlertNotifier $notifier): RedirectResponse
    {
        $vehicle = $this->findVehicle($code);
        abort_unless($vehicle->is_active, 403);

        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(Alert::REASONS))],
            'message' => ['nullable', 'string', 'max:280'],
        ]);

        // Honeypot: real people never fill this hidden field.
        if (filled($request->input('website'))) {
            return back()->withErrors(['reason' => 'Could not send. Please try again.']);
        }

        if (! $turnstile->verify($request->input('cf-turnstile-response'), $request->ip())) {
            return back()->withInput()->withErrors(['reason' => 'Please complete the "I am human" check.']);
        }

        $senderHash = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));

        if ($vehicle->user->blockedSenders()->where('sender_hash', $senderHash)->exists()) {
            return back()->withErrors(['reason' => 'The owner is not accepting alerts from this device.']);
        }

        $recent = $vehicle->alerts()
            ->where('created_at', '>=', now()->subSeconds(config('contactscan.vehicle_cooldown_seconds')))
            ->latest()->first();
        if ($recent) {
            if ($recent->sender_hash === $senderHash) {
                return redirect($recent->statusUrl());
            }

            return back()->withErrors(['reason' => 'The owner was alerted moments ago. Please wait a couple of minutes before sending again.']);
        }

        $limiterKey = 'scan-sender:'.$senderHash;
        if (RateLimiter::tooManyAttempts($limiterKey, config('contactscan.sender_hourly_limit'))) {
            return back()->withErrors(['reason' => 'Too many alerts sent from this device. Please try again later.']);
        }
        RateLimiter::hit($limiterKey, 3600);

        $alert = $vehicle->alerts()->create([
            'reason' => $data['reason'],
            'message' => $data['message'] ?? null,
            'sender_hash' => $senderHash,
        ]);

        $notifier->notify($alert);

        return redirect($alert->statusUrl());
    }

    public function status(string $token): View
    {
        $alert = Alert::where('public_token', $token)->with('vehicle')->firstOrFail();

        return view('scan.status', ['alert' => $alert]);
    }

    public function poll(string $token): JsonResponse
    {
        $alert = Alert::where('public_token', $token)->firstOrFail();

        return response()->json([
            'seen' => (bool) $alert->seen_at,
            'reply' => $alert->reply,
            'reply_label' => $alert->replyLabel(),
            'replied_ago' => $alert->replied_at?->diffForHumans(),
        ]);
    }

    private function findVehicle(string $code): Vehicle
    {
        $code = strtoupper(str_replace(['-', ' '], '', $code));

        return Vehicle::where('tag_code', $code)->with('user')->firstOrFail();
    }
}
