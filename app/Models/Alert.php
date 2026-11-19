<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class Alert extends Model
{
    public const REASONS = [
        'blocking' => ['label' => 'Blocking my car', 'emoji' => '🚗'],
        'reserved' => ['label' => 'Parked in my / a reserved spot', 'emoji' => '🅿️'],
        'lights' => ['label' => 'Lights are on', 'emoji' => '💡'],
        'tow' => ['label' => 'About to be towed / ticketed', 'emoji' => '🚨'],
        'window' => ['label' => 'Window or door open', 'emoji' => '🪟'],
        'other' => ['label' => 'Something else', 'emoji' => '💬'],
    ];

    public const REPLIES = [
        'coming_5' => 'On my way — about 5 minutes',
        'coming_15' => 'Coming — about 15 minutes',
        'moved' => 'Car has been moved',
        'cant' => "Sorry, I can't come right now",
    ];

    protected $fillable = ['reason', 'message', 'sender_hash'];

    protected function casts(): array
    {
        return [
            'seen_at' => 'datetime',
            'replied_at' => 'datetime',
            'delivery' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Alert $alert) {
            $alert->public_token ??= Str::random(32);
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason]['label'] ?? 'Please move your car';
    }

    public function reasonEmoji(): string
    {
        return self::REASONS[$this->reason]['emoji'] ?? '🚗';
    }

    public function replyLabel(): ?string
    {
        return $this->reply ? (self::REPLIES[$this->reply] ?? null) : null;
    }

    /** Owner-facing page, usable without logging in (signed, valid 7 days). */
    public function ownerUrl(): string
    {
        return URL::temporarySignedRoute('owner.alert', now()->addDays(7), ['alert' => $this->id]);
    }

    public function ownerReplyUrl(string $reply): string
    {
        return URL::temporarySignedRoute('owner.alert.reply', now()->addDays(7), ['alert' => $this->id, 'reply' => $reply]);
    }

    /** Scanner-facing status page. */
    public function statusUrl(): string
    {
        return route('scan.status', $this->public_token);
    }

    public function markSeen(): void
    {
        if (! $this->seen_at) {
            $this->forceFill(['seen_at' => now()])->save();
        }
    }

    public function setReply(string $reply): void
    {
        $this->forceFill([
            'reply' => $reply,
            'replied_at' => now(),
            'seen_at' => $this->seen_at ?? now(),
        ])->save();
    }
}
