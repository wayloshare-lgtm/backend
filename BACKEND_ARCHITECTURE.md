# WayloShare Backend Architecture
## Laravel REST API for Ubuntu 22.04 VPS

---

## Table of Contents
1. [Required Backend Endpoints](#1-required-backend-endpoints)
2. [Database Schema](#2-database-schema)
3. [Authentication Strategy](#3-authentication-strategy)
4. [Background Jobs](#4-background-jobs)
5. [Redis Usage](#5-redis-usage)
6. [Storage Requirements](#6-storage-requirements)
7. [Laravel Folder Structure](#7-laravel-folder-structure)
8. [Deployment Plan](#8-deployment-plan)
9. [Security Best Practices](#9-security-best-practices)
10. [Scalability Roadmap](#10-scalability-roadmap)

---

## 1. Required Backend Endpoints

### Authentication Endpoints
```
POST   /api/v1/auth/verify-firebase-token
POST   /api/v1/auth/register
POST   /api/v1/auth/refresh-token
POST   /api/v1/auth/logout
DELETE /api/v1/auth/delete-account
```

### User Management
```
GET    /api/v1/user/profile
PUT    /api/v1/user/profile
POST   /api/v1/user/profile/photo
DELETE /api/v1/user/profile/photo
PUT    /api/v1/user/onboarding
GET    /api/v1/user/preferences
PUT    /api/v1/user/preferences
```

### Ride Management
```
POST   /api/v1/rides                    # Create/offer a ride
GET    /api/v1/rides                    # Search/list rides
GET    /api/v1/rides/{id}               # Get ride details
PUT    /api/v1/rides/{id}               # Update ride
DELETE /api/v1/rides/{id}               # Cancel ride
GET    /api/v1/rides/my-rides           # Get user's offered rides
POST   /api/v1/rides/{id}/publish       # Publish ride
POST   /api/v1/rides/{id}/complete      # Mark ride as completed
```

### Booking Management
```
POST   /api/v1/bookings                 # Create booking
GET    /api/v1/bookings                 # List user bookings
GET    /api/v1/bookings/{id}            # Get booking details
PUT    /api/v1/bookings/{id}/status     # Update booking status
DELETE /api/v1/bookings/{id}            # Cancel booking
GET    /api/v1/bookings/history         # Booking history
```

### Saved Routes
```
GET    /api/v1/routes/saved             # Get saved routes
POST   /api/v1/routes/saved             # Save route
DELETE /api/v1/routes/saved/{id}        # Delete saved route
PUT    /api/v1/routes/saved/{id}/pin    # Toggle pin status
PUT    /api/v1/routes/saved/{id}/use    # Update last used
```

### Driver Verification (KYC)
```
POST   /api/v1/driver/verification/license        # Upload driving license
POST   /api/v1/driver/verification/registration   # Upload RC
POST   /api/v1/driver/verification/vehicle        # Submit vehicle details
POST   /api/v1/driver/verification/complete       # Complete verification
GET    /api/v1/driver/verification/status         # Get KYC status
```

### Vehicle Management
```
GET    /api/v1/vehicles                 # List user vehicles
POST   /api/v1/vehicles                 # Add vehicle
GET    /api/v1/vehicles/{id}            # Get vehicle details
PUT    /api/v1/vehicles/{id}            # Update vehicle
DELETE /api/v1/vehicles/{id}            # Delete vehicle
PUT    /api/v1/vehicles/{id}/default    # Set default vehicle
```

### Payment Methods
```
GET    /api/v1/payment-methods          # List payment methods
POST   /api/v1/payment-methods          # Add payment method
GET    /api/v1/payment-methods/{id}     # Get payment method
PUT    /api/v1/payment-methods/{id}     # Update payment method
DELETE /api/v1/payment-methods/{id}     # Delete payment method
PUT    /api/v1/payment-methods/{id}/default  # Set default
```

### Chat/Messaging
```
GET    /api/v1/chats                    # List user chats
GET    /api/v1/chats/{id}               # Get chat details
POST   /api/v1/chats/{id}/messages      # Send message
GET    /api/v1/chats/{id}/messages      # Get messages
PUT    /api/v1/chats/{id}/read          # Mark as read
```

### Notifications
```
GET    /api/v1/notifications            # List notifications
PUT    /api/v1/notifications/{id}/read  # Mark as read
PUT    /api/v1/notifications/read-all   # Mark all as read
DELETE /api/v1/notifications/{id}       # Delete notification
POST   /api/v1/notifications/token      # Register FCM token
```

### Admin Endpoints
```
GET    /api/v1/admin/kyc/pending        # Pending KYC verifications
PUT    /api/v1/admin/kyc/{id}/approve   # Approve KYC
PUT    /api/v1/admin/kyc/{id}/reject    # Reject KYC
GET    /api/v1/admin/users              # List users
GET    /api/v1/admin/rides              # List all rides
GET    /api/v1/admin/reports            # Get reports
```

---

## 2. Database Schema

### Users Table
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(128) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone_number VARCHAR(20) UNIQUE,
    full_name VARCHAR(255),
    display_name VARCHAR(255),
    bio TEXT,
    photo_url VARCHAR(500),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    user_preference ENUM('driver', 'passenger', 'both') DEFAULT 'passenger',
    onboarding_completed BOOLEAN DEFAULT FALSE,
    profile_completed BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_firebase_uid (firebase_uid),
    INDEX idx_email (email),
    INDEX idx_phone (phone_number)
);
```

### Driver Verifications Table
```sql
CREATE TABLE driver_verifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    dl_number VARCHAR(50),
    dl_expiry_date DATE,
    dl_front_image VARCHAR(500),
    dl_back_image VARCHAR(500),
    rc_number VARCHAR(50),
    rc_front_image VARCHAR(500),
    rc_back_image VARCHAR(500),
    rejection_reason TEXT,
    verified_at TIMESTAMP NULL,
    verified_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);
```

### Vehicles Table
```sql
CREATE TABLE vehicles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    make VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    year YEAR NOT NULL,
    color VARCHAR(50),
    registration_number VARCHAR(50) UNIQUE NOT NULL,
    vehicle_type ENUM('sedan', 'suv', 'hatchback', 'muv', 'compact_suv') NOT NULL,
    seating_capacity INT NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_registration (registration_number)
);
```

### Rides Table
```sql
CREATE TABLE rides (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    driver_id BIGINT UNSIGNED NOT NULL,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    from_location VARCHAR(255) NOT NULL,
    from_latitude DECIMAL(10, 8),
    from_longitude DECIMAL(11, 8),
    to_location VARCHAR(255) NOT NULL,
    to_latitude DECIMAL(10, 8),
    to_longitude DECIMAL(11, 8),
    departure_date DATE NOT NULL,
    departure_time TIME NOT NULL,
    available_seats INT NOT NULL,
    price_per_seat DECIMAL(10, 2) NOT NULL,
    status ENUM('draft', 'published', 'in_progress', 'completed', 'cancelled') DEFAULT 'draft',
    description TEXT,
    preferences JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    INDEX idx_driver (driver_id),
    INDEX idx_status (status),
    INDEX idx_departure (departure_date, departure_time),
    INDEX idx_locations (from_location, to_location)
);
```

### Bookings Table
```sql
CREATE TABLE bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ride_id BIGINT UNSIGNED NOT NULL,
    passenger_id BIGINT UNSIGNED NOT NULL,
    seats_booked INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    payment_method_id BIGINT UNSIGNED,
    cancellation_reason TEXT,
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ride (ride_id),
    INDEX idx_passenger (passenger_id),
    INDEX idx_status (status)
);
```

### Saved Routes Table
```sql
CREATE TABLE saved_routes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    from_location VARCHAR(255) NOT NULL,
    to_location VARCHAR(255) NOT NULL,
    is_pinned BOOLEAN DEFAULT FALSE,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_route (user_id, from_location, to_location),
    INDEX idx_user_id (user_id),
    INDEX idx_pinned (is_pinned)
);
```

### Payment Methods Table
```sql
CREATE TABLE payment_methods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('card', 'upi', 'bank') NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    -- Card fields
    card_type VARCHAR(50),
    card_last_four VARCHAR(4),
    card_expiry VARCHAR(7),
    -- UPI fields
    upi_id VARCHAR(255),
    -- Bank fields
    account_holder_name VARCHAR(255),
    account_last_four VARCHAR(4),
    ifsc_code VARCHAR(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);
```

### Chats Table
```sql
CREATE TABLE chats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ride_id BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE,
    INDEX idx_ride (ride_id)
);
```

### Chat Participants Table
```sql
CREATE TABLE chat_participants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chat_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    last_read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_chat_user (chat_id, user_id),
    INDEX idx_chat (chat_id),
    INDEX idx_user (user_id)
);
```

### Messages Table
```sql
CREATE TABLE messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chat_id BIGINT UNSIGNED NOT NULL,
    sender_id BIGINT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    type ENUM('text', 'image', 'location') DEFAULT 'text',
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_chat (chat_id),
    INDEX idx_sender (sender_id),
    INDEX idx_created (created_at)
);
```

### Notifications Table
```sql
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    data JSON,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
);
```

### FCM Tokens Table
```sql
CREATE TABLE fcm_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL,
    device_type ENUM('android', 'ios') NOT NULL,
    device_id VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token (token),
    INDEX idx_user (user_id),
    INDEX idx_active (is_active)
);
```

### Reviews Table
```sql
CREATE TABLE reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ride_id BIGINT UNSIGNED NOT NULL,
    reviewer_id BIGINT UNSIGNED NOT NULL,
    reviewee_id BIGINT UNSIGNED NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (ride_id, reviewer_id, reviewee_id),
    INDEX idx_reviewee (reviewee_id)
);
```

---

## 3. Authentication Strategy

### Firebase Token Verification in Laravel

#### Install Firebase Admin SDK
```bash
composer require kreait/firebase-php
```

#### Configuration (config/firebase.php)
```php
<?php
return [
    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS'),
    ],
    'database' => [
        'url' => env('FIREBASE_DATABASE_URL'),
    ],
];
```

#### Firebase Auth Middleware
```php
<?php
namespace App\Http\Middleware;

