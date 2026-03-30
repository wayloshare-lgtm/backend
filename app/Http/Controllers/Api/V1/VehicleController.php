<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Vehicle;
use App\Services\FileUploadService;
use App\Rules\FileUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VehicleController extends Controller
{
    private FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Create a new vehicle
     * POST /api/v1/vehicles
     */
    public function createVehicle(Request $request): JsonResponse
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
                'vehicle_name' => 'required|string|max:255',
                'vehicle_type' => 'required|in:sedan,suv,hatchback,muv,compact_suv',
                'license_plate' => 'required|string|unique:vehicles,license_plate',
                'vehicle_color' => 'nullable|string|max:255',
                'vehicle_year' => 'nullable|integer|min:1900|max:' . date('Y'),
                'seating_capacity' => 'nullable|integer|min:1|max:8',
                'vehicle_photo' => ['nullable', 'file', new FileUpload()],
            ]);

            $vehicleData = [
                'user_id' => $user->id,
                'vehicle_name' => $request->vehicle_name,
                'vehicle_type' => $request->vehicle_type,
                'license_plate' => $request->license_plate,
                'vehicle_color' => $request->vehicle_color,
                'vehicle_year' => $request->vehicle_year,
                'seating_capacity' => $request->seating_capacity,
                'is_active' => true,
            ];

            // Handle vehicle photo upload
            if ($request->hasFile('vehicle_photo')) {
                $filePath = $this->fileUploadService->upload($request->file('vehicle_photo'), 'vehicle-photos');
                $vehicleData['vehicle_photo'] = $filePath;
            }

            $vehicle = Vehicle::create($vehicleData);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle created successfully',
                'vehicle' => $this->formatVehicle($vehicle),
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
                'error' => 'Failed to create vehicle',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Upload vehicle photo
     * POST /api/v1/vehicles/{id}/photo
     */
    public function uploadVehiclePhoto(Request $request, Vehicle $vehicle): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Check if user owns this vehicle
            if ($vehicle->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'You do not have permission to update this vehicle',
                ], 403);
            }

            $request->validate([
                'vehicle_photo' => ['required', 'file', new FileUpload()],
            ]);

            $file = $request->file('vehicle_photo');

            // Delete old photo if exists
            if ($vehicle->vehicle_photo) {
                try {
                    $this->fileUploadService->delete($vehicle->vehicle_photo);
                } catch (\Exception $e) {
                    // Log error but continue with upload
                }
            }

            // Upload new photo
            $filePath = $this->fileUploadService->upload($file, 'vehicles/photos');
            $fileUrl = $this->fileUploadService->getUrl($filePath);

            // Update vehicle record
            $vehicle->update([
                'vehicle_photo' => $filePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle photo uploaded successfully',
                'vehicle_photo_url' => $fileUrl,
                'vehicle' => $this->formatVehicle($vehicle),
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
                'error' => 'Failed to upload vehicle photo',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get all vehicles for authenticated user
     * GET /api/v1/vehicles
     */
    public function listVehicles(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            $vehicles = Vehicle::where('user_id', $user->id)
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Vehicles retrieved successfully',
                'vehicles' => $vehicles->map(fn($v) => $this->formatVehicle($v))->toArray(),
                'count' => $vehicles->count(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve vehicles',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get a specific vehicle
     * GET /api/v1/vehicles/{id}
     */
    public function getVehicle(Request $request, Vehicle $vehicle): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Check if user owns this vehicle
            if ($vehicle->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'You do not have permission to view this vehicle',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Vehicle retrieved successfully',
                'vehicle' => $this->formatVehicle($vehicle),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve vehicle',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update vehicle details
     * PUT /api/v1/vehicles/{id}
     */
    public function updateVehicle(Request $request, Vehicle $vehicle): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Check if user owns this vehicle
            if ($vehicle->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'You do not have permission to update this vehicle',
                ], 403);
            }

            $request->validate([
                'vehicle_name' => 'nullable|string|max:255',
                'vehicle_type' => 'nullable|in:sedan,suv,hatchback,muv,compact_suv',
                'license_plate' => 'nullable|string|unique:vehicles,license_plate,' . $vehicle->id,
                'vehicle_color' => 'nullable|string|max:255',
                'vehicle_year' => 'nullable|integer|min:1900|max:' . date('Y'),
                'seating_capacity' => 'nullable|integer|min:1|max:8',
                'is_active' => 'nullable|boolean',
            ]);

            $vehicle->update($request->only([
                'vehicle_name',
                'vehicle_type',
                'license_plate',
                'vehicle_color',
                'vehicle_year',
                'seating_capacity',
                'is_active',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Vehicle updated successfully',
                'vehicle' => $this->formatVehicle($vehicle),
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
                'error' => 'Failed to update vehicle',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a vehicle
     * DELETE /api/v1/vehicles/{id}
     */
    public function deleteVehicle(Request $request, Vehicle $vehicle): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Check if user owns this vehicle
            if ($vehicle->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'You do not have permission to delete this vehicle',
                ], 403);
            }

            // Delete vehicle photo if exists
            if ($vehicle->vehicle_photo) {
                try {
                    $this->fileUploadService->delete($vehicle->vehicle_photo);
                } catch (\Exception $e) {
                    // Log error but continue with deletion
                }
            }

            $vehicle->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete vehicle',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Set vehicle as default
     * POST /api/v1/vehicles/{id}/set-default
     */
    public function setDefaultVehicle(Request $request, Vehicle $vehicle): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Check if user owns this vehicle
            if ($vehicle->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'You do not have permission to update this vehicle',
                ], 403);
            }

            // Unset all other vehicles as default for this user
            Vehicle::where('user_id', $user->id)
                ->where('id', '!=', $vehicle->id)
                ->update(['is_default' => false]);

            // Set this vehicle as default
            $vehicle->update(['is_default' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle set as default successfully',
                'vehicle' => $this->formatVehicle($vehicle),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to set default vehicle',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Format vehicle response
     */
    private function formatVehicle(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'user_id' => $vehicle->user_id,
            'vehicle_name' => $vehicle->vehicle_name,
            'vehicle_type' => $vehicle->vehicle_type,
            'license_plate' => $vehicle->license_plate,
            'vehicle_color' => $vehicle->vehicle_color,
            'vehicle_year' => $vehicle->vehicle_year,
            'seating_capacity' => $vehicle->seating_capacity,
            'vehicle_photo_url' => $vehicle->vehicle_photo ? $this->fileUploadService->getUrl($vehicle->vehicle_photo) : null,
            'is_default' => $vehicle->is_default,
            'is_active' => $vehicle->is_active,
            'created_at' => $vehicle->created_at,
            'updated_at' => $vehicle->updated_at,
        ];
    }
}
