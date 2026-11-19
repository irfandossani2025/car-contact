<?php

namespace App\Services;

use App\Mail\CarAlertMail;
use App\Models\Alert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends an alert to the owner over every channel they have set up (push, Telegram, SMS, email).
 * Runs inside the request (no background worker needed on Plesk).
 */
class AlertNotifier
{
    public function __construct(
        private WebPushNotifier $push,
        private TelegramNotifier $telegram,
        private SmsNotifier $sms,
    ) {}

    public function notify(Alert $alert): array
    {
        $user = $alert->vehicle->user;
        $result = ['push' => 0, 'telegram' => false, 'sms' => false, 'email' => false];

        try {
            $result['push'] = $this->push->sendAlert($user, $alert);
        } catch (\Throwable $e) {
            Log::error('Push notify failed', ['alert' => $alert->id, 'error' => $e->getMessage()]);
        }

        try {
            $result['telegram'] = $this->telegram->sendAlert($user, $alert);
        } catch (\Throwable $e) {
            Log::error('Telegram notify failed', ['alert' => $alert->id, 'error' => $e->getMessage()]);
        }

        try {
            $result['sms'] = $this->sms->sendAlert($user, $alert);
        } catch (\Throwable $e) {
            Log::error('SMS notify failed', ['alert' => $alert->id, 'error' => $e->getMessage()]);
        }

        // Email always goes out if enabled, or if no instant channel reached the owner.
        if ($user->notify_email || (! $result['push'] && ! $result['telegram'] && ! $result['sms'])) {
            try {
                Mail::to($user)->send(new CarAlertMail($alert));
                $result['email'] = true;
            } catch (\Throwable $e) {
                Log::error('Email notify failed', ['alert' => $alert->id, 'error' => $e->getMessage()]);
            }
        }

        $alert->forceFill(['delivery' => $result])->save();

        return $result;
    }
}
