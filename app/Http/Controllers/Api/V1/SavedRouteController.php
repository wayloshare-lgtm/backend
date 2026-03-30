<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\SavedRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SavedRouteController extends Controller
{
    /**
     * Create a new saved route
     * POST /api/v1/saved-routes
     */
    public function createSavedRoute(Request $request): JsonResponse
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
                'from_location' => 'required|string|max:255',
                'to_location' => 'required|string|max:255',
            ]);

            // Sanitize string fields
            $fromLocation = strip_tags($request->from_location);
            $toLocation = strip_tags($request->to_location);

            $savedRoute = SavedRoute::create([
                'user_id' => $user->id,
                'from_location' => $fromLocation,
                'to_location' => $toLocation,
                'is_pinned' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Saved route created successfully',
                'saved_route' => $this->formatSavedRoute($savedRoute),
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
                'error' => 'Failed to create saved route',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get all saved routes for authenticated user
     * GET /api/v1/saved-routes
     */
    public function listSavedRoutes(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            $savedRoutes = SavedRoute::where('user_id', $user->id)
                ->orderBy('is_pinned', 'desc')
                ->orderBy('last_used_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Saved routes retrieved successfully',
                'saved_routes' => $savedRoutes->map(fn($r) => $this->formatSavedRoute($r))->toArray(),
                'count' => $savedRoutes->count(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve saved routes',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get recent saved routes for authenticated user
     * GET /api/v1/saved-routes/recent
     */
    public function getRecentRoutes(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            $limit = $request->query('limit', 10);
            
            // Validate limit parameter
            if (!is_numeric($limit) || $limit < 1 || $limit > 100) {
                $limit = 10;
            }

            $recentRoutes = SavedRoute::where('user_id', $user->id)
                ->whereNotNull('last_used_at')
                ->orderBy('last_used_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Recent routes retrieved successfully',
                'recent_routes' => $recentRoutes->map(fn($r) => $this->formatSavedRoute($r))->toArray(),
                'count' => $recentRoutes->count(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve recent routes',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get a specific saved route
     * GET /api/v1/saved-routes/{id}
     */
    public function getSavedRoute(Request $request, SavedRoute $savedRoute): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Check if user owns this saved route
            if ($savedRoute->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'You do not have permission to view this saved route',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Saved route retrieved successfully',
                'saved_route' => $this->formatSavedRoute($savedRoute),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve saved route',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update a saved route
     * PUT /api/v1/saved-routes/{id}
     */
    public function updateSavedRoute(Request $request, SavedRoute $savedRoute): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Check if user owns this saved route
            if ($savedRoute->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'You do not have permission to update this saved route',
                ], 403);
            }

            $request->validate([
                'from_location' => 'nullable|string|max:255',
                'to_location' => 'nullable|string|max:255',
            ]);

            $updateData = $request->only([
                'from_location',
                'to_location',
            ]);

            // Sanitize string fields
            if (isset($updateData['from_location'])) {
                $updateData['from_location'] = strip_tags($updateData['from_location']);
            }
            if (isset($updateData['to_location'])) {
                $updateData['to_location'] = strip_tags($updateData['to_location']);
            }

            $savedRoute->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Saved route updated successfully',
                'saved_route' => $this->formatSavedRoute($savedRoute),
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
                'error' => 'Failed to update saved route',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete a saved route
     * DELETE /api/v1/saved-routes/{id}
     */
    public function deleteSavedRoute(Request $request, SavedRoute $savedRoute): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Check if user owns this saved route
            if ($savedRoute->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'You do not have permission to delete this saved route',
                ], 403);
            }

            $savedRoute->delete();

            return response()->json([
                'success' => true,
                'message' => 'Saved route deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete saved route',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Pin or unpin a saved route
     * POST /api/v1/saved-routes/{id}/pin
     */
    public function togglePin(Request $request, SavedRoute $savedRoute): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Check if user owns this saved route
            if ($savedRoute->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'You do not have permission to update this saved route',
                ], 403);
            }

            // Toggle the pin status
            $savedRoute->update([
                'is_pinned' => !$savedRoute->is_pinned,
            ]);

            return response()->json([
                'success' => true,
                'message' => $savedRoute->is_pinned ? 'Route pinned successfully' : 'Route unpinned successfully',
                'saved_route' => $this->formatSavedRoute($savedRoute),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to toggle pin status',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Format saved route response
     */
    private function formatSavedRoute(SavedRoute $savedRoute): array
    {
        return [
            'id' => $savedRoute->id,
            'user_id' => $savedRoute->user_id,
            'from_location' => $savedRoute->from_location,
            'to_location' => $savedRoute->to_location,
            'is_pinned' => $savedRoute->is_pinned,
            'last_used_at' => $savedRoute->last_used_at,
            'created_at' => $savedRoute->created_at,
            'updated_at' => $savedRoute->updated_at,
        ];
    }
}
