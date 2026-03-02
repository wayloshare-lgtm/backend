<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Only admins can access this resource',
            ], 403);
        }

        return $next($request);
    }
}
