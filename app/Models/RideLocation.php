<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RideLocation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'ride_id',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'heading',
        'altitude',
        'timestamp',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'accuracy' => 'decimal:2',
        'speed' => 'decimal:2',
        'heading' => 'decimal:2',
        'altitude' => 'decimal:2',
        'timestamp' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Get the ride that owns this location
     */
    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }
}
