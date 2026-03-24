<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserProfileService;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserProfileController extends Controller
{
    private UserProfileService $userProfileService;
    private FileUploadService $fileUploadService;

    public function __construct(
        UserProfileService $userProfileService,
        FileUploadService $fileUploadService
    ) {
        $this->userProfileService = $userProfileService;
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Get user profile
     * GET /api/v1/user/profile
     */
    public function getProfile(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            $profile = $this->userProfileService->getProfile($user);

            return response()->json([
                'success' => true,
                'profile' => $profile,
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
     * Update user profile
     * POST /api/v1/user/profile
     */
    public function updateProfile(Request $request): JsonResponse
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
                'display_name' => 'nullable|string|max:255',
                'date_of_birth' => 'nullable|date|before:today',
                'gender' => 'nullable|in:male,female,other',
                'bio' => 'nullable|string|max:500',
                'user_preference' => 'nullable|in:driver,passenger,both',
                'profile_visibility' => 'nullable|in:public,private,friends_only',
                'show_phone' => 'nullable|boolean',
                'show_email' => 'nullable|boolean',
                'allow_messages' => 'nullable|boolean',
                'language' => 'nullable|in:english,hindi,regional',
                'theme' => 'nullable|in:light,dark,auto',
            ]);

            $updateData = $request->only([
                'display_name',
                'date_of_birth',
                'gender',
                'bio',
                'user_preference',
                'profile_visibility',
                'show_phone',
                'show_email',
                'allow_messages',
                'language',
                'theme',
            ]);

            // Remove null values to avoid overwriting existing data
            $updateData = array_filter($updateData, fn($value) => $value !== null);

            $user = $this->userProfileService->updateProfile($user, $updateData);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'profile' => $this->userProfileService->getProfile($user),
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
                'error' => 'Failed to update profile',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Upload profile photo
     * POST /api/v1/user/profile/photo
     */
    public function uploadProfilePhoto(Request $request): JsonResponse
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
                'photo' => 'required|file|mimes:jpeg,png|max:10240',
            ]);

            $file = $request->file('photo');

            // Validate file
            $validationErrors = $this->fileUploadService->validate($file);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'error' => 'File validation failed',
                    'errors' => $validationErrors,
                ], 422);
            }

            // Delete old profile photo if exists
            if ($user->profile_photo_url) {
                try {
                    $this->fileUploadService->delete($user->profile_photo_url);
                } catch (\Exception $e) {
                    // Log error but continue with upload
                }
            }

            // Upload new photo
            $filePath = $this->fileUploadService->upload($file, 'profile-photos');
            $fileUrl = $this->fileUploadService->getUrl($filePath);

            // Update user profile
            $user = $this->userProfileService->updateProfile($user, [
                'profile_photo_url' => $filePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile photo uploaded successfully',
                'profile_photo_url' => $fileUrl,
                'profile' => $this->userProfileService->getProfile($user),
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
                'error' => 'Failed to upload profile photo',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Complete user onboarding
     * POST /api/v1/user/complete-onboarding
     */
    public function completeOnboarding(Request $request): JsonResponse
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
                'display_name' => 'required|string|max:255',
                'date_of_birth' => 'required|date|before:today',
                'gender' => 'required|in:male,female,other',
                'user_preference' => 'required|in:driver,passenger,both',
            ]);

            // Update profile with required onboarding fields
            $user = $this->userProfileService->updateProfile($user, [
                'display_name' => $request->display_name,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'user_preference' => $request->user_preference,
                'onboarding_completed' => true,
                'profile_completed' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Onboarding completed successfully',
                'profile' => $this->userProfileService->getProfile($user),
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
                'error' => 'Failed to complete onboarding',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    /**
     * Complete user profile
     * POST /api/v1/user/profile/complete
     */
    /**
     * Complete user profile
     * POST /api/v1/user/profile/complete
     */
    public function completeProfile(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            // Check if required fields are already filled in the user profile
            $requiredFields = ['display_name', 'date_of_birth', 'gender', 'profile_photo_url'];
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if (empty($user->$field)) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Missing required profile fields',
                    'missing_fields' => $missingFields,
                ], 422);
            }

            // Mark profile as complete
            $user = $this->userProfileService->updateProfile($user, [
                'profile_completed' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile marked as complete',
                'profile' => $this->userProfileService->getProfile($user),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to complete profile',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

}
