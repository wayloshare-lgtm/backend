<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;

Route::prefix('v1')->group(function () {
    // Public auth routes (Firebase verification only)
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected routes (Sanctum token required)
    Route::middleware('auth:sanctum')->group(function () {
        // Auth endpoints
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::delete('/auth/delete-account', [AuthController::class, 'deleteAccount']);

        // Health check
        Route::get('/health', function () {
            return response()->json([
                'status' => 'ok',
                'timestamp' => now(),
                'user_id' => auth()->id(),
            ]);
        });
    });
});

