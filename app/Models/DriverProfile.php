<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'license_number',
        'vehicle_type',
        'vehicle_number',
        'is_approved',
        'is_online',
        'current_lat',
        'current_lng',
        'bio',
        'languages_spoken',
        'emergency_contact',
        'insurance_provider',
        'insurance_policy_number',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_online' => 'boolean',
        'current_lat' => 'decimal:7',
        'current_lng' => 'decimal:7',
    ];

    /**
     * Get the user that owns this driver profile
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
