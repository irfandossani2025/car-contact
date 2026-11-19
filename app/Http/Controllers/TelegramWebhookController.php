<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\User;
use App\Services\TelegramNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Receives updates from Telegram: account linking and one-tap replies. */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramNotifier $telegram): Response
    {
        $secret = (string) config('contactscan.telegram.webhook_secret');
        abort_if($secret === '' || ! hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')), 403);

        if ($callback = $request->input('callback_query')) {
            $this->handleCallback($callback, $telegram);
        } elseif ($message = $request->input('message')) {
            $this->handleMessage($message, $telegram);
        }

        return response('ok');
    }

    private function handleMessage(array $message, TelegramNotifier $telegram): void
    {
        $chatId = (string) data_get($message, 'chat.id');
        $text = trim((string) data_get($message, 'text', ''));

        if (str_starts_with($text, '/start')) {
            $token = trim(substr($text, 6));
            $user = $token !== '' ? User::where('telegram_link_token', $token)->first() : null;

            if ($user) {
                // One Telegram chat belongs to one account.
                User::where('telegram_chat_id', $chatId)->where('id', '!=', $user->id)
                    ->update(['telegram_chat_id' => null]);
                $user->forceFill(['telegram_chat_id' => $chatId, 'telegram_link_token' => null])->save();
                $telegram->sendMessage($chatId, "✅ <b>Connected!</b>\n\nYou'll get a message here whenever someone scans your car's QR code. Send /stop to disconnect.");
            } else {
                $telegram->sendMessage($chatId, 'To connect, open your Contact Scan dashboard and tap <b>Connect Telegram</b>.');
            }

            return;
        }

        if ($text === '/stop') {
            User::where('telegram_chat_id', $chatId)->update(['telegram_chat_id' => null]);
            $telegram->sendMessage($chatId, 'Disconnected. You will no longer get alerts here.');

            return;
        }

        $telegram->sendMessage($chatId, 'I only deliver car alerts. Use the buttons under an alert to reply.');
    }

    private function handleCallback(array $callback, TelegramNotifier $telegram): void
    {
        $chatId = (string) data_get($callback, 'message.chat.id');
        $parts = explode(':', (string) data_get($callback, 'data', ''));
        $answer = 'Something went wrong.';

        if (count($parts) === 3 && $parts[0] === 'r' && array_key_exists($parts[2], Alert::REPLIES)) {
            $alert = Alert::with('vehicle.user')->find((int) $parts[1]);

            if ($alert && $alert->vehicle->user->telegram_chat_id === $chatId) {
                $alert->setReply($parts[2]);
                $answer = 'Sent: '.Alert::REPLIES[$parts[2]];

                $telegram->call('editMessageText', [
                    'chat_id' => $chatId,
                    'message_id' => data_get($callback, 'message.message_id'),
                    'parse_mode' => 'HTML',
                    'text' => $alert->reasonEmoji().' <b>'.e($alert->vehicle->name).': '.e($alert->reasonLabel()).'</b>'
                        .($alert->message ? "\n\n“".e($alert->message).'”' : '')
                        ."\n\n✅ You replied: <i>".e(Alert::REPLIES[$parts[2]]).'</i>',
                    'reply_markup' => $telegram->replyKeyboard($alert),
                ]);
            }
        }

        $telegram->call('answerCallbackQuery', [
            'callback_query_id' => data_get($callback, 'id'),
            'text' => $answer,
        ]);
    }
}
