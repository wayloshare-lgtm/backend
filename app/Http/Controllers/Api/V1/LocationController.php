<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use App\Models\RideLocation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    /**
     * Update driver location
     * POST /api/v1/locations/update
     */
    public function updateLocation(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            $request->validate([
                'ride_id' => 'required|integer|exists:rides,id',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'accuracy' => 'nullable|numeric|min:0',
                'speed' => 'nullable|numeric|min:0',
                'heading' => 'nullable|numeric|between:0,360',
                'altitude' => 'nullable|numeric',
                'timestamp' => 'nullable|date_format:Y-m-d H:i:s',
            ]);

            $ride = Ride::find($request->ride_id);

            // Authorization check: only the driver of the ride can update location
            if ($ride->driver_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'You are not authorized to update location for this ride',
                ], 403);
            }

            // Create location record
            $location = RideLocation::create([
                'ride_id' => $request->ride_id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'accuracy' => $request->accuracy,
                'speed' => $request->speed,
                'heading' => $request->heading,
                'altitude' => $request->altitude,
                'timestamp' => $request->timestamp ?? now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
                'data' => $this->formatLocation($location),
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
                'error' => 'Failed to update location',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get location history for a ride
     * GET /api/v1/locations/history/{ride_id}
     */
    public function getLocationHistory(Request $request, $rideId): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            $ride = Ride::find($rideId);

            if (!$ride) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ride not found',
                ], 404);
            }

            // Authorization check: only rider or driver of the ride can view location history
            if ($ride->rider_id !== $user->id && $ride->driver_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'You are not authorized to view location history for this ride',
                ], 403);
            }

            $request->validate([
                'limit' => 'nullable|integer|min:1|max:1000',
                'offset' => 'nullable|integer|min:0',
            ]);

            $limit = $request->limit ?? 100;
            $offset = $request->offset ?? 0;

            $locations = RideLocation::where('ride_id', $rideId)
                ->orderBy('timestamp', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->map(fn($location) => $this->formatLocation($location))
                ->toArray();

            $totalCount = RideLocation::where('ride_id', $rideId)->count();

            return response()->json([
                'success' => true,
                'data' => $locations,
                'pagination' => [
                    'total' => $totalCount,
                    'limit' => $limit,
                    'offset' => $offset,
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
                'error' => 'Failed to fetch location history',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get current location for a ride
     * GET /api/v1/locations/current/{ride_id}
     */
    public function getCurrentLocation(Request $request, $rideId): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            $ride = Ride::find($rideId);

            if (!$ride) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ride not found',
                ], 404);
            }

            // Authorization check: only rider or driver of the ride can view current location
            if ($ride->rider_id !== $user->id && $ride->driver_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'You are not authorized to view current location for this ride',
                ], 403);
            }

            $location = RideLocation::where('ride_id', $rideId)
                ->orderBy('timestamp', 'desc')
                ->first();

            if (!$location) {
                return response()->json([
                    'success' => false,
                    'error' => 'No location data available',
                    'message' => 'No location updates have been recorded for this ride yet',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatLocation($location),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch current location',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Format location for response
     */
    private function formatLocation(RideLocation $location): array
    {
        return [
            'id' => $location->id,
            'ride_id' => $location->ride_id,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'accuracy' => $location->accuracy,
            'speed' => $location->speed,
            'heading' => $location->heading,
            'altitude' => $location->altitude,
            'timestamp' => $location->timestamp,
            'created_at' => $location->created_at,
        ];
    }
}
