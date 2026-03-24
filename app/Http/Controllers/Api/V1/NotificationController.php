<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Register FCM token
     * POST /api/v1/notifications/fcm-token
     */
    public function registerFcmToken(Request $request): JsonResponse
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
                'fcm_token' => 'required|string|min:10',
                'device_type' => 'required|in:android,ios',
                'device_id' => 'nullable|string|max:255',
                'device_name' => 'nullable|string|max:255',
            ]);

            // Check if token already exists for this user
            $existingToken = FcmToken::where('user_id', $user->id)
                ->where('fcm_token', $request->fcm_token)
                ->first();

            if ($existingToken) {
                // Update existing token
                $existingToken->update([
                    'device_type' => $request->device_type,
                    'device_id' => $request->device_id,
                    'device_name' => $request->device_name,
                    'is_active' => true,
                ]);
                $fcmToken = $existingToken;
            } else {
                // Create new token
                $fcmToken = FcmToken::create([
                    'user_id' => $user->id,
                    'fcm_token' => $request->fcm_token,
                    'device_type' => $request->device_type,
                    'device_id' => $request->device_id,
                    'device_name' => $request->device_name,
                    'is_active' => true,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'FCM token registered successfully',
                'data' => $this->formatFcmToken($fcmToken),
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
                'error' => 'Failed to register FCM token',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get notification preferences
     * GET /api/v1/notifications/preferences
     */
    public function getPreferences(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            $preferences = NotificationPreference::where('user_id', $user->id)
                ->get()
                ->map(fn($pref) => $this->formatPreference($pref))
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => $preferences,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch notification preferences',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update notification preferences
     * POST /api/v1/notifications/preferences
     */
    public function updatePreferences(Request $request): JsonResponse
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
                'preferences' => 'required|array',
                'preferences.*.notification_type' => 'required|in:ride_updates,messages,reviews,promotions,system_alerts,driver_requests,booking_confirmations',
                'preferences.*.is_enabled' => 'required|boolean',
            ]);

            $updatedPreferences = [];

            foreach ($request->preferences as $pref) {
                $preference = NotificationPreference::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'notification_type' => $pref['notification_type'],
                    ],
                    [
                        'is_enabled' => $pref['is_enabled'],
                    ]
                );

                $updatedPreferences[] = $this->formatPreference($preference);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification preferences updated successfully',
                'data' => $updatedPreferences,
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
                'error' => 'Failed to update notification preferences',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get notifications
     * GET /api/v1/notifications
     */
    public function getNotifications(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Get user's FCM tokens
            $fcmTokens = FcmToken::where('user_id', $user->id)
                ->where('is_active', true)
                ->get()
                ->map(fn($token) => $this->formatFcmToken($token))
                ->toArray();

            // Get user's notification preferences
            $preferences = NotificationPreference::where('user_id', $user->id)
                ->get()
                ->map(fn($pref) => $this->formatPreference($pref))
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'fcm_tokens' => $fcmTokens,
                    'preferences' => $preferences,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch notifications',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Format FCM token for response
     */
    private function formatFcmToken(FcmToken $token): array
    {
        return [
            'id' => $token->id,
            'fcm_token' => $token->fcm_token,
            'device_type' => $token->device_type,
            'device_id' => $token->device_id,
            'device_name' => $token->device_name,
            'is_active' => $token->is_active,
            'created_at' => $token->created_at,
            'updated_at' => $token->updated_at,
        ];
    }

    /**
     * Format preference for response
     */
    private function formatPreference(NotificationPreference $preference): array
    {
        return [
            'id' => $preference->id,
            'notification_type' => $preference->notification_type,
            'is_enabled' => $preference->is_enabled,
            'created_at' => $preference->created_at,
            'updated_at' => $preference->updated_at,
        ];
    }
}
