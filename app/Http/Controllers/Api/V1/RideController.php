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
}