use Closure;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;

class VerifyFirebaseToken
{
    protected $auth;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(config('firebase.credentials.file'));
        $this->auth = $factory->createAuth();
    }

    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        try {
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $uid = $verifiedIdToken->claims()->get('sub');
            
            // Find or create user
            $user = User::where('firebase_uid', $uid)->first();
            
            if (!$user) {
                // Auto-create user from Firebase token
                $user = User::create([
                    'firebase_uid' => $uid,
                    'email' => $verifiedIdToken->claims()->get('email'),
                    'phone_number' => $verifiedIdToken->claims()->get('phone_number'),
                ]);
            }
            
            $request->merge(['user' => $user]);
            auth()->setUser($user);
            
            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }
    }
}
```

#### Register Middleware (app/Http/Kernel.php)
```php
protected $routeMiddleware = [
    'firebase.auth' => \App\Http\Middleware\VerifyFirebaseToken::class,
];
```

#### Protected Routes (routes/api.php)
```php
Route::middleware('firebase.auth')->group(function () {
    Route::prefix('v1')->group(function () {
        // All protected routes here
    });
});
```

---

## 4. Required Background Jobs (Queues)

### Job Classes

#### 1. SendNotificationJob
```php
<?php
namespace App\Jobs;

use App\Models\User;
use Kreait\Firebase\Messaging\CloudMessage;

