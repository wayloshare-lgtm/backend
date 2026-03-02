<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FareSetting;
use App\Services\FareCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminFareController extends Controller
{
    /**
     * Get active fare configuration
     * GET /api/v1/admin/fare
     */
    public function getFareConfig(Request $request): JsonResponse
    {
        try {
            $city = $request->query('city');
            $fareSetting = FareSetting::getActive($city);

            if (!$fareSetting) {
                return response()->json([
                    'success' => false,
                    'error' => 'No active fare settings found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'fare_config' => [
                    'id' => $fareSetting->id,
                    'base_fare' => (float) $fareSetting->base_fare,
                    'per_km_rate' => (float) $fareSetting->per_km_rate,
                    'per_minute_rate' => (float) $fareSetting->per_minute_rate,
                    'fuel_surcharge_per_km' => (float) $fareSetting->fuel_surcharge_per_km,
                    'platform_fee_percentage' => (float) $fareSetting->platform_fee_percentage,
                    'toll_enabled' => $fareSetting->toll_enabled,
                    'night_multiplier' => (float) $fareSetting->night_multiplier,
                    'surge_multiplier' => (float) $fareSetting->surge_multiplier,
                    'city' => $fareSetting->city,
                    'is_active' => $fareSetting->is_active,
                    'created_at' => $fareSetting->created_at,
                    'updated_at' => $fareSetting->updated_at,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch fare config',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create or update fare configuration
     * POST /api/v1/admin/fare
     */
    public function createOrUpdateFareConfig(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'base_fare' => 'required|numeric|min:0',
                'per_km_rate' => 'required|numeric|min:0',
                'per_minute_rate' => 'required|numeric|min:0',
                'fuel_surcharge_per_km' => 'required|numeric|min:0',
                'platform_fee_percentage' => 'required|numeric|min:0|max:100',
                'toll_enabled' => 'boolean',
                'night_multiplier' => 'required|numeric|min:0.1|max:5',
                'surge_multiplier' => 'required|numeric|min:0.1|max:5',
                'city' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            $city = $request->city;

            // Check if config exists for this city
            $fareSetting = FareSetting::where('city', $city)
                ->where('is_active', true)
                ->first();

            if ($fareSetting) {
                // Update existing
                $fareSetting->update($request->only([
                    'base_fare',
                    'per_km_rate',
                    'per_minute_rate',
                    'fuel_surcharge_per_km',
                    'platform_fee_percentage',
                    'toll_enabled',
                    'night_multiplier',
                    'surge_multiplier',
                    'is_active',
                ]));

                $statusCode = 200;
                $message = 'Fare configuration updated successfully';
            } else {
                // Create new
                $fareSetting = FareSetting::create($request->only([
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
                ]));

                $statusCode = 201;
                $message = 'Fare configuration created successfully';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'fare_config' => [
                    'id' => $fareSetting->id,
                    'base_fare' => (float) $fareSetting->base_fare,
                    'per_km_rate' => (float) $fareSetting->per_km_rate,
                    'per_minute_rate' => (float) $fareSetting->per_minute_rate,
                    'fuel_surcharge_per_km' => (float) $fareSetting->fuel_surcharge_per_km,
                    'platform_fee_percentage' => (float) $fareSetting->platform_fee_percentage,
                    'toll_enabled' => $fareSetting->toll_enabled,
                    'night_multiplier' => (float) $fareSetting->night_multiplier,
                    'surge_multiplier' => (float) $fareSetting->surge_multiplier,
                    'city' => $fareSetting->city,
                    'is_active' => $fareSetting->is_active,
                    'created_at' => $fareSetting->created_at,
                    'updated_at' => $fareSetting->updated_at,
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
                'error' => 'Failed to create/update fare config',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Calculate fare estimate
     * POST /api/v1/admin/fare/calculate
     */
    public function calculateFareEstimate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'distance_km' => 'required|numeric|min:0.1',
                'duration_minutes' => 'required|integer|min:1',
                'toll_amount' => 'nullable|numeric|min:0',
                'is_night_time' => 'boolean',
                'city' => 'nullable|string',
            ]);

            $calculator = new FareCalculatorService($request->city);

            $fareBreakdown = $calculator->calculate(
                $request->distance_km,
                $request->duration_minutes,
                $request->toll_amount ?? 0,
                $request->is_night_time ?? false
            );

            return response()->json([
                'success' => true,
                'fare_estimate' => $fareBreakdown,
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
                'error' => 'Failed to calculate fare',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
