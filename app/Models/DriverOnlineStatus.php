<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverOnlineStatus extends Model
{
    protected $fillable = ['driver_id', 'is_active', 'changed_at', 'car_details'];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    // Scope to filter by week
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('changed_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    // Scope to filter by month
    public function scopeThisMonth($query)
    {
        return $query->whereBetween('changed_at', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);
    }
}
