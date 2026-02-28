<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;
use Exception;

class FirebaseService
{
    private FirebaseAuth $auth;

    public function __construct()
    {
        try {
            $factory = (new Factory)->withServiceAccount(config('firebase.credentials.file'));
            $this->auth = $factory->createAuth();
        } catch (Exception $e) {
            throw new Exception('Firebase initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify Firebase ID token
     */
    public function verifyToken(string $token): array
    {
        try {
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $claims = $verifiedIdToken->claims();

            return [
                'uid' => $claims->get('sub'),
                'email' => $claims->get('email'),
                'phone' => $claims->get('phone_number'),
                'name' => $claims->get('name'),
                'picture' => $claims->get('picture'),
            ];
        } catch (Exception $e) {
            throw new Exception('Token verification failed: ' . $e->getMessage());
        }
    }
}
