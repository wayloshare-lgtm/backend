<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vehicle_name',
        'vehicle_type',
        'license_plate',
        'vehicle_color',
        'vehicle_year',
        'seating_capacity',
        'vehicle_photo',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'vehicle_type' => 'string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'vehicle_year' => 'integer',
            'seating_capacity' => 'integer',
        ];
    }

    /**
     * Get the user that owns this vehicle
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the rides that use this vehicle
     */
    public function rides(): HasMany
    {
        return $this->hasMany(Ride::class);
    }

    /**
     * Get seating capacity based on vehicle type
     * Auto-determines capacity if not explicitly set
     */
    public function getSeatingCapacityAttribute($value): int
    {
        // If seating capacity is explicitly set, return it
        if ($value !== null) {
            return $value;
        }

        // Auto-determine based on vehicle type
        return match ($this->vehicle_type) {
            'sedan' => 5,
            'suv' => 7,
            'hatchback' => 5,
            'muv' => 8,
            'compact_suv' => 5,
            default => 5,
        };
    }

    /**
     * Set seating capacity with validation
     */
    public function setSeatingCapacityAttribute($value): void
    {
        // Allow null to enable auto-determination
        if ($value === null) {
            $this->attributes['seating_capacity'] = null;
        } else {
            // Validate capacity is between 1 and 8
            $validated = max(1, min(8, (int) $value));
            $this->attributes['seating_capacity'] = $validated;
        }
    }
}
