<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use App\Services\RideService;
use App\Services\RideStatusValidator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RideController extends Controller
{
    private RideService $rideService;

    public function __construct(RideService $rideService)
    {
        $this->rideService = $rideService;
    }

    /**
     * Request a new ride
     * POST /api/v1/rides
     */
    public function requestRide(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'pickup_location' => 'required|string',
                'pickup_lat' => 'required|numeric|between:-90,90',
                'pickup_lng' => 'required|numeric|between:-180,180',
                'dropoff_location' => 'required|string',
                'dropoff_lat' => 'required|numeric|between:-90,90',
                'dropoff_lng' => 'required|numeric|between:-180,180',
                'estimated_distance_km' => 'required|numeric|min:0.1',
                'estimated_duration_minutes' => 'required|integer|min:1',
                'toll_amount' => 'nullable|numeric|min:0',
                'city' => 'nullable|string',
            ]);

            $ride = $this->rideService->requestRide(
                auth()->user(),
                $request->pickup_location,
                $request->pickup_lat,
                $request->pickup_lng,
                $request->dropoff_location,
                $request->dropoff_lat,
                $request->dropoff_lng,
                $request->estimated_distance_km,
                $request->estimated_duration_minutes,
                $request->toll_amount ?? 0,
                $request->city
            );

            return response()->json([
                'success' => true,
                'message' => 'Ride requested successfully',
                'ride' => $this->rideService->getRideDetails($ride),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to request ride',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get ride details
     * GET /api/v1/rides/{id}
     */
    public function getRide(Ride $ride): JsonResponse
    {
        try {
            // Check authorization
            $user = auth()->user();
            if ($ride->rider_id !== $user->id && $ride->driver_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'ride' => $this->rideService->getRideDetails($ride),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch ride',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Search available rides offered by drivers
     * GET /api/v1/rides/available
     */
    public function searchAvailable(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'from_location' => 'nullable|string',
                'to_location' => 'nullable|string',
                'date' => 'nullable|date_format:Y-m-d',
                'time_from' => 'nullable|date_format:Y-m-d H:i:s',
                'time_to' => 'nullable|date_format:Y-m-d H:i:s',
                'seats_needed' => 'nullable|integer|min:1|max:8',
                'price_min' => 'nullable|numeric|min:0',
                'price_max' => 'nullable|numeric|min:0',
                'ac_available' => 'nullable|in:true,false,1,0',
                'wifi_available' => 'nullable|in:true,false,1,0',
                'smoking_allowed' => 'nullable|in:true,false,1,0',
                'sort_by' => 'nullable|string|in:price,rating,departure_time',
                'sort_order' => 'nullable|string|in:asc,desc',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            // Convert string booleans to actual booleans
            $criteria = $request->all();
            if (isset($criteria['ac_available'])) {
                $criteria['ac_available'] = filter_var($criteria['ac_available'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($criteria['wifi_available'])) {
                $criteria['wifi_available'] = filter_var($criteria['wifi_available'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($criteria['smoking_allowed'])) {
                $criteria['smoking_allowed'] = filter_var($criteria['smoking_allowed'], FILTER_VALIDATE_BOOLEAN);
            }

            $searchService = new \App\Services\RideSearchService();
            $rides = $searchService->searchAvailableRides($criteria);

            // Format the response with driver and vehicle information
            $formattedRides = $rides->map(function ($ride) {
                return [
                    'id' => $ride->id,
                    'pickup_location' => $ride->pickup_location,
                    'pickup_lat' => $ride->pickup_lat,
                    'pickup_lng' => $ride->pickup_lng,
                    'dropoff_location' => $ride->dropoff_location,
                    'dropoff_lat' => $ride->dropoff_lat,
                    'dropoff_lng' => $ride->dropoff_lng,
                    'estimated_distance_km' => $ride->estimated_distance_km,
                    'estimated_duration_minutes' => $ride->estimated_duration_minutes,
                    'estimated_fare' => $ride->estimated_fare,
                    'available_seats' => $ride->available_seats,
                    'price_per_seat' => $ride->price_per_seat,
                    'description' => $ride->description,
                    'preferences' => $ride->preferences,
                    'ac_available' => $ride->ac_available,
                    'wifi_available' => $ride->wifi_available,
                    'music_preference' => $ride->music_preference,
                    'smoking_allowed' => $ride->smoking_allowed,
                    'requested_at' => $ride->requested_at,
                    'driver' => $ride->driver ? [
                        'id' => $ride->driver->id,
                        'name' => $ride->driver->name,
                        'phone' => $ride->driver->phone,
                        'profile_photo_url' => $ride->driver->profile_photo_url,
                        'rating' => $ride->driver->driverProfile?->rating ?? 0,
                        'total_rides' => $ride->driver->driverProfile?->total_rides ?? 0,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Available rides retrieved successfully',
                'data' => $formattedRides,
                'pagination' => [
                    'total' => $rides->total(),
                    'per_page' => $rides->perPage(),
                    'current_page' => $rides->currentPage(),
                    'last_page' => $rides->lastPage(),
                    'from' => $rides->firstItem(),
                    'to' => $rides->lastItem(),
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to search available rides',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Accept a ride (driver only)
     * POST /api/v1/rides/{id}/accept
     */
    public function acceptRide(Ride $ride): JsonResponse
    {
        try {
            $driver = auth()->user();

            if ($driver->role !== 'driver') {
                return response()->json([
                    'success' => false,
                    'error' => 'Only drivers can accept rides',
                ], 403);
            }

            $ride = $this->rideService->acceptRide($ride, $driver);

            return response()->json([
                'success' => true,
                'message' => 'Ride accepted successfully',
                'ride' => $this->rideService->getRideDetails($ride),
            ], 200);

        } catch (\App\Exceptions\RideAlreadyTakenException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ride already taken',
                'message' => $e->getMessage(),
            ], 409);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to accept ride',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Arrive at pickup location
     * POST /api/v1/rides/{id}/arrive
     */
    public function arriveAtPickup(Ride $ride): JsonResponse
    {
        try {
            $ride = $this->rideService->arriveAtPickup($ride);

            return response()->json([
                'success' => true,
                'message' => 'Driver arrived at pickup location',
                'ride' => $this->rideService->getRideDetails($ride),
            ], 200);

        } catch (\App\Exceptions\InvalidRideTransitionException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid ride status',
                'message' => $e->getMessage(),
            ], 409);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update ride status',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Start the ride
     * POST /api/v1/rides/{id}/start
     */
    public function startRide(Ride $ride): JsonResponse
    {
        try {
            $ride = $this->rideService->startRide($ride);

            return response()->json([
                'success' => true,
                'message' => 'Ride started',
                'ride' => $this->rideService->getRideDetails($ride),
            ], 200);

        } catch (\App\Exceptions\InvalidRideTransitionException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid ride status',
                'message' => $e->getMessage(),
            ], 409);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to start ride',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Complete the ride
     * POST /api/v1/rides/{id}/complete
     */
    public function completeRide(Request $request, Ride $ride): JsonResponse
    {
        try {
            $request->validate([
                'actual_distance_km' => 'required|numeric|min:0.1',
                'actual_duration_minutes' => 'required|integer|min:1',
                'toll_amount' => 'nullable|numeric|min:0',
                'city' => 'nullable|string',
            ]);

            $ride = $this->rideService->completeRide(
                $ride,
                $request->actual_distance_km,
                $request->actual_duration_minutes,
                $request->toll_amount ?? 0,
                $request->city
            );

            return response()->json([
                'success' => true,
                'message' => 'Ride completed successfully',
                'ride' => $this->rideService->getRideDetails($ride),
            ], 200);

        } catch (\App\Exceptions\InvalidRideTransitionException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid ride status',
                'message' => $e->getMessage(),
            ], 409);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to complete ride',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel a ride
     * POST /api/v1/rides/{id}/cancel
     */
    public function cancelRide(Request $request, Ride $ride): JsonResponse
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            $ride = $this->rideService->cancelRide($ride, $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Ride cancelled successfully',
                'ride' => $this->rideService->getRideDetails($ride),
            ], 200);

        } catch (\App\Exceptions\InvalidRideTransitionException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot cancel ride',
                'message' => $e->getMessage(),
            ], 409);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to cancel ride',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Offer a ride (driver offering a ride)
     * POST /api/v1/rides/offer
     */
    public function offerRide(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'pickup_location' => 'required|string',
                'pickup_lat' => 'required|numeric|between:-90,90',
                'pickup_lng' => 'required|numeric|between:-180,180',
                'dropoff_location' => 'required|string',
                'dropoff_lat' => 'required|numeric|between:-90,90',
                'dropoff_lng' => 'required|numeric|between:-180,180',
                'estimated_distance_km' => 'required|numeric|min:0.1',
                'estimated_duration_minutes' => 'required|integer|min:1',
                'available_seats' => 'required|integer|min:1|max:8',
                'price_per_seat' => 'required|numeric|min:0.01|max:10000',
                'description' => 'nullable|string|max:500',
                'preferences' => 'nullable|json',
                'ac_available' => 'nullable|boolean',
                'wifi_available' => 'nullable|boolean',
                'music_preference' => 'nullable|string|max:255',
                'smoking_allowed' => 'nullable|boolean',
                'toll_amount' => 'nullable|numeric|min:0',
                'city' => 'nullable|string',
            ]);

            $ride = $this->rideService->offerRide(
                auth()->user(),
                $request->pickup_location,
                $request->pickup_lat,
                $request->pickup_lng,
                $request->dropoff_location,
                $request->dropoff_lat,
                $request->dropoff_lng,
                $request->estimated_distance_km,
                $request->estimated_duration_minutes,
                $request->available_seats,
                $request->price_per_seat,
                $request->description,
                $request->preferences ? json_decode($request->preferences, true) : null,
                $request->boolean('ac_available', false),
                $request->boolean('wifi_available', false),
                $request->music_preference,
                $request->boolean('smoking_allowed', false),
                $request->toll_amount ?? 0,
                $request->city
            );

            return response()->json([
                'success' => true,
                'message' => 'Ride offered successfully',
                'ride' => $this->rideService->getRideDetails($ride),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to offer ride',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    /**
     * Update ride status
     * POST /api/v1/rides/{id}/update-status
     */
    public function updateRideStatus(Request $request, Ride $ride): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|string|in:accepted,arrived,started,completed,cancelled',
            ]);

            $ride = $this->rideService->updateRideStatus($ride, $request->status);

            return response()->json([
                'success' => true,
                'message' => 'Ride status updated successfully',
                'ride' => $this->rideService->getRideDetails($ride),
            ], 200);

        } catch (\App\Exceptions\InvalidRideTransitionException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid ride status transition',
                'message' => $e->getMessage(),
            ], 409);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update ride status',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

