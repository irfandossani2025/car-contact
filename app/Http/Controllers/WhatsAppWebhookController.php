<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\User;
use App\Services\WhatsAppNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Meta WhatsApp Cloud API webhook: account linking ("CONNECT CODE") and quick-reply taps. */
class WhatsAppWebhookController extends Controller
{
    /** Meta calls this once when you save the webhook URL. */
    public function verify(Request $request): Response
    {
        $token = (string) config('contactscan.whatsapp.verify_token');
        if ($token !== '' && $request->query('hub_mode') === 'subscribe'
            && hash_equals($token, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'));
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request, WhatsAppNotifier $wa): Response
    {
        $secret = (string) config('contactscan.whatsapp.app_secret');
        $signature = (string) $request->header('X-Hub-Signature-256');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);
        abort_if($secret === '' || ! hash_equals($expected, $signature), 403);

        foreach ((array) $request->input('entry', []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                foreach ((array) data_get($change, 'value.messages', []) as $message) {
                    $this->handle($message, $wa);
                }
            }
        }

        return response('ok');
    }

    private function handle(array $message, WhatsAppNotifier $wa): void
    {
        $from = '+'.ltrim((string) ($message['from'] ?? ''), '+');
        $type = $message['type'] ?? '';

        // Template quick-reply tap, or interactive button reply.
        $payload = match ($type) {
            'button' => data_get($message, 'button.payload'),
            'interactive' => data_get($message, 'interactive.button_reply.id'),
            default => null,
        };
        if ($payload) {
            $this->handleReply((string) $payload, $from, $wa);

            return;
        }

        if ($type !== 'text') {
            return;
        }

        $text = strtoupper(trim((string) data_get($message, 'text.body')));

        if (preg_match('/^CONNECT\s+([A-Z0-9]{6,12})$/', $text, $m)) {
            $user = User::where('whatsapp_link_code', $m[1])->first();
            if (! $user) {
                $wa->sendText($from, 'That code is not valid. Open your Contact Scan dashboard and tap "Connect WhatsApp" again.');

                return;
            }
            User::where('whatsapp_number', $from)->where('id', '!=', $user->id)
                ->update(['whatsapp_number' => null, 'notify_whatsapp' => false]);
            $user->forceFill(['whatsapp_number' => $from, 'whatsapp_link_code' => null, 'notify_whatsapp' => true])->save();
            $wa->sendText($from, "✅ Connected! You'll get a WhatsApp message here when someone scans your car's QR code. Send STOP to disconnect.");

            return;
        }

        if ($text === 'STOP') {
            User::where('whatsapp_number', $from)->update(['whatsapp_number' => null, 'notify_whatsapp' => false]);
            $wa->sendText($from, 'Disconnected. You will no longer get car alerts on WhatsApp.');

            return;
        }

        $wa->sendText($from, 'This number only sends Contact Scan car alerts. Use the buttons under an alert to reply.');
    }

    private function handleReply(string $payload, string $from, WhatsAppNotifier $wa): void
    {
        $parts = explode(':', $payload);
        if (count($parts) !== 3 || $parts[0] !== 'r' || ! array_key_exists($parts[2], Alert::REPLIES)) {
            return;
        }

        $alert = Alert::with('vehicle.user')->find((int) $parts[1]);
        if (! $alert || $alert->vehicle->user->whatsapp_number !== $from) {
            return;
        }

        $alert->setReply($parts[2]);
        $wa->sendText($from, '✅ Sent: "'.Alert::REPLIES[$parts[2]].'". They can see it on their screen now.');
    }
}
