# Flutter Backend Alignment - Requirements

## Overview
Align the WayloShare backend with Flutter app requirements by implementing 100+ missing data fields across 13 new database tables and 40+ new API endpoints.

## Current State
- ✅ Basic authentication (Firebase + Sanctum)
- ✅ Ride request/acceptance/completion
- ✅ Driver profile (basic)
- ✅ Fare calculation
- ✅ Health check

## Target State
- Complete user profile system (13 fields)
- Driver verification & KYC (12 fields)
- Vehicle management (8 fields)
- Bookings system (10 fields)
- Ratings & reviews (8 fields)
- Chat & messaging (6 fields)
- Saved routes (5 fields)
- Notifications & FCM (5 fields)
- Real-time location tracking (9 fields)
- Payment methods (5 fields)
- Enhanced ride offering (14 fields)
- Privacy & preferences (8 fields)

## Key Requirements

### 1. User Profile Enhancement
- Extend users table with 8 new fields
- Support profile photo uploads
- Track onboarding and profile completion status
- Support user preferences (driver/passenger/both)

### 2. Driver Verification
- Create driver_verifications table
- Support document uploads (DL, RC, vehicle photos)
- Track verification status (pending/approved/rejected)
- Store insurance information

### 3. Vehicle Management
- Create vehicles table with vehicle types
- Auto-determine seating capacity by type
- Support vehicle photos
- Allow multiple vehicles per driver

### 4. Bookings System
- Create bookings table
- Track passenger details and special requests
- Support luggage and accessibility info
- Track booking status

### 5. Ratings & Reviews
- Create reviews table
- Support 1-5 star ratings
- Allow category-based ratings
- Support review photos

### 6. Chat & Messaging
- Create chats and messages tables
- Support text, image, and location messages
- Track message read status
- Support attachments

### 7. Saved Routes
- Create saved_routes table
- Track frequently used routes
- Support pinning favorite routes
- Track last used timestamp

### 8. Notifications
- Create fcm_tokens table
- Support Android and iOS devices
- Track notification preferences
- Support different notification types

### 9. Location Tracking
- Create ride_locations table
- Track real-time driver location
- Store GPS accuracy and speed
- Support location history

### 10. Payment Methods
- Create payment_methods table
- Support multiple payment types (card, wallet, UPI)
- Encrypt payment details
- Track default payment method

## Data Validation Rules

| Field | Validation |
|-------|-----------|
| phone_number | 10 digits (India format) |
| email | Valid email format |
| date_of_birth | 18+ years old |
| latitude | -90 to 90 |
| longitude | -180 to 180 |
| rating | 1-5 integer |
| price_per_seat | > 0, max 10000 |
| seats_booked | 1-8 |
| file_size | Max 10MB |
| file_types | JPG, PNG, PDF only |

## Security Requirements
- Encrypt payment data
- Validate all file uploads
- Implement rate limiting
- Use HTTPS for all API calls
- Validate Firebase tokens
- Implement proper CORS
- Sanitize all user input
- Log all sensitive operations

## Performance Requirements
- API response time: <200ms
- Database query time: <50ms
- Queue processing time: <5s
- Error rate: <0.1%
- Ride acceptance success rate: >99%

## API Endpoint Categories

### Authentication (4 endpoints)
- POST /api/v1/auth/login
- GET /api/v1/auth/me
- POST /api/v1/auth/logout
- DELETE /api/v1/auth/delete-account

### User Profile (8 endpoints)
- GET /api/v1/user/profile
- POST /api/v1/user/profile
- POST /api/v1/user/profile/photo
- POST /api/v1/user/complete-onboarding
- GET /api/v1/user/preferences
- POST /api/v1/user/preferences
- GET /api/v1/user/privacy
- POST /api/v1/user/privacy

### Driver Verification (6 endpoints)
- POST /api/v1/driver/verification
- GET /api/v1/driver/verification/status
- POST /api/v1/driver/verification/documents
- GET /api/v1/driver/verification/documents
- POST /api/v1/driver/verification/submit
- GET /api/v1/driver/kyc-status

### Vehicles (6 endpoints)
- POST /api/v1/vehicles
- GET /api/v1/vehicles
- GET /api/v1/vehicles/{id}
- PUT /api/v1/vehicles/{id}
- DELETE /api/v1/vehicles/{id}
- POST /api/v1/vehicles/{id}/set-default

