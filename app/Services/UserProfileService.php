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
            return [
                'id' => $user->id,
                'firebase_uid' => $user->firebase_uid,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'is_verified' => $user->is_verified,
                'created_at' => $user->created_at,
            ];
        });
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
