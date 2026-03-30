<?php

namespace App\Services;

use App\Models\Ride;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\Paginator;

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
     * Search available rides offered by drivers
     */
    public function searchAvailableRides(array $criteria): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Ride::where('status', 'offered')
            ->where('available_seats', '>', 0);

        // Filter by from location
        if (!empty($criteria['from_location'])) {
            $query->where('pickup_location', 'like', "%{$criteria['from_location']}%");
        }

        // Filter by to location
        if (!empty($criteria['to_location'])) {
            $query->where('dropoff_location', 'like', "%{$criteria['to_location']}%");
        }

        // Filter by date
        if (!empty($criteria['date'])) {
            $query->whereDate('requested_at', $criteria['date']);
        }

        // Filter by time range (if provided)
        if (!empty($criteria['time_from']) && !empty($criteria['time_to'])) {
            $query->whereBetween('requested_at', [
                $criteria['time_from'],
                $criteria['time_to'],
            ]);
        }

        // Filter by seats needed
        if (!empty($criteria['seats_needed'])) {
            $query->where('available_seats', '>=', $criteria['seats_needed']);
        }

        // Filter by price range
        if (isset($criteria['price_min']) && $criteria['price_min'] !== null && $criteria['price_min'] !== '') {
            $query->where('price_per_seat', '>=', $criteria['price_min']);
        }

        if (isset($criteria['price_max']) && $criteria['price_max'] !== null && $criteria['price_max'] !== '') {
            $query->where('price_per_seat', '<=', $criteria['price_max']);
        }

        // Filter by vehicle type
        if (!empty($criteria['vehicle_type'])) {
            // This would require a join with vehicles table
            // For now, we'll skip this filter as it needs vehicle relationship
        }

        // Filter by amenities
        if (isset($criteria['ac_available']) && $criteria['ac_available'] === true) {
            $query->where('ac_available', true);
        }

        if (isset($criteria['wifi_available']) && $criteria['wifi_available'] === true) {
            $query->where('wifi_available', true);
        }

        if (isset($criteria['smoking_allowed']) && $criteria['smoking_allowed'] === false) {
            $query->where('smoking_allowed', false);
        }

        // Apply sorting
        $sortBy = $criteria['sort_by'] ?? 'price_per_seat';
        $sortOrder = $criteria['sort_order'] ?? 'asc';

        if ($sortBy === 'price') {
            $query->orderBy('price_per_seat', $sortOrder);
        } elseif ($sortBy === 'rating') {
            // This would require a join with reviews table
            $query->orderBy('created_at', $sortOrder);
        } elseif ($sortBy === 'departure_time') {
            $query->orderBy('requested_at', $sortOrder);
        } else {
            $query->orderBy('price_per_seat', $sortOrder);
        }

        // Eager load driver and vehicle information
        $query->with(['driver', 'driver.driverProfile']);

        // Paginate results
        $perPage = $criteria['per_page'] ?? 15;
        $page = $criteria['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
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
