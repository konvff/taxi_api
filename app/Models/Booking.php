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
        'name', 'email', 'category', 'pickuplocation', 'destination','user_id'
    ];

    public function user()
{
    return $this->belongsTo(User::class);
}

}