class SendNotificationJob implements ShouldQueue
{
    public $user;
    public $title;
    public $body;
    public $data;

    public function handle()
    {
        // Send FCM notification
        $messaging = app('firebase.messaging');
        $tokens = $this->user->fcmTokens()->where('is_active', true)->pluck('token');
        
        foreach ($tokens as $token) {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification([
                    'title' => $this->title,
                    'body' => $this->body,
                ])
                ->withData($this->data);
            
            $messaging->send($message);
        }
    }
}
```

#### 2. ProcessKYCDocumentJob
```php
<?php
namespace App\Jobs;

class ProcessKYCDocumentJob implements ShouldQueue
{
    public $verification;
    public $documentType;

    public function handle()
    {
        // OCR processing for document verification
        // Extract data from DL/RC images
        // Validate document authenticity
        // Update verification status
    }
}
```

#### 3. SendRideReminderJob
```php
<?php
namespace App\Jobs;

class SendRideReminderJob implements ShouldQueue
{
    public $ride;

    public function handle()
    {
        // Send reminder 24h before ride
        // Send reminder 1h before ride
        // Notify all participants
    }
}
```

#### 4. CleanupExpiredRidesJob
```php
<?php
namespace App\Jobs;

class CleanupExpiredRidesJob implements ShouldQueue
{
    public function handle()
    {
        // Mark expired rides as cancelled
        // Refund bookings if applicable
        // Send notifications
    }
}
```

### Queue Configuration

#### .env Configuration
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### Supervisor Configuration (/etc/supervisor/conf.d/wayloshare-worker.conf)
```ini
[program:wayloshare-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/wayloshare/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/wayloshare/storage/logs/worker.log
stopwaitsecs=3600
```

#### Start Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start wayloshare-worker:*
```

---

## 5. Redis Usage Requirements

### Cache Configuration
```php
// config/cache.php
'default' => env('CACHE_DRIVER', 'redis'),

'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],
```

### Redis Use Cases

#### 1. Session Storage
```php
// .env
SESSION_DRIVER=redis
```

#### 2. Cache Frequently Accessed Data
```php
// Cache user profile
Cache::remember("user:{$userId}", 3600, function () use ($userId) {
    return User::with('vehicles', 'paymentMethods')->find($userId);
});

// Cache ride search results
Cache::remember("rides:{$from}:{$to}:{$date}", 300, function () {
    return Ride::search($from, $to, $date)->get();
});
```

#### 3. Rate Limiting
```php
// Middleware
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Apply to routes
Route::middleware(['throttle:api'])->group(function () {
    // Routes
});
```

#### 4. Real-time Features (Laravel Echo + Redis)
```php
// Broadcasting configuration
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
],

// Broadcast events
event(new NewMessageEvent($message));
event(new RideStatusUpdated($ride));
event(new BookingConfirmed($booking));
```

#### 5. Queue Management
```php
// Queue configuration
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => 90,
    'block_for' => null,
],
```

---

## 6. Storage Requirements

### Local Storage (Ubuntu VPS)
```
/var/www/wayloshare/storage/
├── app/
│   ├── public/
│   │   ├── profile-photos/
│   │   ├── vehicle-images/
│   │   └── temp/
│   └── private/
│       ├── kyc-documents/
│       │   ├── driving-licenses/
│       │   └── registration-certificates/
│       └── chat-attachments/
├── logs/
└── framework/
```

### Storage Configuration
```php
// config/filesystems.php
'disks' => [
    'local' => [
        'driver' => 'local',
        'root' => storage_path('app'),
    ],
    
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
    
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
    ],
];
```


### File Upload Handling
```php
// app/Http/Controllers/UploadController.php
public function uploadProfilePhoto(Request $request)
{
    $request->validate([
        'photo' => 'required|image|max:5120', // 5MB max
    ]);
    
    $user = auth()->user();
    
    // Delete old photo if exists
    if ($user->photo_url) {
        Storage::disk('public')->delete($user->photo_url);
    }
    
    // Store new photo
    $path = $request->file('photo')->store('profile-photos', 'public');
    
    $user->update(['photo_url' => $path]);
    
    return response()->json([
        'url' => Storage::url($path),
    ]);
}

public function uploadKYCDocument(Request $request)
{
    $request->validate([
        'document' => 'required|image|max:10240', // 10MB max
        'type' => 'required|in:dl_front,dl_back,rc_front,rc_back',
    ]);
    
    // Store in private disk for security
    $path = $request->file('document')->store('kyc-documents', 'private');
    
    return response()->json([
        'path' => $path,
    ]);
}
```

### Permissions
```bash
# Set proper permissions
sudo chown -R www-data:www-data /var/www/wayloshare/storage
sudo chmod -R 775 /var/www/wayloshare/storage
sudo chmod -R 775 /var/www/wayloshare/bootstrap/cache
```

---

## 7. Laravel Folder Structure

```
wayloshare/
├── app/
│   ├── Console/
│   │   └── Kernel.php
│   ├── Events/
│   │   ├── NewMessageEvent.php
│   │   ├── RideStatusUpdated.php
│   │   └── BookingConfirmed.php
│   ├── Exceptions/
│   │   └── Handler.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── V1/
│   │   │   │   │   ├── AuthController.php
│   │   │   │   │   ├── UserController.php
│   │   │   │   │   ├── RideController.php
│   │   │   │   │   ├── BookingController.php
│   │   │   │   │   ├── SavedRouteController.php
│   │   │   │   │   ├── DriverVerificationController.php
│   │   │   │   │   ├── VehicleController.php
│   │   │   │   │   ├── PaymentMethodController.php
│   │   │   │   │   ├── ChatController.php
│   │   │   │   │   ├── NotificationController.php
│   │   │   │   │   └── AdminController.php
│   │   │   └── Controller.php
│   │   ├── Middleware/
│   │   │   ├── VerifyFirebaseToken.php
│   │   │   ├── CheckDriverVerification.php
│   │   │   └── CheckOnboardingCompleted.php
│   │   ├── Requests/
│   │   │   ├── CreateRideRequest.php
│   │   │   ├── CreateBookingRequest.php
│   │   │   ├── UpdateProfileRequest.php
│   │   │   └── DriverVerificationRequest.php
│   │   └── Resources/
│   │       ├── UserResource.php
│   │       ├── RideResource.php
│   │       ├── BookingResource.php
│   │       ├── VehicleResource.php
│   │       └── MessageResource.php
│   ├── Jobs/
│   │   ├── SendNotificationJob.php
│   │   ├── ProcessKYCDocumentJob.php
│   │   ├── SendRideReminderJob.php
│   │   └── CleanupExpiredRidesJob.php
│   ├── Listeners/
│   │   ├── SendNewMessageNotification.php
│   │   ├── SendRideStatusNotification.php
│   │   └── SendBookingConfirmationNotification.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── DriverVerification.php
│   │   ├── Vehicle.php
│   │   ├── Ride.php
│   │   ├── Booking.php
│   │   ├── SavedRoute.php
│   │   ├── PaymentMethod.php
│   │   ├── Chat.php
│   │   ├── ChatParticipant.php
│   │   ├── Message.php
│   │   ├── Notification.php
│   │   ├── FcmToken.php
│   │   └── Review.php
│   ├── Notifications/
│   │   ├── NewBookingNotification.php
│   │   ├── RideReminderNotification.php
│   │   └── KYCStatusNotification.php
│   ├── Policies/
│   │   ├── RidePolicy.php
│   │   ├── BookingPolicy.php
│   │   └── VehiclePolicy.php
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── AuthServiceProvider.php
│   │   ├── BroadcastServiceProvider.php
│   │   ├── EventServiceProvider.php
│   │   └── RouteServiceProvider.php
│   └── Services/
│       ├── FirebaseService.php
│       ├── NotificationService.php
│       ├── PaymentService.php
│       └── KYCVerificationService.php
├── bootstrap/
│   ├── app.php
│   └── cache/
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── broadcasting.php
│   ├── cache.php
│   ├── database.php
│   ├── filesystems.php
│   ├── firebase.php
│   ├── queue.php
│   └── services.php
├── database/
│   ├── factories/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_users_table.php
│   │   ├── 2024_01_01_000002_create_driver_verifications_table.php
│   │   ├── 2024_01_01_000003_create_vehicles_table.php
│   │   ├── 2024_01_01_000004_create_rides_table.php
│   │   ├── 2024_01_01_000005_create_bookings_table.php
│   │   ├── 2024_01_01_000006_create_saved_routes_table.php
│   │   ├── 2024_01_01_000007_create_payment_methods_table.php
│   │   ├── 2024_01_01_000008_create_chats_table.php
│   │   ├── 2024_01_01_000009_create_chat_participants_table.php
│   │   ├── 2024_01_01_000010_create_messages_table.php
│   │   ├── 2024_01_01_000011_create_notifications_table.php
│   │   ├── 2024_01_01_000012_create_fcm_tokens_table.php
│   │   └── 2024_01_01_000013_create_reviews_table.php
│   └── seeders/
│       └── DatabaseSeeder.php
├── public/
│   ├── index.php
│   └── storage -> ../storage/app/public
├── resources/
│   └── views/
├── routes/
│   ├── api.php
│   ├── channels.php
│   ├── console.php
│   └── web.php
├── storage/
│   ├── app/
│   │   ├── public/
│   │   └── private/
│   ├── framework/
│   └── logs/
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env
├── .env.example
├── artisan
├── composer.json
└── composer.lock
```

---

## 8. Deployment Plan (Ubuntu 22.04 VPS)

### Step 1: Server Setup

#### Update System
```bash
sudo apt update && sudo apt upgrade -y
```

#### Install Required Packages
```bash
# Install PHP 8.2 and extensions
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring \
    php8.2-curl php8.2-xml php8.2-bcmath php8.2-redis

# Install Nginx
sudo apt install -y nginx

# Install MySQL
sudo apt install -y mysql-server

# Install Redis
sudo apt install -y redis-server

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Supervisor
sudo apt install -y supervisor

# Install Certbot for SSL
sudo apt install -y certbot python3-certbot-nginx
```

### Step 2: MySQL Configuration

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE wayloshare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'wayloshare_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON wayloshare.* TO 'wayloshare_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 3: Redis Configuration

```bash
# Edit Redis config
sudo nano /etc/redis/redis.conf

# Set maxmemory policy
maxmemory 256mb
maxmemory-policy allkeys-lru

# Restart Redis
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

### Step 4: Deploy Laravel Application

```bash
# Create directory
sudo mkdir -p /var/www/wayloshare
cd /var/www/wayloshare

# Clone repository (or upload files)
git clone https://github.com/your-repo/wayloshare-backend.git .

# Install dependencies
composer install --optimize-autoloader --no-dev

# Set permissions
sudo chown -R www-data:www-data /var/www/wayloshare
sudo chmod -R 775 /var/www/wayloshare/storage
sudo chmod -R 775 /var/www/wayloshare/bootstrap/cache

# Copy environment file
cp .env.example .env
nano .env
```

#### Configure .env
```env
APP_NAME=WayloShare
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://api.wayloshare.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wayloshare
DB_USERNAME=wayloshare_user
DB_PASSWORD=strong_password_here

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

FIREBASE_CREDENTIALS=/var/www/wayloshare/firebase-credentials.json
FIREBASE_DATABASE_URL=https://your-project.firebaseio.com
```

```bash
# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 5: Nginx Configuration

```bash
sudo nano /etc/nginx/sites-available/wayloshare
```

```nginx
server {
    listen 80;
    server_name api.wayloshare.com;
    root /var/www/wayloshare/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Increase upload size for KYC documents
    client_max_body_size 20M;
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/wayloshare /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Step 6: SSL Certificate

```bash
# Obtain SSL certificate
sudo certbot --nginx -d api.wayloshare.com

# Auto-renewal
sudo certbot renew --dry-run
```

### Step 7: Configure Supervisor for Queue Workers

```bash
sudo nano /etc/supervisor/conf.d/wayloshare-worker.conf
```

```ini
[program:wayloshare-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/wayloshare/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/wayloshare/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Start supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start wayloshare-worker:*
```

### Step 8: Configure Cron Jobs

```bash
sudo crontab -e -u www-data
```

```cron
* * * * * cd /var/www/wayloshare && php artisan schedule:run >> /dev/null 2>&1
```

### Step 9: Firewall Configuration

```bash
# Configure UFW
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### Step 10: Monitoring Setup

```bash
# Install monitoring tools
sudo apt install -y htop iotop nethogs

# Setup log rotation
sudo nano /etc/logrotate.d/wayloshare
```

```
/var/www/wayloshare/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

---

## 9. Security Best Practices

### 1. Environment Security

#### Secure .env File
```bash
# Set proper permissions
chmod 600 /var/www/wayloshare/.env
chown www-data:www-data /var/www/wayloshare/.env
```

#### Hide Sensitive Information
```php
// config/app.php
'debug' => env('APP_DEBUG', false),

// Never expose stack traces in production
```

### 2. Database Security

#### Use Prepared Statements (Laravel does this by default)
```php
// Always use Eloquent or Query Builder
User::where('email', $email)->first();

// Never use raw queries with user input
DB::raw("SELECT * FROM users WHERE email = '$email'"); // NEVER DO THIS
```

#### Encrypt Sensitive Data
```php
// app/Models/PaymentMethod.php
use Illuminate\Database\Eloquent\Casts\Encrypted;

protected $casts = [
    'card_last_four' => Encrypted::class,
    'upi_id' => Encrypted::class,
    'account_last_four' => Encrypted::class,
];
```

### 3. API Security

#### Rate Limiting
```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        'throttle:60,1', // 60 requests per minute
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];

// Custom rate limits for sensitive endpoints
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/auth/verify-firebase-token', [AuthController::class, 'verify']);
});
```

#### CORS Configuration
```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'https://wayloshare.com',
        'https://app.wayloshare.com',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

