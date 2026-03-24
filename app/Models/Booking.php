<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'ride_id',
        'passenger_id',
        'seats_booked',
        'passenger_name',
        'passenger_phone',
        'special_instructions',
        'luggage_info',
        'accessibility_requirements',
        'booking_status',
        'cancellation_reason',
    ];

    protected $casts = [
        'seats_booked' => 'integer',
        'booking_status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the ride associated with this booking
     */
    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }

    /**
     * Get the passenger (user) who made this booking
     */
    public function passenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }
}
