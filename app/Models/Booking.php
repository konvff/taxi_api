<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'bookings';

    protected $fillable = [
        'name',
        'email',
        'category',
        'pickuplocation',
        'pickup_latitude',
        'pickup_longitude',
        'destination',
        'dropoff_latitude',
        'dropoff_longitude',
        'user_id',
        'amount',
        'phone',
        'status',
        'notes',
        'created_by',
        'booking_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