#### Input Validation
```php
// app/Http/Requests/CreateRideRequest.php
public function rules()
{
    return [
        'from_location' => 'required|string|max:255',
        'to_location' => 'required|string|max:255',
        'departure_date' => 'required|date|after:today',
        'departure_time' => 'required|date_format:H:i',
        'available_seats' => 'required|integer|min:1|max:8',
        'price_per_seat' => 'required|numeric|min:0|max:10000',
    ];
}
```

#### SQL Injection Prevention
```php
// Always use parameter binding
$users = DB::table('users')
    ->where('email', '=', $email)
    ->get();

// Or use Eloquent
$users = User::where('email', $email)->get();
```

### 4. File Upload Security

#### Validate File Types
```php
public function uploadKYCDocument(Request $request)
{
    $request->validate([
        'document' => [
            'required',
            'file',
            'mimes:jpg,jpeg,png,pdf',
            'max:10240', // 10MB
        ],
    ]);
    
    // Sanitize filename
    $filename = Str::uuid() . '.' . $request->file('document')->extension();
    
    // Store in private disk
    $path = $request->file('document')->storeAs('kyc-documents', $filename, 'private');
    
    return response()->json(['path' => $path]);
}
```

#### Prevent Directory Traversal
```php
// Never use user input directly in file paths
$filename = basename($request->input('filename')); // Remove directory components
$path = storage_path('app/uploads/' . $filename);
```

