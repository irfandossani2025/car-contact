<?php

namespace App\Http\Controllers;

use App\Services\TelegramNotifier;
use App\Services\WebPushNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Owner's alert channel settings: web push devices, Telegram, email. */
class NotificationController extends Controller
{
    public function subscribePush(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:2000'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'in:aesgcm,aes128gcm'],
        ]);

        $request->user()->pushSubscriptions()->updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function unsubscribePush(Request $request): JsonResponse
    {
        $endpoint = (string) $request->input('endpoint');
        $request->user()->pushSubscriptions()->where('endpoint_hash', hash('sha256', $endpoint))->delete();

        return response()->json(['ok' => true]);
    }

    public function testPush(Request $request, WebPushNotifier $push): RedirectResponse
    {
        $sent = $push->sendTest($request->user());

        return back()->with('status', $sent ? "Test notification sent to {$sent} device(s)." : 'No devices received it. Turn on alerts on your phone first.');
    }

    public function removeAllPush(Request $request): RedirectResponse
    {
        $request->user()->pushSubscriptions()->delete();

        return back()->with('status', 'All devices removed from phone notifications.');
    }

    public function connectTelegram(Request $request, TelegramNotifier $telegram): RedirectResponse
    {
        abort_unless($telegram->isConfigured(), 404);

        $user = $request->user();
        $token = Str::random(32);
        $user->forceFill(['telegram_link_token' => $token])->save();

        return redirect()->away('https://t.me/'.config('contactscan.telegram.bot_username').'?start='.$token);
    }

    public function disconnectTelegram(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['telegram_chat_id' => null, 'telegram_link_token' => null])->save();

        return back()->with('status', 'Telegram disconnected.');
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $request->user()->update(['notify_email' => $request->boolean('notify_email')]);

        return back()->with('status', 'Email preference saved.');
    }
}
