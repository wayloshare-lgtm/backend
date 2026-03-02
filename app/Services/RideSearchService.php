<?php

namespace App\Services;

use App\Models\Ride;
use Illuminate\Support\Facades\Cache;

class RideSearchService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Search available rides with caching
     */
    public function searchRides(
        string $pickupLocation,
        string $dropoffLocation,
        ?string $date = null
    ): array
    {
        $cacheKey = $this->getCacheKey($pickupLocation, $dropoffLocation, $date);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use (
            $pickupLocation,
            $dropoffLocation,
            $date
        ) {
            $query = Ride::where('status', 'requested')
                ->where('pickup_location', 'like', "%{$pickupLocation}%")
                ->where('dropoff_location', 'like', "%{$dropoffLocation}%");

            if ($date) {
                $query->whereDate('created_at', $date);
            }

            return $query->select([
                'id',
                'rider_id',
                'pickup_location',
                'dropoff_location',
                'estimated_fare',
                'estimated_distance_km',
                'estimated_duration_minutes',
                'created_at',
            ])->get()->toArray();
        });
    }

    /**
     * Invalidate ride search cache
     */
    public function invalidateCache(string $pickupLocation, string $dropoffLocation): void
    {
        $cacheKey = $this->getCacheKey($pickupLocation, $dropoffLocation);
        Cache::forget($cacheKey);
    }

    /**
     * Generate cache key
     */
    private function getCacheKey(
        string $pickupLocation,
        string $dropoffLocation,
        ?string $date = null
    ): string
    {
        $key = "rides:search:{$pickupLocation}:{$dropoffLocation}";
        if ($date) {
            $key .= ":{$date}";
        }
        return $key;
    }
}
