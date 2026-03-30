<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\AdminFareController;
use App\Http\Controllers\Api\V1\RideController;
use App\Http\Controllers\Api\V1\HealthCheckController;
use App\Http\Controllers\Api\V1\UserProfileController;
use App\Http\Controllers\Api\V1\DriverVerificationController;
use App\Http\Controllers\Api\V1\VehicleController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\SavedRouteController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PerformanceMetricsController;

Route::prefix('v1')->group(function () {
    // Public health check (no auth required)
    Route::get('/health', [HealthCheckController::class, 'check']);

    // Debug endpoint to check request headers
    Route::get('/debug/headers', function (\Illuminate\Http\Request $request) {
        return response()->json([
            'headers' => [
                'Authorization' => $request->header('Authorization'),
                'Bearer Token' => $request->bearerToken(),
                'Accept' => $request->header('Accept'),
                'Content-Type' => $request->header('Content-Type'),
            ],
            'auth_user' => auth()->user(),
            'sanctum_user' => auth('sanctum')->user(),
        ]);
    });

    // Public auth routes (rate limited)
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');

    // Protected routes (Sanctum token required)
    Route::middleware('auth:sanctum')->group(function () {
        // Auth endpoints (rate limited)
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('throttle:sensitive');
        Route::delete('/auth/delete-account', [AuthController::class, 'deleteAccount'])->middleware('throttle:auth');

        // User Profile endpoints (rate limited for write operations)
        Route::get('/user/profile', [UserProfileController::class, 'getProfile']);
        Route::post('/user/profile', [UserProfileController::class, 'updateProfile'])->middleware('throttle:sensitive');
        Route::post('/user/profile/photo', [UserProfileController::class, 'uploadProfilePhoto'])->middleware('throttle:sensitive');
        Route::post('/user/profile/complete', [UserProfileController::class, 'completeProfile'])->middleware('throttle:sensitive');
        Route::post('/user/complete-onboarding', [UserProfileController::class, 'completeOnboarding'])->middleware('throttle:sensitive');

        // User Privacy endpoints
        Route::get('/user/privacy', [UserProfileController::class, 'getPrivacy']);
        Route::post('/user/privacy', [UserProfileController::class, 'updatePrivacy']);

        // User Preferences endpoints
        Route::get('/user/preferences', [UserProfileController::class, 'getPreferences']);
        Route::post('/user/preferences', [UserProfileController::class, 'updatePreferences']);

        // Vehicle endpoints
        Route::post('/vehicles', [VehicleController::class, 'createVehicle']);
        Route::get('/vehicles', [VehicleController::class, 'listVehicles']);
        Route::get('/vehicles/{vehicle}', [VehicleController::class, 'getVehicle']);
        Route::put('/vehicles/{vehicle}', [VehicleController::class, 'updateVehicle']);
        Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'deleteVehicle']);
        Route::post('/vehicles/{vehicle}/photo', [VehicleController::class, 'uploadVehiclePhoto']);
        Route::post('/vehicles/{vehicle}/set-default', [VehicleController::class, 'setDefaultVehicle']);

        // Ride endpoints
        Route::post('/rides/offer', [RideController::class, 'offerRide'])->middleware(\App\Http\Middleware\CheckDriverRole::class);
        Route::get('/rides/available', [RideController::class, 'searchAvailable']);
        Route::post('/rides', [RideController::class, 'requestRide']);
        Route::get('/rides/{ride}', [RideController::class, 'getRide']);
        Route::post('/rides/{ride}/accept', [RideController::class, 'acceptRide']);
        Route::post('/rides/{ride}/arrive', [RideController::class, 'arriveAtPickup']);
        Route::post('/rides/{ride}/start', [RideController::class, 'startRide']);
        Route::post('/rides/{ride}/complete', [RideController::class, 'completeRide']);
        Route::post('/rides/{ride}/cancel', [RideController::class, 'cancelRide']);
        Route::post('/rides/{ride}/update-status', [RideController::class, 'updateRideStatus']);

        // Booking endpoints (rate limited for write operations)
        Route::post('/bookings', [BookingController::class, 'createBooking'])->middleware('throttle:sensitive');
        Route::get('/bookings/history', [BookingController::class, 'getBookingHistory']);
        Route::get('/bookings', [BookingController::class, 'listBookings']);
        Route::get('/bookings/{booking}', [BookingController::class, 'getBookingDetails']);
        Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancelBooking'])->middleware('throttle:sensitive');

        // Review endpoints
        Route::post('/reviews', [ReviewController::class, 'createReview']);
        Route::post('/reviews/rate-passenger', [ReviewController::class, 'ratePassenger']);
        Route::get('/reviews/user/{userId}', [ReviewController::class, 'getReviewsByUser']);
        Route::get('/reviews/ride/{rideId}', [ReviewController::class, 'getReviewsByRide']);
        Route::get('/reviews/{review}', [ReviewController::class, 'getReview']);

        // Chat endpoints
        Route::post('/chats', [ChatController::class, 'createChat']);
        Route::get('/chats', [ChatController::class, 'listChats']);
        Route::post('/chats/{chat}/messages', [ChatController::class, 'sendMessage']);
        Route::get('/chats/{chat}/messages', [ChatController::class, 'getMessages']);
        Route::post('/chats/{chat}/mark-read', [ChatController::class, 'markAsRead']);
        Route::delete('/chats/{chat}', [ChatController::class, 'deleteChat']);

        // Saved Routes endpoints
        Route::post('/saved-routes', [SavedRouteController::class, 'createSavedRoute']);
        Route::get('/saved-routes', [SavedRouteController::class, 'listSavedRoutes']);
        Route::get('/saved-routes/recent', [SavedRouteController::class, 'getRecentRoutes']);
        Route::get('/saved-routes/{savedRoute}', [SavedRouteController::class, 'getSavedRoute']);
        Route::put('/saved-routes/{savedRoute}', [SavedRouteController::class, 'updateSavedRoute']);
        Route::delete('/saved-routes/{savedRoute}', [SavedRouteController::class, 'deleteSavedRoute']);
        Route::post('/saved-routes/{savedRoute}/pin', [SavedRouteController::class, 'togglePin']);

        // Notification endpoints
        Route::post('/notifications/fcm-token', [NotificationController::class, 'registerFcmToken']);
        Route::get('/notifications/preferences', [NotificationController::class, 'getPreferences']);
        Route::post('/notifications/preferences', [NotificationController::class, 'updatePreferences']);
        Route::get('/notifications', [NotificationController::class, 'getNotifications']);

        // Location endpoints
        Route::post('/locations/update', [LocationController::class, 'updateLocation']);
        Route::get('/locations/history/{rideId}', [LocationController::class, 'getLocationHistory']);
        Route::get('/locations/current/{rideId}', [LocationController::class, 'getCurrentLocation']);

        // Payment Method endpoints (rate limited)
        Route::post('/payment-methods', [PaymentController::class, 'addPaymentMethod'])->middleware('throttle:sensitive');
        Route::get('/payment-methods', [PaymentController::class, 'getPaymentMethods']);
        Route::put('/payment-methods/{paymentMethod}', [PaymentController::class, 'updatePaymentMethod'])->middleware('throttle:sensitive');
        Route::delete('/payment-methods/{paymentMethod}', [PaymentController::class, 'deletePaymentMethod'])->middleware('throttle:sensitive');
        Route::post('/payment-methods/{paymentMethod}/set-default', [PaymentController::class, 'setDefaultPaymentMethod'])->middleware('throttle:sensitive');

        // Driver routes
        Route::middleware(\App\Http\Middleware\CheckDriverRole::class)->group(function () {
            Route::get('/driver/profile', [DriverController::class, 'getProfile']);
            Route::post('/driver/profile', [DriverController::class, 'createOrUpdateProfile']);
            Route::post('/driver/location', [DriverController::class, 'updateLocation']);
            Route::post('/driver/toggle-online', [DriverController::class, 'toggleOnlineStatus']);
        });

        // Driver Verification routes (accessible to all authenticated users)
        Route::post('/driver/verification', [DriverVerificationController::class, 'createOrUpdateVerification'])->middleware('throttle:sensitive');
        Route::get('/driver/verification/status', [DriverVerificationController::class, 'getVerificationStatus']);
        Route::get('/driver/kyc-status', [DriverVerificationController::class, 'getKycStatus']);
        Route::post('/driver/verification/documents', [DriverVerificationController::class, 'uploadDlFrontImage'])->middleware('throttle:sensitive');
        Route::post('/driver/verification/dl-front-image', [DriverVerificationController::class, 'uploadDlFrontImage'])->middleware('throttle:sensitive');
        Route::post('/driver/verification/dl-back-image', [DriverVerificationController::class, 'uploadDlBackImage'])->middleware('throttle:sensitive');
        Route::post('/driver/verification/rc-front-image', [DriverVerificationController::class, 'uploadRcFrontImage'])->middleware('throttle:sensitive');
        Route::post('/driver/verification/rc-back-image', [DriverVerificationController::class, 'uploadRcBackImage'])->middleware('throttle:sensitive');
        Route::get('/driver/verification/documents', [DriverVerificationController::class, 'getDocuments']);
        Route::post('/driver/verification/submit', [DriverVerificationController::class, 'submitVerification'])->middleware('throttle:sensitive');

        // Admin routes
        Route::middleware(\App\Http\Middleware\CheckAdminRole::class)->group(function () {
            Route::get('/admin/fare', [AdminFareController::class, 'getFareConfig']);
            Route::post('/admin/fare', [AdminFareController::class, 'createOrUpdateFareConfig']);
            Route::post('/admin/fare/calculate', [AdminFareController::class, 'calculateFareEstimate']);
        });

        // Performance Metrics endpoints (authenticated users)
        Route::get('/admin/performance/summary', [PerformanceMetricsController::class, 'summary']);
        Route::get('/admin/performance/queries', [PerformanceMetricsController::class, 'allQueries']);
        Route::get('/admin/performance/slow-queries', [PerformanceMetricsController::class, 'slowQueries']);
        Route::get('/admin/performance/top-slowest', [PerformanceMetricsController::class, 'topSlowest']);
        Route::get('/admin/performance/degradation', [PerformanceMetricsController::class, 'degradation']);
        Route::post('/admin/performance/clear-stats', [PerformanceMetricsController::class, 'clearStats']);
    });
});

