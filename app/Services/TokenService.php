<?php

namespace App\Services;

use App\Models\User;

class TokenService
{
    /**
     * Generate Sanctum API token for user
     */
    public function generateToken(User $user, string $tokenName = 'api-token'): string
    {
        // Revoke existing tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken($tokenName);

        return $token->plainTextToken;
    }

    /**
     * Revoke all user tokens
     */
    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Revoke specific token
     */
    public function revokeToken(User $user, string $tokenId): void
    {
        $user->tokens()->where('id', $tokenId)->delete();
    }
}