### 5. Authentication Security

#### Token Expiration
```php
// Firebase tokens expire automatically
// Implement refresh token mechanism
public function refreshToken(Request $request)
{
    $token = $request->bearerToken();
    
    try {
        $verifiedToken = $this->auth->verifyIdToken($token);
        // Generate new token if needed
    } catch (\Exception $e) {
        return response()->json(['error' => 'Token expired'], 401);
    }
}
```

#### Password Hashing (if implementing local auth)
```php
use Illuminate\Support\Facades\Hash;

// Hash password
$user->password = Hash::make($request->password);

// Verify password
if (Hash::check($request->password, $user->password)) {
    // Password is correct
}
```

### 6. XSS Prevention

#### Escape Output
```php
// Laravel Blade automatically escapes
{{ $user->name }} // Safe

// Raw output (use with caution)
{!! $html !!} // Dangerous - only use for trusted content
```

#### Sanitize Input
```php
use Illuminate\Support\Str;

$clean = Str::of($request->input('bio'))
    ->trim()
    ->limit(500)
    ->stripTags();
```

### 7. CSRF Protection

```php
// Enabled by default for web routes
// API routes typically use token authentication instead
```

### 8. Logging and Monitoring

#### Log Security Events
```php
use Illuminate\Support\Facades\Log;

// Log failed login attempts
Log::warning('Failed login attempt', [
    'email' => $request->email,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
]);

// Log KYC approvals
Log::info('KYC approved', [
    'user_id' => $user->id,
    'admin_id' => auth()->id(),
]);
```

