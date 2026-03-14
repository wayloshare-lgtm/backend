# WayloShare Flutter Backend Alignment - Task List

## Phase 1: User Profile Enhancement (13 fields)
- [~] 1.1 Add display_name field to users table
- [ ] 1.2 Add date_of_birth field to users table
- [ ] 1.3 Add gender field to users table (enum: male, female, other)
- [ ] 1.4 Add bio field to users table (max 500 chars)
- [ ] 1.5 Add profile_photo_url field to users table
- [ ] 1.6 Add user_preference field to users table (enum: driver, passenger, both)
- [ ] 1.7 Add onboarding_completed field to users table
- [ ] 1.8 Add profile_completed field to users table
- [ ] 1.9 Create migration for user profile fields
- [ ] 1.10 Update User model with new fields
- [ ] 1.11 Create UserProfileController with update endpoints
- [ ] 1.12 Add profile photo upload endpoint
- [ ] 1.13 Add profile completion endpoint

## Phase 2: Driver Verification System (12 fields)
- [ ] 2.1 Create driver_verifications table migration
- [ ] 2.2 Add dl_number field (driving license)
- [ ] 2.3 Add dl_expiry_date field
- [ ] 2.4 Add dl_front_image field (file storage)
- [ ] 2.5 Add dl_back_image field (file storage)
- [ ] 2.6 Add rc_number field (registration certificate)
- [ ] 2.7 Add rc_front_image field (file storage)
- [ ] 2.8 Add rc_back_image field (file storage)
- [ ] 2.9 Add verification_status field (enum: pending, approved, rejected)
- [ ] 2.10 Create DriverVerification model
- [ ] 2.11 Create DriverVerificationController
- [ ] 2.12 Add KYC status endpoint

## Phase 3: Vehicle Management (8 fields)
- [ ] 3.1 Create vehicles table migration
- [ ] 3.2 Add vehicle_name field
- [ ] 3.3 Add vehicle_type field (enum: sedan, suv, hatchback, muv, compact_suv)
- [ ] 3.4 Add license_plate field (unique)
- [ ] 3.5 Add vehicle_color field
- [ ] 3.6 Add vehicle_year field
- [ ] 3.7 Add seating_capacity field (auto-determined by type)
- [ ] 3.8 Add vehicle_photo field (file storage)
- [ ] 3.9 Create Vehicle model with relationships
- [ ] 3.10 Create VehicleController (CRUD operations)
- [ ] 3.11 Add vehicle list endpoint
- [ ] 3.12 Add set default vehicle endpoint

## Phase 4: Bookings System (10 fields)
- [ ] 4.1 Create bookings table migration
- [ ] 4.2 Add ride_id foreign key
- [ ] 4.3 Add passenger_id foreign key
- [ ] 4.4 Add seats_booked field
- [ ] 4.5 Add passenger_name field
- [ ] 4.6 Add passenger_phone field
- [ ] 4.7 Add special_instructions field
- [ ] 4.8 Add luggage_info field
- [ ] 4.9 Add accessibility_requirements field
- [ ] 4.10 Add booking_status field (enum: pending, confirmed, completed, cancelled)
- [ ] 4.11 Create Booking model
- [ ] 4.12 Create BookingController
- [ ] 4.13 Add create booking endpoint
- [ ] 4.14 Add cancel booking endpoint
- [ ] 4.15 Add booking history endpoint

## Phase 5: Ratings & Reviews System (8 fields)
- [ ] 5.1 Create reviews table migration
- [ ] 5.2 Add ride_id foreign key
- [ ] 5.3 Add reviewer_id foreign key
- [ ] 5.4 Add reviewee_id foreign key
- [ ] 5.5 Add rating field (1-5 integer)
- [ ] 5.6 Add comment field (max 500 chars)
- [ ] 5.7 Add categories field (JSON for category ratings)
- [ ] 5.8 Add photos field (JSON array for review photos)
- [ ] 5.9 Create Review model
- [ ] 5.10 Create ReviewController
- [ ] 5.11 Add rate driver endpoint
- [ ] 5.12 Add rate passenger endpoint
- [ ] 5.13 Add get reviews endpoint

## Phase 6: Chat & Messaging System (6 fields)
- [ ] 6.1 Create chats table migration
- [ ] 6.2 Create chat_participants table migration
- [ ] 6.3 Create messages table migration
- [ ] 6.4 Add message_type field (enum: text, image, location)
- [ ] 6.5 Add attachment field (file storage)
- [ ] 6.6 Add metadata field (JSON)
- [ ] 6.7 Create Chat model
- [ ] 6.8 Create Message model
- [ ] 6.9 Create ChatController
- [ ] 6.10 Add send message endpoint
- [ ] 6.11 Add get messages endpoint
- [ ] 6.12 Add mark as read endpoint

## Phase 7: Saved Routes & Recent Routes (5 fields)
- [ ] 7.1 Create saved_routes table migration
- [ ] 7.2 Add from_location field
- [ ] 7.3 Add to_location field
- [ ] 7.4 Add is_pinned field
- [ ] 7.5 Add last_used_at field
- [ ] 7.6 Create SavedRoute model
- [ ] 7.7 Create SavedRouteController
- [ ] 7.8 Add save route endpoint
- [ ] 7.9 Add get recent routes endpoint
- [ ] 7.10 Add update route endpoint
- [ ] 7.11 Add delete route endpoint

