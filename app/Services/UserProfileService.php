<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserProfileService
{
    private const CACHE_TTL = 1800; // 30 minutes

    /**
     * Get user profile with caching
     */
    public function getProfile(User $user): array
    {
        $cacheKey = $this->getCacheKey($user->id);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $profile = [
                'id' => $user->id,
                'firebase_uid' => $user->firebase_uid,
                'name' => $user->name,
                'display_name' => $user->display_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'is_verified' => $user->is_verified,
                'gender' => $user->gender,
                'bio' => $user->bio,
                'profile_photo_url' => $user->profile_photo_url,
                'user_preference' => $user->user_preference,
                'date_of_birth' => $user->date_of_birth,
                'onboarding_completed' => $user->onboarding_completed,
                'profile_completed' => $user->profile_completed,
                'profile_visibility' => $user->profile_visibility,
                'show_phone' => $user->show_phone,
                'show_email' => $user->show_email,
                'allow_messages' => $user->allow_messages,
                'language' => $user->language,
                'theme' => $user->theme,
                'created_at' => $user->created_at,
            ];

            // Include driver profile fields if user is a driver
            if ($user->role === 'driver' && $user->driverProfile) {
                $profile['driver_profile'] = [
                    'languages_spoken' => $user->driverProfile->languages_spoken,
                    'emergency_contact' => $user->driverProfile->emergency_contact,
                    'insurance_provider' => $user->driverProfile->insurance_provider,
                    'insurance_policy_number' => $user->driverProfile->insurance_policy_number,
                ];
            }

            return $profile;
        });
    }

    /**
     * Update user profile
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);
        $this->invalidateCache($user->id);
        return $user;
    }

    /**
     * Invalidate user profile cache
     */
    public function invalidateCache(int $userId): void
    {
        Cache::forget($this->getCacheKey($userId));
    }

    /**
     * Generate cache key
     */
    private function getCacheKey(int $userId): string
    {
        return "user:profile:{$userId}";
    }
}
