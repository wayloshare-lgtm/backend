<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FareSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'base_fare',
        'per_km_rate',
        'per_minute_rate',
        'fuel_surcharge_per_km',
        'platform_fee_percentage',
        'toll_enabled',
        'night_multiplier',
        'surge_multiplier',
        'city',
        'is_active',
    ];

    protected $casts = [
        'base_fare' => 'decimal:2',
        'per_km_rate' => 'decimal:2',
        'per_minute_rate' => 'decimal:2',
        'fuel_surcharge_per_km' => 'decimal:2',
        'platform_fee_percentage' => 'decimal:2',
        'toll_enabled' => 'boolean',
        'night_multiplier' => 'decimal:2',
        'surge_multiplier' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get active fare settings for a city
     */
    public static function getActive(?string $city = null): ?self
    {
        $query = self::where('is_active', true);

        if ($city) {
            $query->where(function ($q) use ($city) {
                $q->where('city', $city)
                  ->orWhereNull('city');
            })->orderBy('city', 'desc');
        }

        return $query->latest()->first();
    }
}