#### Monitor Suspicious Activity
```php
// Implement rate limiting for failed attempts
if (RateLimiter::tooManyAttempts('login:' . $request->ip(), 5)) {
    return response()->json(['error' => 'Too many attempts'], 429);
}

RateLimiter::hit('login:' . $request->ip(), 60);
```

### 9. Dependency Security

```bash
# Regularly update dependencies
composer update

# Check for security vulnerabilities
composer audit

# Use specific versions in composer.json
"require": {
    "laravel/framework": "^10.0",
    "kreait/firebase-php": "^7.0"
}
```

### 10. Server Security

#### Disable Directory Listing
```nginx
# In Nginx config
autoindex off;
```

#### Hide Server Information
```nginx
# In Nginx config
server_tokens off;
```

#### Keep Software Updated
```bash
# Regular updates
sudo apt update && sudo apt upgrade -y

# Security updates only
sudo apt install unattended-upgrades
sudo dpkg-reconfigure --priority=low unattended-upgrades
```

---

## 10. Scalability Roadmap

### Phase 1: Initial Launch (0-1000 users)

#### Current Architecture
- Single VPS server
- MySQL database
- Redis cache
- File storage on local disk

#### Monitoring
```bash
# Install monitoring tools
sudo apt install -y prometheus node-exporter
```

