<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthCheckController extends Controller
{
    /**
     * Health check endpoint
     * GET /api/v1/health
     */
    public function check(): JsonResponse
    {
        $health = [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'services' => [],
        ];

        // Check database
        try {
            DB::connection()->getPdo();
            $health['services']['database'] = [
                'status' => 'ok',
                'connection' => DB::connection()->getName(),
            ];
        } catch (\Exception $e) {
            $health['services']['database'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
            $health['status'] = 'degraded';
        }

        // Check Redis (only in production)
if (app()->environment('production')) {
    try {
        Redis::ping();
        $health['services']['redis'] = [
            'status' => 'ok',
            'connection' => 'redis',
        ];
    } catch (\Exception $e) {
        $health['services']['redis'] = [
            'status' => 'error',
            'message' => $e->getMessage(),
        ];
        $health['status'] = 'degraded';
    }
} else {
    $health['services']['redis'] = [
        'status' => 'skipped',
        'message' => 'Redis check skipped in local environment',
    ];
}

        // Check queue
        try {
            $queueConnection = config('queue.default');
            $health['services']['queue'] = [
                'status' => 'ok',
                'connection' => $queueConnection,
            ];
        } catch (\Exception $e) {
            $health['services']['queue'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
            $health['status'] = 'degraded';
        }

        // Add authenticated user info if available
        if (auth()->check()) {
            $health['user_id'] = auth()->id();
        }

        $statusCode = $health['status'] === 'ok' ? 200 : 503;

        return response()->json($health, $statusCode);
    }
}
