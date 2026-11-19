<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'notify_email',
        'notify_sms',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'telegram_link_token',
        'phone_verify_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notify_email' => 'boolean',
            'notify_sms' => 'boolean',
            'phone_verified_at' => 'datetime',
            'phone_verify_expires_at' => 'datetime',
        ];
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function alerts(): HasManyThrough
    {
        return $this->hasManyThrough(Alert::class, Vehicle::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function blockedSenders(): HasMany
    {
        return $this->hasMany(BlockedSender::class);
    }

    public function canReceiveSms(): bool
    {
        return $this->notify_sms && filled($this->phone) && $this->phone_verified_at !== null;
    }

    /** "+97150•••••67" for display. */
    public function maskedPhone(): ?string
    {
        if (! $this->phone) {
            return null;
        }

        return substr($this->phone, 0, 6).str_repeat('•', max(0, strlen($this->phone) - 8)).substr($this->phone, -2);
    }

    public function hasTelegram(): bool
    {
        return filled($this->telegram_chat_id);
    }
}