#### Performance Optimization
```php
// Enable query caching
DB::enableQueryLog();

// Use eager loading
$rides = Ride::with(['driver', 'vehicle', 'bookings'])->get();

// Cache frequently accessed data
Cache::remember('popular_routes', 3600, function () {
    return Route::popular()->get();
});
```

### Phase 2: Growth (1000-10000 users)

#### Database Optimization

##### Add Indexes
```sql
-- Add composite indexes for common queries
CREATE INDEX idx_rides_search ON rides(from_location, to_location, departure_date, status);
CREATE INDEX idx_bookings_user_status ON bookings(passenger_id, status);
CREATE INDEX idx_messages_chat_created ON messages(chat_id, created_at);
```

##### Query Optimization
```php
// Use select to limit columns
$users = User::select('id', 'name', 'email')->get();

// Use chunk for large datasets
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});
```

#### Implement CDN
```bash
# Use CloudFlare or AWS CloudFront for static assets
# Configure in .env
CDN_URL=https://cdn.wayloshare.com
```

#### Horizontal Scaling
```bash
# Add load balancer (Nginx)
upstream wayloshare_backend {
    server 192.168.1.10:80;
    server 192.168.1.11:80;
    server 192.168.1.12:80;
}

server {
    listen 80;
    server_name api.wayloshare.com;
    
    location / {
        proxy_pass http://wayloshare_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### Phase 3: Scale (10000-100000 users)

#### Database Sharding
```php
// Implement read replicas
// config/database.php
'mysql' => [
    'read' => [
        'host' => ['192.168.1.20', '192.168.1.21'],
    ],
    'write' => [
        'host' => ['192.168.1.10'],
    ],
    // ... other config
],
```

#### Microservices Architecture
```
- Auth Service (Laravel)
- Ride Service (Laravel)
- Chat Service (Node.js + Socket.io)
- Notification Service (Laravel + FCM)
- Payment Service (Laravel)
```

#### Message Queue Scaling
```bash
# Multiple queue workers
php artisan queue:work --queue=high,default,low --tries=3

# Separate queues for different job types
php artisan queue:work --queue=notifications
php artisan queue:work --queue=kyc-processing
php artisan queue:work --queue=emails
```

#### Caching Strategy
```php
// Multi-layer caching
// 1. Application cache (Redis)
Cache::remember('user:' . $id, 3600, function () use ($id) {
    return User::find($id);
});

// 2. Query result cache
DB::table('rides')->remember(300)->get();

// 3. HTTP cache headers
return response()->json($data)
    ->header('Cache-Control', 'public, max-age=300');
