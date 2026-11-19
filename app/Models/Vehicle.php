<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    /** Unambiguous characters only (no 0/O, 1/I/L) so the code is easy to type. */
    public const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public const CODE_LENGTH = 8;

    protected $fillable = ['name', 'plate', 'color', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Vehicle $vehicle) {
            $vehicle->tag_code ??= static::generateUniqueCode();
        });
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (static::where('tag_code', $code)->exists());

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function scanUrl(): string
    {
        return route('scan.show', $this->tag_code);
    }

    /** "ABCD-EFGH" for printing. */
    public function displayCode(): string
    {
        return implode('-', str_split($this->tag_code, 4));
    }
}
