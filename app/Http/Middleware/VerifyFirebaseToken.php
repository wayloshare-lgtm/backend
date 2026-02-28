<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Symfony\Component\HttpFoundation\Response;

class VerifyFirebaseToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        try {
            $factory = (new Factory)->withServiceAccount(config('firebase.credentials.file'));
            $auth = $factory->createAuth();

            $verifiedIdToken = $auth->verifyIdToken($token);
            $uid = $verifiedIdToken->claims()->get('sub');

            // Find or create user
            $user = \App\Models\User::where('firebase_uid', $uid)->first();

            if (!$user) {
                // Auto-create user from Firebase token
                $user = \App\Models\User::create([
                    'firebase_uid' => $uid,
                    'email' => $verifiedIdToken->claims()->get('email'),
                    'phone_number' => $verifiedIdToken->claims()->get('phone_number'),
                ]);
            }

            $request->merge(['user' => $user]);
            auth()->setUser($user);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token', 'message' => $e->getMessage()], 401);
        }
    }
}
