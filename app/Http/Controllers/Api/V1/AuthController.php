<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FirebaseService;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    private FirebaseService $firebaseService;
    private TokenService $tokenService;

    public function __construct(FirebaseService $firebaseService, TokenService $tokenService)
    {
        $this->firebaseService = $firebaseService;
        $this->tokenService = $tokenService;
    }

    /**
     * Login with Firebase ID token
     * POST /api/v1/auth/login
     * 
     * Verifies Firebase token and generates Sanctum API token
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Get Firebase token from Authorization header
            $firebaseToken = $request->bearerToken();

            if (!$firebaseToken) {
                return response()->json([
                    'success' => false,
                    'error' => 'Missing authorization token',
                ], 401);
            }

            // Verify Firebase token
            $firebaseData = $this->firebaseService->verifyToken($firebaseToken);

            // Find or create user
            $user = User::firstOrCreate(
                ['firebase_uid' => $firebaseData['uid']],
                [
                    'email' => $firebaseData['email'],
                    'phone' => $firebaseData['phone'],
                    'name' => $firebaseData['name'],
                    'is_active' => true,
                ]
            );

            // Update user if needed
            $user->update([
                'email' => $firebaseData['email'] ?? $user->email,
                'phone' => $firebaseData['phone'] ?? $user->phone,
                'name' => $firebaseData['name'] ?? $user->name,
            ]);

            // Generate Sanctum API token
            $apiToken = $this->tokenService->generateToken($user);

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'firebase_uid' => $user->firebase_uid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'is_verified' => $user->is_verified,
                    'created_at' => $user->created_at,
                ],
                'token' => $apiToken,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication failed',
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Logout user
     * POST /api/v1/auth/logout
     * 
     * Revokes all API tokens for the user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Revoke all tokens
            $this->tokenService->revokeAllTokens($user);

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Logout failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete user account
     * DELETE /api/v1/auth/delete-account
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Revoke all tokens before deletion
            $this->tokenService->revokeAllTokens($user);

            // Delete user
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete account',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get current user profile
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'firebase_uid' => $user->firebase_uid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'is_verified' => $user->is_verified,
                    'created_at' => $user->created_at,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch user',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
