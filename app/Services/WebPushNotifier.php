<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushNotifier
{
    public function isConfigured(): bool
    {
        return filled(config('contactscan.vapid.public_key')) && filled(config('contactscan.vapid.private_key'));
    }

    /** @return int number of devices the alert was delivered to */
    public function sendAlert(User $user, Alert $alert): int
    {
        $payload = [
            'title' => $alert->reasonEmoji().' '.$alert->vehicle->name.': '.$alert->reasonLabel(),
            'body' => $alert->message ?: 'Someone scanned your car\'s QR code. Tap to reply.',
            'url' => $alert->ownerUrl(),
            'tag' => 'alert-'.$alert->id,
            'actions' => [
                ['action' => 'coming_5', 'title' => 'Coming (5 min)', 'url' => $alert->ownerReplyUrl('coming_5')],
                ['action' => 'moved', 'title' => 'Moved', 'url' => $alert->ownerReplyUrl('moved')],
            ],
        ];

        return $this->send($user, $payload);
    }

    public function sendTest(User $user): int
    {
        return $this->send($user, [
            'title' => '✅ Contact Scan alerts are on',
            'body' => 'This device will be notified when someone scans your car.',
            'url' => route('dashboard'),
            'tag' => 'test',
        ]);
    }

    private function send(User $user, array $payload): int
    {
        if (! $this->isConfigured()) {
            return 0;
        }

        $subscriptions = $user->pushSubscriptions()->get();
        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('contactscan.vapid.subject'),
                'publicKey' => config('contactscan.vapid.public_key'),
                'privateKey' => config('contactscan.vapid.private_key'),
            ],
        ], ['TTL' => 3600, 'urgency' => 'high'], 10);

        $byEndpoint = [];
        foreach ($subscriptions as $sub) {
            $byEndpoint[$sub->endpoint] = $sub;
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding,
                ]),
                json_encode($payload)
            );
        }

        $delivered = 0;
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getEndpoint();
            if ($report->isSuccess()) {
                $delivered++;
            } elseif ($report->isSubscriptionExpired()) {
                // Device unsubscribed / browser data cleared: forget it.
                $byEndpoint[$endpoint]?->delete();
            } else {
                Log::warning('Web push failed', ['reason' => $report->getReason()]);
            }
        }

        return $delivered;
    }
}
