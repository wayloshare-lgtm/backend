<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DriverProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DriverController extends Controller
{
    /**
     * Get driver's own profile
     * GET /api/v1/driver/profile
     */
    public function getProfile(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $profile = $user->driverProfile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'error' => 'Driver profile not found',
                    'message' => 'Please create a driver profile first',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'profile' => [
                    'id' => $profile->id,
                    'user_id' => $profile->user_id,
                    'license_number' => $profile->license_number,
                    'vehicle_type' => $profile->vehicle_type,
                    'vehicle_number' => $profile->vehicle_number,
                    'is_approved' => $profile->is_approved,
                    'is_online' => $profile->is_online,
                    'current_lat' => $profile->current_lat,
                    'current_lng' => $profile->current_lng,
                    'created_at' => $profile->created_at,
                    'updated_at' => $profile->updated_at,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch profile',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create or update driver profile
     * POST /api/v1/driver/profile
     */
    public function createOrUpdateProfile(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'license_number' => 'required|string|unique:driver_profiles,license_number',
                'vehicle_type' => 'required|string',
                'vehicle_number' => 'required|string|unique:driver_profiles,vehicle_number',
                'current_lat' => 'nullable|numeric|between:-90,90',
                'current_lng' => 'nullable|numeric|between:-180,180',
            ]);

            $user = auth()->user();

            // Check if profile already exists
            $profile = $user->driverProfile;

            if ($profile) {
                // Update existing profile
                $profile->update([
                    'license_number' => $request->license_number,
                    'vehicle_type' => $request->vehicle_type,
                    'vehicle_number' => $request->vehicle_number,
                    'current_lat' => $request->current_lat,
                    'current_lng' => $request->current_lng,
                ]);

                $statusCode = 200;
                $message = 'Driver profile updated successfully';
            } else {
                // Create new profile
                $profile = DriverProfile::create([
                    'user_id' => $user->id,
                    'license_number' => $request->license_number,
                    'vehicle_type' => $request->vehicle_type,
                    'vehicle_number' => $request->vehicle_number,
                    'current_lat' => $request->current_lat,
                    'current_lng' => $request->current_lng,
                ]);

                $statusCode = 201;
                $message = 'Driver profile created successfully';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'profile' => [
                    'id' => $profile->id,
                    'user_id' => $profile->user_id,
                    'license_number' => $profile->license_number,
                    'vehicle_type' => $profile->vehicle_type,
                    'vehicle_number' => $profile->vehicle_number,
                    'is_approved' => $profile->is_approved,
                    'is_online' => $profile->is_online,
                    'current_lat' => $profile->current_lat,
                    'current_lng' => $profile->current_lng,
                    'created_at' => $profile->created_at,
                    'updated_at' => $profile->updated_at,
                ],
            ], $statusCode);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create/update profile',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update driver location
     * POST /api/v1/driver/location
     */
    public function updateLocation(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'current_lat' => 'required|numeric|between:-90,90',
                'current_lng' => 'required|numeric|between:-180,180',
            ]);

            $user = auth()->user();
            $profile = $user->driverProfile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'error' => 'Driver profile not found',
                ], 404);
            }

            $profile->update([
                'current_lat' => $request->current_lat,
                'current_lng' => $request->current_lng,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
                'location' => [
                    'current_lat' => $profile->current_lat,
                    'current_lng' => $profile->current_lng,
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
                'error' => 'Failed to update location',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Toggle driver online status
     * POST /api/v1/driver/toggle-online
     */
    public function toggleOnlineStatus(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $profile = $user->driverProfile;

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'error' => 'Driver profile not found',
                ], 404);
            }

            $profile->update([
                'is_online' => !$profile->is_online,
            ]);

            return response()->json([
                'success' => true,
                'message' => $profile->is_online ? 'Driver is now online' : 'Driver is now offline',
                'is_online' => $profile->is_online,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to toggle online status',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
