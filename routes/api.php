<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\AdminFareController;
use App\Http\Controllers\Api\V1\RideController;
use App\Http\Controllers\Api\V1\HealthCheckController;

Route::prefix('v1')->group(function () {
    // Public health check (no auth required)
    Route::get('/health', [HealthCheckController::class, 'check']);

    // Public auth routes
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected routes (Sanctum token required)
    Route::middleware('auth:sanctum')->group(function () {
        // Auth endpoints
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::delete('/auth/delete-account', [AuthController::class, 'deleteAccount']);

        // Ride endpoints
        Route::post('/rides', [RideController::class, 'requestRide']);
        Route::get('/rides/{ride}', [RideController::class, 'getRide']);
        Route::post('/rides/{ride}/accept', [RideController::class, 'acceptRide']);
        Route::post('/rides/{ride}/arrive', [RideController::class, 'arriveAtPickup']);
        Route::post('/rides/{ride}/start', [RideController::class, 'startRide']);
        Route::post('/rides/{ride}/complete', [RideController::class, 'completeRide']);
        Route::post('/rides/{ride}/cancel', [RideController::class, 'cancelRide']);

        // Driver routes (role:driver middleware)
        Route::middleware('role:driver')->group(function () {
            Route::get('/driver/profile', [DriverController::class, 'getProfile']);
            Route::post('/driver/profile', [DriverController::class, 'createOrUpdateProfile']);
            Route::post('/driver/location', [DriverController::class, 'updateLocation']);
            Route::post('/driver/toggle-online', [DriverController::class, 'toggleOnlineStatus']);
        });

        // Admin routes (role:admin middleware)
        Route::middleware('role:admin')->group(function () {
            Route::get('/admin/fare', [AdminFareController::class, 'getFareConfig']);
            Route::post('/admin/fare', [AdminFareController::class, 'createOrUpdateFareConfig']);
            Route::post('/admin/fare/calculate', [AdminFareController::class, 'calculateFareEstimate']);
        });
    });
});