## Phase 8: Notifications & FCM (5 fields)
- [ ] 8.1 Create fcm_tokens table migration
- [ ] 8.2 Add fcm_token field
- [ ] 8.3 Add device_type field (enum: android, ios)
- [ ] 8.4 Add device_id field
- [ ] 8.5 Add device_name field
- [ ] 8.6 Create notification_preferences table
- [ ] 8.7 Create FcmToken model
- [ ] 8.8 Create NotificationController
- [ ] 8.9 Add register FCM token endpoint
- [ ] 8.10 Add update notification preferences endpoint

## Phase 9: Real-time Location Tracking (9 fields)
- [ ] 9.1 Create ride_locations table migration
- [ ] 9.2 Add ride_id foreign key
- [ ] 9.3 Add latitude field
- [ ] 9.4 Add longitude field
- [ ] 9.5 Add accuracy field
- [ ] 9.6 Add speed field
- [ ] 9.7 Add heading field
- [ ] 9.8 Add altitude field
- [ ] 9.9 Add timestamp field
- [ ] 9.10 Create RideLocation model
- [ ] 9.11 Create LocationController
- [ ] 9.12 Add update location endpoint
- [ ] 9.13 Add get location history endpoint

## Phase 10: Payment Methods (5 fields)
- [ ] 10.1 Create payment_methods table migration
- [ ] 10.2 Add user_id foreign key
- [ ] 10.3 Add payment_type field (enum: card, wallet, upi)
- [ ] 10.4 Add payment_details field (encrypted JSON)
- [ ] 10.5 Add is_default field
- [ ] 10.6 Create PaymentMethod model
- [ ] 10.7 Create PaymentController
- [ ] 10.8 Add add payment method endpoint
- [ ] 10.9 Add get payment methods endpoint
- [ ] 10.10 Add delete payment method endpoint

## Phase 11: Ride Offering (Driver Side) (14 fields)
- [ ] 11.1 Extend Ride model for driver offerings
- [ ] 11.2 Add available_seats field
- [ ] 11.3 Add price_per_seat field
- [ ] 11.4 Add description field
- [ ] 11.5 Add preferences field (JSON)
- [ ] 11.6 Add ac_available field
- [ ] 11.7 Add wifi_available field
- [ ] 11.8 Add music_preference field
- [ ] 11.9 Add smoking_allowed field
- [ ] 11.10 Create ride offering endpoint
- [ ] 11.11 Add update ride status endpoint
- [ ] 11.12 Add search available rides endpoint
- [ ] 11.13 Add ride details endpoint
- [ ] 11.14 Add ride cancellation endpoint

## Phase 12: Enhanced User Profile Fields (8 fields)
- [ ] 12.1 Add languages_spoken field to driver_profiles
- [ ] 12.2 Add emergency_contact field
- [ ] 12.3 Add insurance_provider field
- [ ] 12.4 Add insurance_policy_number field
- [ ] 12.5 Add profile_visibility field (enum: public, private, friends_only)
- [ ] 12.6 Add show_phone field
- [ ] 12.7 Add show_email field
- [ ] 12.8 Add allow_messages field
- [ ] 12.9 Create privacy settings endpoint
- [ ] 12.10 Create preferences endpoint

## Phase 13: Data Validation & Security
- [ ] 13.1 Add phone number validation (India format)
- [ ] 13.2 Add email format validation
- [ ] 13.3 Add date validations (DOB, expiry dates)
- [ ] 13.4 Add coordinate validation (lat/lng)
- [ ] 13.5 Add file upload validation (size, type)
- [ ] 13.6 Add rate limiting on sensitive endpoints
- [ ] 13.7 Add encryption for payment data
- [ ] 13.8 Add CORS configuration
- [ ] 13.9 Add input sanitization
- [ ] 13.10 Add request logging

## Phase 14: API Documentation & Testing
- [ ] 14.1 Update API_ENDPOINTS.md with all new endpoints
- [ ] 14.2 Create Postman collection for new endpoints
- [ ] 14.3 Add unit tests for new models
- [ ] 14.4 Add feature tests for new controllers
- [ ] 14.5 Add integration tests for workflows
- [ ] 14.6 Create API documentation for Flutter team
- [ ] 14.7 Add error handling documentation
- [ ] 14.8 Add authentication flow documentation

## Phase 15: Database Optimization & Indexing
- [ ] 15.1 Add indexes to bookings table
- [ ] 15.2 Add indexes to reviews table
- [ ] 15.3 Add indexes to messages table
- [ ] 15.4 Add indexes to fcm_tokens table
- [ ] 15.5 Add indexes to ride_locations table
- [ ] 15.6 Add indexes to saved_routes table
- [ ] 15.7 Add foreign key constraints
- [ ] 15.8 Add cascade delete rules
- [ ] 15.9 Create database optimization migration
- [ ] 15.10 Add query performance monitoring

## Summary Statistics
- **Total Tasks**: 150+
- **Phases**: 15
- **Estimated Duration**: 4-6 weeks
- **Priority**: High (Flutter app depends on this)
