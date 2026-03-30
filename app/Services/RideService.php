<?php

namespace App\Services;

use App\Models\Ride;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RideService
{
    private RideStatusValidator $statusValidator;
    private FareCalculatorService $fareCalculator;

    public function __construct(RideStatusValidator $statusValidator)
    {
        $this->statusValidator = $statusValidator;
    }

    /**
     * Request a new ride
     */
    public function requestRide(
        User $rider,
        string $pickupLocation,
        float $pickupLat,
        float $pickupLng,
        string $dropoffLocation,
        float $dropoffLat,
        float $dropoffLng,
        float $estimatedDistanceKm,
        int $estimatedDurationMinutes,
        float $tollAmount = 0,
        ?string $city = null
    ): Ride
    {
        return DB::transaction(function () use (
            $rider,
            $pickupLocation,
            $pickupLat,
            $pickupLng,
            $dropoffLocation,
            $dropoffLat,
            $dropoffLng,
            $estimatedDistanceKm,
            $estimatedDurationMinutes,
            $tollAmount,
            $city
        ) {
            // Calculate estimated fare
            $this->fareCalculator = new FareCalculatorService($city);
            $fareBreakdown = $this->fareCalculator->calculate(
                $estimatedDistanceKm,
                $estimatedDurationMinutes,
                $tollAmount
            );

            // Create ride
            $ride = Ride::create([
                'rider_id' => $rider->id,
                'pickup_location' => $pickupLocation,
                'pickup_lat' => $pickupLat,
                'pickup_lng' => $pickupLng,
                'dropoff_location' => $dropoffLocation,
                'dropoff_lat' => $dropoffLat,
                'dropoff_lng' => $dropoffLng,
                'estimated_distance_km' => $estimatedDistanceKm,
                'estimated_duration_minutes' => $estimatedDurationMinutes,
                'estimated_fare' => $fareBreakdown['total_fare'],
                'toll_amount' => $tollAmount,
                'status' => 'requested',
                'requested_at' => now(),
            ]);

            return $ride;
        });
    }

    /**
     * Accept a ride
     */
    public function acceptRide(Ride $ride, User $driver): Ride
    {
        return DB::transaction(function () use ($ride, $driver) {
            // Validate status transition
            $this->statusValidator->validate($ride->status, 'accepted');

            // Safe update: only update if status is still 'requested'
            $rowsAffected = DB::table('rides')
                ->where('id', $ride->id)
                ->where('status', 'requested')
                ->update([
                    'driver_id' => $driver->id,
                    'status' => 'accepted',
                    'accepted_at' => now(),
                    'updated_at' => now(),
                ]);

            // If no rows were affected, ride was already taken
            if ($rowsAffected === 0) {
                \Illuminate\Support\Facades\Log::warning('Ride already taken', [
                    'ride_id' => $ride->id,
                    'driver_id' => $driver->id,
                    'timestamp' => now(),
                ]);
                throw new \App\Exceptions\RideAlreadyTakenException();
            }

            \Illuminate\Support\Facades\Log::info('Ride accepted', [
                'ride_id' => $ride->id,
                'driver_id' => $driver->id,
            ]);

            return $ride->refresh();
        });
    }

    /**
     * Driver arrived at pickup location
     */
    public function arriveAtPickup(Ride $ride): Ride
    {
        return DB::transaction(function () use ($ride) {
            // Validate status transition
            $this->statusValidator->validate($ride->status, 'arrived');

            // Safe update: only update if status is still 'accepted'
            $rowsAffected = DB::table('rides')
                ->where('id', $ride->id)
                ->where('status', 'accepted')
                ->update([
                    'status' => 'arrived',
                    'arrived_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($rowsAffected === 0) {
                throw new \App\Exceptions\InvalidRideTransitionException(
                    'Ride status has changed. Cannot arrive at pickup.'
                );
            }

            return $ride->refresh();
        });
    }

    /**
     * Start the ride
     */
    public function startRide(Ride $ride): Ride
    {
        return DB::transaction(function () use ($ride) {
            // Validate status transition
            $this->statusValidator->validate($ride->status, 'started');

            // Safe update: only update if status is still 'arrived'
            $rowsAffected = DB::table('rides')
                ->where('id', $ride->id)
                ->where('status', 'arrived')
                ->update([
                    'status' => 'started',
                    'started_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($rowsAffected === 0) {
                throw new \App\Exceptions\InvalidRideTransitionException(
                    'Ride status has changed. Cannot start ride.'
                );
            }

            return $ride->refresh();
        });
    }

    /**
     * Complete the ride
     */
    public function completeRide(
        Ride $ride,
        float $actualDistanceKm,
        int $actualDurationMinutes,
        float $tollAmount = 0,
        ?string $city = null
    ): Ride
    {
        return DB::transaction(function () use (
            $ride,
            $actualDistanceKm,
            $actualDurationMinutes,
            $tollAmount,
            $city
        ) {
            // Validate status transition
            $this->statusValidator->validate($ride->status, 'completed');

            // Calculate actual fare
            $this->fareCalculator = new FareCalculatorService($city);
            $fareBreakdown = $this->fareCalculator->calculate(
                $actualDistanceKm,
                $actualDurationMinutes,
                $tollAmount
            );

            // Safe update: only update if status is still 'started'
            $rowsAffected = DB::table('rides')
                ->where('id', $ride->id)
                ->where('status', 'started')
                ->update([
                    'actual_distance_km' => $actualDistanceKm,
                    'actual_duration_minutes' => $actualDurationMinutes,
                    'actual_fare' => $fareBreakdown['total_fare'],
                    'toll_amount' => $tollAmount,
                    'status' => 'completed',
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($rowsAffected === 0) {
                throw new \App\Exceptions\InvalidRideTransitionException(
                    'Ride status has changed. Cannot complete ride.'
                );
            }

            return $ride->refresh();
        });
    }

    /**
     * Cancel a ride
     */
    public function cancelRide(Ride $ride, string $reason): Ride
    {
        return DB::transaction(function () use ($ride, $reason) {
            // Validate status transition
            $this->statusValidator->validate($ride->status, 'cancelled');

            // Safe update: only update if status is 'requested' or 'accepted'
            $rowsAffected = DB::table('rides')
                ->where('id', $ride->id)
                ->whereIn('status', ['requested', 'accepted'])
                ->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                    'cancelled_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($rowsAffected === 0) {
                throw new \App\Exceptions\InvalidRideTransitionException(
                    'Cannot cancel ride in current status.'
                );
            }

            // Log cancellation
            \Illuminate\Support\Facades\Log::info('Ride cancelled', [
                'ride_id' => $ride->id,
                'reason' => $reason,
                'user_id' => auth()->id(),
            ]);

            return $ride->refresh();
        });
    }

    /**
     * Offer a ride (driver offering a ride)
     */
    public function offerRide(
        User $driver,
        string $pickupLocation,
        float $pickupLat,
        float $pickupLng,
        string $dropoffLocation,
        float $dropoffLat,
        float $dropoffLng,
        float $estimatedDistanceKm,
        int $estimatedDurationMinutes,
        int $availableSeats,
        float $pricePerSeat,
        ?string $description = null,
        ?array $preferences = null,
        bool $acAvailable = false,
        bool $wifiAvailable = false,
        ?string $musicPreference = null,
        bool $smokingAllowed = false,
        float $tollAmount = 0,
        ?string $city = null
    ): Ride
    {
        return DB::transaction(function () use (
            $driver,
            $pickupLocation,
            $pickupLat,
            $pickupLng,
            $dropoffLocation,
            $dropoffLat,
            $dropoffLng,
            $estimatedDistanceKm,
            $estimatedDurationMinutes,
            $availableSeats,
            $pricePerSeat,
            $description,
            $preferences,
            $acAvailable,
            $wifiAvailable,
            $musicPreference,
            $smokingAllowed,
            $tollAmount,
            $city
        ) {
            // Calculate estimated fare
            $this->fareCalculator = new FareCalculatorService($city);
            $fareBreakdown = $this->fareCalculator->calculate(
                $estimatedDistanceKm,
                $estimatedDurationMinutes,
                $tollAmount
            );

            // Create ride with driver_id set (driver offering)
            $ride = Ride::create([
                'driver_id' => $driver->id,
                'pickup_location' => $pickupLocation,
                'pickup_lat' => $pickupLat,
                'pickup_lng' => $pickupLng,
                'dropoff_location' => $dropoffLocation,
                'dropoff_lat' => $dropoffLat,
                'dropoff_lng' => $dropoffLng,
                'estimated_distance_km' => $estimatedDistanceKm,
                'estimated_duration_minutes' => $estimatedDurationMinutes,
                'estimated_fare' => $fareBreakdown['total_fare'],
                'toll_amount' => $tollAmount,
                'status' => 'offered',
                'requested_at' => now(),
                'available_seats' => $availableSeats,
                'price_per_seat' => $pricePerSeat,
                'description' => $description,
                'preferences' => $preferences,
                'ac_available' => $acAvailable,
                'wifi_available' => $wifiAvailable,
                'music_preference' => $musicPreference,
                'smoking_allowed' => $smokingAllowed,
            ]);

            \Illuminate\Support\Facades\Log::info('Ride offered', [
                'ride_id' => $ride->id,
                'driver_id' => $driver->id,
                'available_seats' => $availableSeats,
                'price_per_seat' => $pricePerSeat,
            ]);

            return $ride;
        });
    }

    /**
     * Get ride details
     */
    public function getRideDetails(Ride $ride): array
    {
        return [
            'id' => $ride->id,
            'rider_id' => $ride->rider_id,
            'driver_id' => $ride->driver_id,
            'pickup_location' => $ride->pickup_location,
            'pickup_lat' => (float) $ride->pickup_lat,
            'pickup_lng' => (float) $ride->pickup_lng,
            'dropoff_location' => $ride->dropoff_location,
            'dropoff_lat' => (float) $ride->dropoff_lat,
            'dropoff_lng' => (float) $ride->dropoff_lng,
            'estimated_distance_km' => (float) $ride->estimated_distance_km,
            'estimated_duration_minutes' => $ride->estimated_duration_minutes,
            'estimated_fare' => (float) $ride->estimated_fare,
            'actual_distance_km' => $ride->actual_distance_km ? (float) $ride->actual_distance_km : null,
            'actual_duration_minutes' => $ride->actual_duration_minutes,
            'actual_fare' => $ride->actual_fare ? (float) $ride->actual_fare : null,
            'toll_amount' => (float) $ride->toll_amount,
            'status' => $ride->status,
            'cancellation_reason' => $ride->cancellation_reason,
            'requested_at' => $ride->requested_at,
            'accepted_at' => $ride->accepted_at,
            'arrived_at' => $ride->arrived_at,
            'started_at' => $ride->started_at,
            'completed_at' => $ride->completed_at,
            'cancelled_at' => $ride->cancelled_at,
            'available_seats' => $ride->available_seats,
            'price_per_seat' => $ride->price_per_seat ? (float) $ride->price_per_seat : null,
            'description' => $ride->description,
            'preferences' => $ride->preferences,
            'ac_available' => (bool) $ride->ac_available,
            'wifi_available' => (bool) $ride->wifi_available,
            'music_preference' => $ride->music_preference,
            'smoking_allowed' => (bool) $ride->smoking_allowed,
            'created_at' => $ride->created_at,
            'updated_at' => $ride->updated_at,
        ];
    }

    /**
     * Update ride status generically
     */
    public function updateRideStatus(Ride $ride, string $newStatus): Ride
    {
        return DB::transaction(function () use ($ride, $newStatus) {
            // Validate status transition
            $this->statusValidator->validate($ride->status, $newStatus);

            // Handle different status transitions
            $updateData = [
                'status' => $newStatus,
                'updated_at' => now(),
            ];

            // Set appropriate timestamp and driver_id based on new status
            switch ($newStatus) {
                case 'accepted':
                    $updateData['accepted_at'] = now();
                    $updateData['driver_id'] = auth()->id();
                    break;
                case 'arrived':
                    $updateData['arrived_at'] = now();
                    break;
                case 'started':
                    $updateData['started_at'] = now();
                    break;
                case 'completed':
                    $updateData['completed_at'] = now();
                    break;
                case 'cancelled':
                    $updateData['cancelled_at'] = now();
                    break;
            }

            // Safe update: only update if status matches current status
            $rowsAffected = DB::table('rides')
                ->where('id', $ride->id)
                ->where('status', $ride->status)
                ->update($updateData);

            if ($rowsAffected === 0) {
                throw new \App\Exceptions\InvalidRideTransitionException(
                    'Ride status has changed. Cannot update ride.'
                );
            }

            \Illuminate\Support\Facades\Log::info('Ride status updated', [
                'ride_id' => $ride->id,
                'old_status' => $ride->status,
                'new_status' => $newStatus,
                'user_id' => auth()->id(),
            ]);

            return $ride->refresh();
        });
    }
}
