# Flutter Backend Alignment - Design

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Flutter Mobile App                       │
└────────────────────────┬────────────────────────────────────┘
                         │ HTTP/HTTPS
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    API Gateway (Nginx)                      │
│              Rate Limiting, CORS, SSL/TLS                   │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                  Laravel 11 Application                     │
├─────────────────────────────────────────────────────────────┤
│  Middleware Layer                                           │
│  ├─ Authentication (Sanctum)                               │
│  ├─ Authorization (Role-based)                             │
│  ├─ Validation                                             │
│  └─ Error Handling                                         │
├─────────────────────────────────────────────────────────────┤
│  Controller Layer (40+ endpoints)                          │
│  ├─ AuthController                                         │
│  ├─ UserProfileController (NEW)                           │
│  ├─ DriverVerificationController (NEW)                    │
│  ├─ VehicleController (NEW)                               │
│  ├─ BookingController (NEW)                               │
│  ├─ ReviewController (NEW)                                │
│  ├─ ChatController (NEW)                                  │
│  ├─ SavedRouteController (NEW)                            │
│  ├─ NotificationController (NEW)                          │
│  ├─ LocationController (NEW)                              │
│  ├─ PaymentController (NEW)                               │
│  └─ RideController (extended)                             │
├─────────────────────────────────────────────────────────────┤
│  Service Layer (Business Logic)                            │
│  ├─ UserProfileService (NEW)                              │
│  ├─ DriverVerificationService (NEW)                       │
│  ├─ VehicleService (NEW)                                  │
│  ├─ BookingService (NEW)                                  │
│  ├─ ReviewService (NEW)                                   │
│  ├─ ChatService (NEW)                                     │
│  ├─ NotificationService (NEW)                             │
│  ├─ LocationService (NEW)                                 │
│  ├─ PaymentService (NEW)                                  │
│  └─ RideService (extended)                                │
├─────────────────────────────────────────────────────────────┤
│  Model Layer (13 tables)                                   │
│  ├─ User (extended)                                       │
│  ├─ DriverProfile (extended)                              │
│  ├─ DriverVerification (NEW)                              │
│  ├─ Vehicle (NEW)                                         │
│  ├─ Ride (extended)                                       │
│  ├─ Booking (NEW)                                         │
│  ├─ Review (NEW)                                          │
│  ├─ Chat (NEW)                                            │
│  ├─ Message (NEW)                                         │
│  ├─ SavedRoute (NEW)                                      │
│  ├─ FcmToken (NEW)                                        │
│  ├─ RideLocation (NEW)                                    │
│  └─ PaymentMethod (NEW)                                   │
└────────────────────────┬────────────────────────────────────┘
                         │
        ┌────────────────┼────────────────┐
        ▼                ▼                ▼
    ┌────────┐      ┌────────┐      ┌────────┐
    │ MySQL  │      │ Redis  │      │Firebase│
    │ 8.0+   │      │ Cache  │      │ Admin  │
    │        │      │ Queue  │      │ SDK    │
    └────────┘      └────────┘      └────────┘
