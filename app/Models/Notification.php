<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'user_id',
        'body',
        'receiver_id',
        'booking_id',
        'is_read',
        'created_at',
    ];
}