```

### Phase 4: Enterprise (100000+ users)

#### Cloud Migration
```
- AWS/GCP/Azure infrastructure
- Auto-scaling groups
- Managed databases (RDS/Cloud SQL)
- Object storage (S3/Cloud Storage)
- Managed Redis (ElastiCache/MemoryStore)
```

#### Advanced Monitoring
```
- Application Performance Monitoring (New Relic/DataDog)
- Error tracking (Sentry)
- Log aggregation (ELK Stack)
- Uptime monitoring (Pingdom/UptimeRobot)
```

#### Disaster Recovery
```bash
# Automated backups
# Database backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u root -p wayloshare > /backups/wayloshare_$DATE.sql
gzip /backups/wayloshare_$DATE.sql

# Upload to S3
aws s3 cp /backups/wayloshare_$DATE.sql.gz s3://wayloshare-backups/

# Retention policy (keep last 30 days)
find /backups -name "*.sql.gz" -mtime +30 -delete
```

#### Performance Metrics
```php
// Track key metrics
- API response time (target: <200ms)
- Database query time (target: <50ms)
- Cache hit rate (target: >80%)
- Queue processing time (target: <5s)
- Error rate (target: <0.1%)
```

---
Add These To Backend Architecture
1️⃣ Seat Race Condition Protection (CRITICAL)

Add transactional booking logic:

Use DB transactions

Use lockForUpdate() on ride row

Prevent overbooking

Reduce available_seats safely

Also add DB constraint:

CHECK (available_seats >= 0)

And:

UNIQUE (ride_id, passenger_id)

So same user can’t book same ride twice.

2️⃣ Proper Payments Table (MISSING)

Add new table:

payments table

Columns:

id

booking_id (FK)

user_id

gateway (razorpay/stripe)

transaction_id

amount

currency

status (pending, success, failed, refunded)

paid_at

refund_id (nullable)

created_at

updated_at

Add indexes on:

booking_id

transaction_id

status

Also define payment flow:

Booking → Create payment → Confirm booking after payment success.

3️⃣ Audit Logs Table (ADMIN SAFETY)

Add:

audit_logs

Columns:

id

admin_id

action_type

target_type

target_id

old_data (JSON)

new_data (JSON)

ip_address

user_agent

created_at

Track:

KYC approval/rejection

Ride force cancel

User ban/unban

4️⃣ Soft Deletes For Major Tables

Add deleted_at to:

rides

bookings

vehicles

reviews

Use Laravel SoftDeletes trait.

5️⃣ WebSocket / Real-Time Clarification

Add real-time strategy section:

Choose one:

Option A: Pusher (easiest)
Option B: Laravel WebSockets
Option C: Node.js Socket Server

Define clearly how chat + ride status updates will work.

6️⃣ Booking Status Protection Rules

Add logic:

Cannot book if ride.status != 'published'

Cannot cancel completed ride

Cannot complete ride unless in_progress

Define state transition rules.

7️⃣ Role-Based Authorization

Add roles:

user

driver

admin

Implement Laravel Policies properly.

8️⃣ Rate Limiting Strategy

Add:

Strict limit for auth routes

Limit for booking creation

Limit for chat spam

Example:

10 booking attempts per minute per user

9️⃣ Database Performance Improvements

Add composite indexes:

(rides.from_location, rides.to_location, rides.departure_date, rides.status)
(bookings.passenger_id, bookings.status)
(messages.chat_id, messages.created_at)
🔟 Backup Strategy Clarification

Add:

Daily DB backup (mysqldump)

7-day retention

Weekly VPS snapshot (offsite)

Optional S3 remote backup

1️⃣1️⃣ Error Monitoring Strategy

Add:

Log channel separation

Failed job logging

Optional Sentry integration

1️⃣2️⃣ Health Check Endpoint

Add:

GET /api/v1/health

Returns:

DB connection status

Redis status

Queue worker status

Used for uptime monitoring.

## Summary

This backend architecture provides:

✅ **Complete API specification** with 40+ endpoints  
✅ **Comprehensive database schema** with 13 tables  
✅ **Firebase authentication integration**  
✅ **Background job processing** with queues  
✅ **Redis caching** for performance  
✅ **Secure file storage** for KYC documents  
✅ **Production-ready deployment** guide  
✅ **Security best practices** implementation  
✅ **Scalability roadmap** from 0 to 100K+ users  

### Next Steps

1. **Setup Development Environment**
   - Install Laravel
   - Configure Firebase
   - Setup local database

2. **Implement Core Features**
   - Authentication endpoints
   - User management
   - Ride CRUD operations

3. **Testing**
   - Unit tests
   - Integration tests
   - API tests

4. **Deployment**
   - Follow deployment guide
   - Configure production environment
   - Setup monitoring

5. **Optimization**
   - Performance tuning
   - Security hardening
   - Scalability improvements

---

**Document Version**: 1.0  
**Last Updated**: February 2026  
**Status**: Complete Specification

---

**Designed and Developed by Arush Sharma**
