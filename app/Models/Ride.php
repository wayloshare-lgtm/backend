<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ride extends Model
{
    use HasFactory;

    protected $fillable = [
        'rider_id',
        'driver_id',
        'vehicle_id',
        'pickup_location',
        'pickup_lat',
        'pickup_lng',
        'dropoff_location',
        'dropoff_lat',
        'dropoff_lng',
        'departure_date',
        'departure_time',
        'estimated_distance_km',
        'estimated_duration_minutes',
        'estimated_fare',
        'actual_distance_km',
        'actual_duration_minutes',
        'actual_fare',
        'toll_amount',
        'status',
        'cancellation_reason',
        'requested_at',
        'accepted_at',
        'arrived_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'available_seats',
        'price_per_seat',
        'description',
        'preferences',
        'ac_available',
        'wifi_available',
        'music_preference',
        'smoking_allowed',
    ];

    protected $casts = [
        'pickup_lat' => 'decimal:7',
        'pickup_lng' => 'decimal:7',
        'dropoff_lat' => 'decimal:7',
        'dropoff_lng' => 'decimal:7',
        'departure_date' => 'date',
        'departure_time' => 'datetime:H:i:s',
        'estimated_distance_km' => 'decimal:2',
        'estimated_fare' => 'decimal:2',
        'actual_distance_km' => 'decimal:2',
        'actual_fare' => 'decimal:2',
        'toll_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'accepted_at' => 'datetime',
        'arrived_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'price_per_seat' => 'decimal:2',
        'preferences' => 'json',
        'ac_available' => 'boolean',
        'wifi_available' => 'boolean',
        'smoking_allowed' => 'boolean',
    ];

    /**
     * Get the rider (user) who requested this ride
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    /**
     * Get the driver (user) who accepted this ride
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * Get the vehicle used for this ride
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    /**
     * Get the bookings for this ride
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the location history for this ride
     */
    public function locations()
    {
        return $this->hasMany(RideLocation::class);
    }

    /**
     * Get the chats for this ride
     */
    public function chats()
    {
        return $this->hasMany(Chat::class);
    }
}