```

## Database Schema Design

### Extended Tables

#### Users Table (Extended)
```sql
ALTER TABLE users ADD COLUMN (
  display_name VARCHAR(255) NULLABLE,
  date_of_birth DATE NULLABLE,
  gender ENUM('male', 'female', 'other') NULLABLE,
  bio TEXT NULLABLE,
  profile_photo_url VARCHAR(255) NULLABLE,
  user_preference ENUM('driver', 'passenger', 'both') DEFAULT 'passenger',
  onboarding_completed BOOLEAN DEFAULT FALSE,
  profile_completed BOOLEAN DEFAULT FALSE,
  profile_visibility ENUM('public', 'private', 'friends_only') DEFAULT 'public',
  show_phone BOOLEAN DEFAULT TRUE,
  show_email BOOLEAN DEFAULT FALSE,
  allow_messages BOOLEAN DEFAULT TRUE,
  language ENUM('english', 'hindi', 'regional') DEFAULT 'english',
  theme ENUM('light', 'dark', 'auto') DEFAULT 'auto'
);
```

#### DriverProfiles Table (Extended)
```sql
ALTER TABLE driver_profiles ADD COLUMN (
  bio TEXT NULLABLE,
  languages_spoken JSON NULLABLE,
  emergency_contact VARCHAR(20) NULLABLE,
  insurance_provider VARCHAR(255) NULLABLE,
  insurance_policy_number VARCHAR(255) NULLABLE
);
```

#### Rides Table (Extended)
```sql
ALTER TABLE rides ADD COLUMN (
  available_seats INT NULLABLE,
  price_per_seat DECIMAL(10,2) NULLABLE,
  description TEXT NULLABLE,
  preferences JSON NULLABLE,
  ac_available BOOLEAN DEFAULT FALSE,
  wifi_available BOOLEAN DEFAULT FALSE,
  music_preference VARCHAR(255) NULLABLE,
  smoking_allowed BOOLEAN DEFAULT FALSE
);
```

### New Tables

#### DriverVerifications Table
```sql
CREATE TABLE driver_verifications (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  dl_number VARCHAR(255) UNIQUE,
  dl_expiry_date DATE,
  dl_front_image VARCHAR(255),
  dl_back_image VARCHAR(255),
  rc_number VARCHAR(255) UNIQUE,
  rc_front_image VARCHAR(255),
  rc_back_image VARCHAR(255),
  verification_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  rejection_reason TEXT NULLABLE,
  verified_at TIMESTAMP NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (user_id),
  INDEX (verification_status)
);
```

#### Vehicles Table
```sql
CREATE TABLE vehicles (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  vehicle_name VARCHAR(255),
  vehicle_type ENUM('sedan', 'suv', 'hatchback', 'muv', 'compact_suv'),
  license_plate VARCHAR(255) UNIQUE,
  vehicle_color VARCHAR(255),
  vehicle_year INT,
  seating_capacity INT,
  vehicle_photo VARCHAR(255) NULLABLE,
  is_default BOOLEAN DEFAULT FALSE,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (user_id),
  INDEX (is_default)
);
```

#### Bookings Table
```sql
CREATE TABLE bookings (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  ride_id BIGINT NOT NULL,
  passenger_id BIGINT NOT NULL,
  seats_booked INT,
  passenger_name VARCHAR(255),
  passenger_phone VARCHAR(20),
  special_instructions TEXT NULLABLE,
  luggage_info TEXT NULLABLE,
  accessibility_requirements TEXT NULLABLE,
  booking_status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
  cancellation_reason TEXT NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE,
  FOREIGN KEY (passenger_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (ride_id),
  INDEX (passenger_id),
  INDEX (booking_status)
);
```

#### Reviews Table
```sql
CREATE TABLE reviews (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  ride_id BIGINT NOT NULL,
  reviewer_id BIGINT NOT NULL,
  reviewee_id BIGINT NOT NULL,
  rating INT CHECK (rating >= 1 AND rating <= 5),
  comment TEXT NULLABLE,
  categories JSON NULLABLE,
  photos JSON NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (ride_id),
  INDEX (reviewer_id),
  INDEX (reviewee_id)
);
```

#### Chats Table
```sql
CREATE TABLE chats (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  ride_id BIGINT NOT NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE,
  INDEX (ride_id)
);
```

#### Messages Table
```sql
CREATE TABLE messages (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  chat_id BIGINT NOT NULL,
  sender_id BIGINT NOT NULL,
  message TEXT,
  message_type ENUM('text', 'image', 'location') DEFAULT 'text',
  attachment VARCHAR(255) NULLABLE,
  metadata JSON NULLABLE,
  is_read BOOLEAN DEFAULT FALSE,
  read_at TIMESTAMP NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (chat_id),
  INDEX (sender_id),
  INDEX (is_read)
);
```

#### SavedRoutes Table
```sql
CREATE TABLE saved_routes (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  from_location VARCHAR(255),
  to_location VARCHAR(255),
  is_pinned BOOLEAN DEFAULT FALSE,
  last_used_at TIMESTAMP NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (user_id),
  INDEX (is_pinned)
);
```

#### FcmTokens Table
```sql
CREATE TABLE fcm_tokens (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  fcm_token VARCHAR(255) UNIQUE,
  device_type ENUM('android', 'ios'),
  device_id VARCHAR(255) NULLABLE,
  device_name VARCHAR(255) NULLABLE,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (user_id),
  INDEX (is_active)
);
```

#### RideLocations Table
```sql
CREATE TABLE ride_locations (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  ride_id BIGINT NOT NULL,
  latitude DECIMAL(10,7),
  longitude DECIMAL(10,7),
  accuracy DECIMAL(10,2) NULLABLE,
  speed DECIMAL(10,2) NULLABLE,
  heading DECIMAL(10,2) NULLABLE,
  altitude DECIMAL(10,2) NULLABLE,
  timestamp TIMESTAMP,
  created_at TIMESTAMP,
  FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE,
  INDEX (ride_id),
  INDEX (timestamp)
);
```

#### PaymentMethods Table
```sql
CREATE TABLE payment_methods (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  payment_type ENUM('card', 'wallet', 'upi'),
  payment_details LONGTEXT,
  is_default BOOLEAN DEFAULT FALSE,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (user_id),
  INDEX (is_default)
);
```

## API Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    // Response data
  },
  "timestamp": "2024-01-01T00:00:00Z"
}
```

### Error Response
```json
{
  "success": false,
  "error": "Error code",
  "message": "Error description",
  "errors": {
    // Validation errors
  },
  "timestamp": "2024-01-01T00:00:00Z"
}
```

## Error Codes
- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 409: Conflict (Race condition)
- 422: Unprocessable Entity (Validation error)
- 429: Too Many Requests (Rate limited)
- 500: Internal Server Error
- 503: Service Unavailable

## File Upload Strategy
- Store in private disk (outside public directory)
- Generate UUID filenames
- Validate mime types (JPG, PNG, PDF)
- Limit size to 10MB
- Scan for malware (future)
- Return signed URLs for access

## Caching Strategy
- User profiles: 30 minutes
- Ride searches: 5 minutes
- Vehicle list: 1 hour
- Saved routes: 1 hour
- Invalidate on updates

## Queue Strategy
- Send notifications asynchronously
- Process location updates
- Generate reports
- Send emails
- Use Redis backend

## Security Measures
- Encrypt payment data
- Validate all inputs
- Rate limit sensitive endpoints
- Use HTTPS only
- Implement CORS properly
- Log all operations
- Sanitize error messages
- Use prepared statements

## Performance Optimization
- Add database indexes
- Use eager loading
- Implement pagination
- Cache frequently accessed data
- Optimize queries
- Monitor slow queries
- Use database transactions

## Monitoring & Logging
- Log all API requests
- Log all errors
- Monitor response times
- Track error rates
- Monitor database performance
- Alert on anomalies
- Maintain audit trail

## Testing Strategy
- Unit tests for services
- Feature tests for endpoints
- Integration tests for workflows
- Load testing for performance
- Security testing
- API contract testing