### Rides (12 endpoints)
- POST /api/v1/rides (request)
- GET /api/v1/rides (search)
- GET /api/v1/rides/{id}
- POST /api/v1/rides/{id}/accept
- POST /api/v1/rides/{id}/arrive
- POST /api/v1/rides/{id}/start
- POST /api/v1/rides/{id}/complete
- POST /api/v1/rides/{id}/cancel
- POST /api/v1/rides/offer (driver offering)
- GET /api/v1/rides/available (search available)
- POST /api/v1/rides/{id}/update-status
- GET /api/v1/rides/{id}/history

### Bookings (6 endpoints)
- POST /api/v1/bookings
- GET /api/v1/bookings
- GET /api/v1/bookings/{id}
- POST /api/v1/bookings/{id}/cancel
- GET /api/v1/bookings/history
- GET /api/v1/bookings/{id}/details

### Reviews (4 endpoints)
- POST /api/v1/reviews
- GET /api/v1/reviews/{id}
- GET /api/v1/reviews/user/{user_id}
- GET /api/v1/reviews/ride/{ride_id}

### Chat (6 endpoints)
- POST /api/v1/chats
- GET /api/v1/chats
- POST /api/v1/chats/{id}/messages
- GET /api/v1/chats/{id}/messages
- POST /api/v1/chats/{id}/mark-read
- DELETE /api/v1/chats/{id}

### Saved Routes (5 endpoints)
- POST /api/v1/saved-routes
- GET /api/v1/saved-routes
- POST /api/v1/saved-routes/{id}/pin
- PUT /api/v1/saved-routes/{id}
- DELETE /api/v1/saved-routes/{id}

### Notifications (4 endpoints)
- POST /api/v1/notifications/fcm-token
- GET /api/v1/notifications/preferences
- POST /api/v1/notifications/preferences
- GET /api/v1/notifications

### Location (3 endpoints)
- POST /api/v1/locations/update
- GET /api/v1/locations/history/{ride_id}
- GET /api/v1/locations/current/{ride_id}

### Payment (5 endpoints)
- POST /api/v1/payment-methods
- GET /api/v1/payment-methods
- PUT /api/v1/payment-methods/{id}
- DELETE /api/v1/payment-methods/{id}
- POST /api/v1/payment-methods/{id}/set-default

## Database Tables (13 total)
1. users (extended)
2. driver_profiles (extended)
3. driver_verifications (new)
4. vehicles (new)
5. rides (extended)
6. bookings (new)
7. reviews (new)
8. chats (new)
9. messages (new)
10. saved_routes (new)
11. fcm_tokens (new)
12. ride_locations (new)
13. payment_methods (new)

## Implementation Phases
1. User Profile Enhancement (Phase 1)
2. Driver Verification (Phase 2)
3. Vehicle Management (Phase 3)
4. Bookings System (Phase 4)
5. Ratings & Reviews (Phase 5)
6. Chat & Messaging (Phase 6)
7. Saved Routes (Phase 7)
8. Notifications & FCM (Phase 8)
9. Location Tracking (Phase 9)
10. Payment Methods (Phase 10)
11. Ride Offering (Phase 11)
12. Enhanced Profile Fields (Phase 12)
13. Data Validation & Security (Phase 13)
14. API Documentation & Testing (Phase 14)
15. Database Optimization (Phase 15)

## Success Criteria
- All 150+ tasks completed
- All 40+ endpoints implemented
- All 13 database tables created
- 100+ data fields supported
- All validations implemented
- Security hardening complete
- API documentation complete
- Postman collection updated
- Unit tests passing
- Feature tests passing
- Integration tests passing
- Performance targets met
- Zero critical bugs
- Flutter app integration successful

## Timeline
- **Estimated Duration**: 4-6 weeks
- **Team Size**: 1-2 developers
- **Sprint Duration**: 1 week per 2-3 phases
- **Testing**: Continuous throughout
- **Deployment**: After each phase completion

## Dependencies
- Laravel 11 framework
- MySQL 8.0+ database
- Redis cache
- Firebase Admin SDK
- Sanctum authentication
- File storage system
- Queue system (for notifications)

## Risks & Mitigation
- **Risk**: Large scope might cause delays
  - **Mitigation**: Break into phases, prioritize critical features
- **Risk**: Database migrations might fail
  - **Mitigation**: Test migrations locally first, create rollback plans
- **Risk**: API changes might break existing clients
  - **Mitigation**: Maintain backward compatibility, version API
- **Risk**: Performance degradation with new tables
  - **Mitigation**: Add proper indexes, optimize queries, monitor performance

## Next Steps
1. Review and approve requirements
2. Start Phase 1: User Profile Enhancement
3. Create migrations for Phase 1
4. Implement Phase 1 models and controllers
5. Add Phase 1 API endpoints
6. Test Phase 1 endpoints
7. Move to Phase 2
