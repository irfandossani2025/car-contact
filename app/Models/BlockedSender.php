<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedSender extends Model
{
    protected $fillable = ['user_id', 'sender_hash'];
}
