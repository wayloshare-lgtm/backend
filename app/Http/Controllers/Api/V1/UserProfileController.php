<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserProfileService;
use App\Services\FileUploadService;
use App\Rules\IndianPhoneNumber;
use App\Rules\ValidEmail;
use App\Rules\DateOfBirth;
use App\Rules\FileUpload;
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

            // Sanitize emergency_contact before validation - remove dashes and spaces
            if ($request->has('emergency_contact') && $request->emergency_contact) {
                $sanitized = preg_replace('/[^0-9+]/', '', $request->emergency_contact);
                $request->merge(['emergency_contact' => $sanitized]);
            }

            $request->validate([
                'display_name' => 'nullable|string|max:255',
                'date_of_birth' => ['nullable', 'date', 'before:today', new DateOfBirth()],
                'gender' => 'nullable|in:male,female,other',
                'bio' => 'nullable|string|max:500',
                'user_preference' => 'nullable|in:driver,passenger,both',
                'profile_visibility' => 'nullable|in:public,private,friends_only',
                'show_phone' => 'nullable|boolean',
                'show_email' => 'nullable|boolean',
                'allow_messages' => 'nullable|boolean',
                'language' => 'nullable|in:english,hindi,regional',
                'theme' => 'nullable|in:light,dark,auto',
                'email' => ['nullable', new ValidEmail()],
                'languages_spoken' => 'nullable|array',
                'emergency_contact' => ['nullable', new IndianPhoneNumber()],
                'insurance_provider' => 'nullable|string|max:255',
                'insurance_policy_number' => 'nullable|string|max:255',
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
                'email',
            ]);

            // Remove null values to avoid overwriting existing data
            $updateData = array_filter($updateData, fn($value) => $value !== null);

            // Sanitize string fields
            if (isset($updateData['bio'])) {
                $updateData['bio'] = strip_tags($updateData['bio']);
            }
            if (isset($updateData['display_name'])) {
                $updateData['display_name'] = strip_tags($updateData['display_name']);
            }
            if (isset($updateData['email'])) {
                $updateData['email'] = strtolower(strip_tags($updateData['email']));
            }

            $user = $this->userProfileService->updateProfile($user, $updateData);

            // Update driver profile fields if provided
            $driverFields = $request->only([
                'languages_spoken',
                'emergency_contact',
                'insurance_provider',
                'insurance_policy_number',
            ]);

            $driverFields = array_filter($driverFields, fn($value) => $value !== null);

            if (!empty($driverFields) && $user->driverProfile) {
                $user->driverProfile()->update($driverFields);
            }

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
                'profile_photo' => ['required', 'file', new FileUpload()],
            ]);

            $file = $request->file('profile_photo');

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
                'date_of_birth' => ['required', 'date', 'before:today', new DateOfBirth()],
                'gender' => 'required|in:male,female,other',
                'user_preference' => 'required|in:driver,passenger,both',
            ]);

            // Sanitize display_name
            $displayName = strip_tags($request->display_name);

            // Update profile with required onboarding fields
            $user = $this->userProfileService->updateProfile($user, [
                'display_name' => $displayName,
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

    /**
     * Get user privacy settings
     * GET /api/v1/user/privacy
     */
    public function getPrivacy(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not authenticated',
                ], 401);
            }

            $privacy = [
                'profile_visibility' => $user->profile_visibility,
                'show_phone' => $user->show_phone,
                'show_email' => $user->show_email,
                'allow_messages' => $user->allow_messages,
            ];

            return response()->json([
                'success' => true,
                'privacy' => $privacy,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch privacy settings',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update user privacy settings
     * POST /api/v1/user/privacy
     */
    public function updatePrivacy(Request $request): JsonResponse
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
                'profile_visibility' => 'nullable|in:public,private,friends_only',
                'show_phone' => 'nullable|boolean',
                'show_email' => 'nullable|boolean',
                'allow_messages' => 'nullable|boolean',
            ]);

            $updateData = $request->only([
                'profile_visibility',
                'show_phone',
                'show_email',
                'allow_messages',
            ]);

            // Remove null values to avoid overwriting existing data
            $updateData = array_filter($updateData, fn($value) => $value !== null);

            $user = $this->userProfileService->updateProfile($user, $updateData);

            $privacy = [
                'profile_visibility' => $user->profile_visibility,
                'show_phone' => $user->show_phone,
                'show_email' => $user->show_email,
                'allow_messages' => $user->allow_messages,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Privacy settings updated successfully',
                'privacy' => $privacy,
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
                'error' => 'Failed to update privacy settings',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get user preferences
     * GET /api/v1/user/preferences
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

            $preferences = [
                'user_preference' => $user->user_preference,
                'language' => $user->language,
                'theme' => $user->theme,
            ];

            return response()->json([
                'success' => true,
                'preferences' => $preferences,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch preferences',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update user preferences
     * POST /api/v1/user/preferences
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
                'user_preference' => 'nullable|in:driver,passenger,both',
                'language' => 'nullable|in:english,hindi,regional',
                'theme' => 'nullable|in:light,dark,auto',
            ]);

            $updateData = $request->only([
                'user_preference',
                'language',
                'theme',
            ]);

            // Remove null values to avoid overwriting existing data
            $updateData = array_filter($updateData, fn($value) => $value !== null);

            $user = $this->userProfileService->updateProfile($user, $updateData);

            $preferences = [
                'user_preference' => $user->user_preference,
                'language' => $user->language,
                'theme' => $user->theme,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Preferences updated successfully',
                'preferences' => $preferences,
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
                'error' => 'Failed to update preferences',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
