# Flutter App Data Structure - WayloShare

## Complete Data Requirements for Backend API

---

## 1. User Authentication & Profile

### Registration/Login Data
- **firebase_uid**: string, required - Firebase unique identifier
- **phone_number**: string, required - User's phone number (India format)
- **email**: string, optional - User's email address
- **full_name**: string, required - User's full name
- **display_name**: string, optional - Display name for profile
- **date_of_birth**: date, optional - User's DOB (YYYY-MM-DD)
- **gender**: enum, optional - Values: 'male', 'female', 'other'
- **bio**: string, optional - User bio/description (max 500 chars)
- **profile_photo_url**: string, optional - Profile picture URL (local or network)
- **user_preference**: enum, required - Values: 'driver', 'passenger', 'both'
- **onboarding_completed**: boolean, required - Onboarding status
- **profile_completed**: boolean, required - Profile completion status

### Profile Update Data
- **full_name**: string, optional
- **display_name**: string, optional
- **date_of_birth**: date, optional
- **gender**: enum, optional
- **bio**: string, optional
- **profile_photo**: file, optional - Image file (max 5MB)
- **user_preference**: enum, optional

---

## 2. Ride Search & Request

### Search Rides Request
- **from_location**: string, required - Pickup location name
- **from_latitude**: decimal, required - Pickup latitude
- **from_longitude**: decimal, required - Pickup longitude
- **to_location**: string, required - Dropoff location name
- **to_latitude**: decimal, required - Dropoff latitude
- **to_longitude**: decimal, required - Dropoff longitude
- **departure_date**: date, required - Travel date (YYYY-MM-DD)
- **departure_time**: time, optional - Preferred departure time (HH:MM)
- **passenger_count**: integer, optional - Number of passengers (default: 1)
- **vehicle_preference**: enum, optional - Preferred vehicle type (sedan, suv, hatchback, muv, compact_suv)
- **promo_code**: string, optional - Discount code if applicable

### Create Booking Request
- **ride_id**: integer, required - ID of the ride to book
- **seats_booked**: integer, required - Number of seats (1-8)
- **passenger_name**: string, required - Passenger name
- **passenger_phone**: string, required - Passenger phone number
- **special_instructions**: string, optional - Special requests/notes
- **payment_method_id**: integer, optional - Selected payment method ID
- **luggage_info**: string, optional - Luggage/cargo details
- **accessibility_requirements**: string, optional - Accessibility needs

---

## 3. Driver Profile & Verification

### Driver Verification - Driving License
- **dl_number**: string, required - License number
- **dl_expiry_date**: date, required - License expiry (YYYY-MM-DD)
- **dl_front_image**: file, required - Front side photo (max 10MB)
- **dl_back_image**: file, required - Back side photo (max 10MB)

### Driver Verification - Registration Certificate
- **rc_number**: string, required - Registration certificate number
- **rc_front_image**: file, required - Front side photo (max 10MB)
- **rc_back_image**: file, required - Back side photo (max 10MB)

### Driver Verification - Vehicle Details
- **vehicle_name**: string, required - Vehicle name/model
- **vehicle_type**: enum, required - Type: sedan, suv, hatchback, muv, compact_suv
- **license_plate**: string, required - Vehicle registration number
- **vehicle_color**: string, required - Vehicle color
- **vehicle_year**: integer, required - Manufacturing year
- **seating_capacity**: integer, required - Number of seats (auto-determined by vehicle_type)
- **vehicle_photo**: file, optional - Vehicle photo (max 10MB)

### Driver Profile Update
- **bio**: string, optional - Driver bio
- **languages_spoken**: array, optional - Languages: ['english', 'hindi', 'regional']
- **vehicle_id**: integer, optional - Default vehicle ID
- **emergency_contact**: string, optional - Emergency contact number
- **insurance_provider**: string, optional - Insurance company name
- **insurance_policy_number**: string, optional - Policy number

---

## 4. Ride Offering (Driver Side)

### Create/Offer Ride
- **vehicle_id**: integer, required - Vehicle to use for ride
- **from_location**: string, required - Pickup location
- **from_latitude**: decimal, required - Pickup latitude
- **from_longitude**: decimal, required - Pickup longitude
- **to_location**: string, required - Dropoff location
- **to_latitude**: decimal, required - Dropoff latitude
- **to_longitude**: decimal, required - Dropoff longitude
- **departure_date**: date, required - Travel date
- **departure_time**: time, required - Departure time (HH:MM)
- **available_seats**: integer, required - Available seats (1-8)
- **price_per_seat**: decimal, required - Price per seat (₹)
- **description**: string, optional - Ride description/notes
- **preferences**: json, optional - Ride preferences (smoking, music, etc.)
- **ac_available**: boolean, optional - AC availability
- **wifi_available**: boolean, optional - WiFi availability
- **music_preference**: string, optional - Music preference
- **smoking_allowed**: boolean, optional - Smoking policy

### Update Ride Status
- **status**: enum, required - Values: draft, published, in_progress, completed, cancelled
- **cancellation_reason**: string, optional - Reason for cancellation

---

## 5. Ride Completion & Ratings

### Complete Ride (Driver)
- **ride_id**: integer, required
- **actual_distance_km**: decimal, required - Actual distance traveled
- **actual_duration_minutes**: integer, required - Actual duration
- **final_amount**: decimal, required - Final amount charged
- **toll_amount**: decimal, optional - Toll charges
- **driver_notes**: string, optional - Driver's notes about ride

### Complete Ride (Passenger)
- **ride_id**: integer, required
- **booking_id**: integer, required
- **passenger_notes**: string, optional - Passenger's notes

### Rate Driver (Passenger)
- **ride_id**: integer, required
- **booking_id**: integer, required
- **rating**: integer, required - Rating 1-5 stars
- **comment**: string, optional - Review comment (max 500 chars)
- **categories**: json, optional - Category ratings (cleanliness, driving, safety, etc.)
- **photos**: array, optional - Evidence photos (max 3)

### Rate Passenger (Driver)
- **ride_id**: integer, required
- **passenger_id**: integer, required
- **rating**: integer, required - Rating 1-5 stars
- **comment**: string, optional - Review comment (max 500 chars)
- **categories**: json, optional - Category ratings

---

## 7. Saved Routes

### Save Route
- **from_location**: string, required - Pickup location
- **to_location**: string, required - Dropoff location
- **is_pinned**: boolean, optional - Pin to top (default: false)

### Update Saved Route
- **id**: integer, required - Route ID
- **is_pinned**: boolean, optional - Toggle pin status
- **last_used_at**: timestamp, optional - Update last used time

### Delete Saved Route
- **id**: integer, required - Route ID

---

## 8. Chat & Messaging

### Send Message
- **chat_id**: integer, required - Chat room ID
- **message**: string, required - Message text (max 5000 chars)
- **type**: enum, optional - Values: text, image, location (default: text)
- **attachment**: file, optional - Image/file attachment (max 10MB)
- **metadata**: json, optional - Additional data (location coordinates, etc.)

### Get Messages
- **chat_id**: integer, required
- **limit**: integer, optional - Number of messages (default: 50)
- **offset**: integer, optional - Pagination offset

### Mark Chat as Read
- **chat_id**: integer, required
- **read_at**: timestamp, optional - Read timestamp

---

## 9. Notifications

### Register FCM Token
- **fcm_token**: string, required - Firebase Cloud Messaging token
- **device_type**: enum, required - Values: android, ios
- **device_id**: string, optional - Device identifier
- **device_name**: string, optional - Device name

### Update Notification Preferences
- **notifications_enabled**: boolean, optional
- **ride_updates**: boolean, optional
- **messages**: boolean, optional
- **promotions**: boolean, optional
- **kyc_updates**: boolean, optional

---

## 10. Saved Routes (Recent Routes)

### Get Recent Routes
- **limit**: integer, optional - Number of routes (default: 10)

### Update Route Usage
- **route_id**: integer, required
- **last_used_at**: timestamp, required

---

## 11. KYC & Driver Verification

### Get KYC Status
- **user_id**: integer, required

### Submit KYC Documents
- **verification_data**: object, required
  - **dl_number**: string
  - **dl_expiry_date**: date
  - **dl_front_image**: file
  - **dl_back_image**: file
  - **rc_number**: string
  - **rc_front_image**: file
  - **rc_back_image**: file
  - **vehicle_details**: object
    - **vehicle_type**: enum
    - **license_plate**: string
    - **vehicle_color**: string
    - **vehicle_year**: integer
    - **seating_capacity**: integer

---

## 12. Vehicle Management

### Add Vehicle
- **vehicle_name**: string, required
- **license_plate**: string, required
- **vehicle_color**: string, required
- **vehicle_year**: integer, required
- **vehicle_type**: enum, required
- **seating_capacity**: integer, required (auto-determined)
- **is_default**: boolean, optional

### Update Vehicle
- **id**: integer, required
- **vehicle_name**: string, optional
- **vehicle_color**: string, optional
- **is_default**: boolean, optional

### Delete Vehicle
- **id**: integer, required

---

## 13. Profile Settings

### Update Privacy Settings
- **profile_visibility**: enum, optional - Values: public, private, friends_only
- **show_phone**: boolean, optional
- **show_email**: boolean, optional
- **allow_messages**: boolean, optional

### Update Preferences
- **language**: enum, optional - Values: english, hindi, regional
- **theme**: enum, optional - Values: light, dark, auto
- **currency**: enum, optional - Values: INR (default)

---

## 14. Location Updates (Real-time)

### Driver Location Update (Frequent)
- **ride_id**: integer, required - Current ride ID
- **latitude**: decimal, required - Current latitude
- **longitude**: decimal, required - Current longitude
- **accuracy**: decimal, optional - GPS accuracy in meters
- **speed**: decimal, optional - Current speed (km/h)
- **heading**: decimal, optional - Direction (0-360 degrees)
- **altitude**: decimal, optional - Altitude in meters
- **timestamp**: timestamp, required - Update timestamp

---

## 15. Booking Management

### Cancel Booking
- **booking_id**: integer, required
- **cancellation_reason**: string, optional
- **refund_reason**: string, optional

### Get Booking History
- **limit**: integer, optional - Number of bookings (default: 20)
- **offset**: integer, optional - Pagination offset
- **status**: enum, optional - Filter by status

---

## Summary Statistics

### Total Data Fields
- **User Profile**: 13 fields
- **Ride Search/Booking**: 10 fields
- **Driver Verification**: 12 fields
- **Ride Offering**: 14 fields
- **Ride Completion**: 8 fields
- **Ratings**: 8 fields
- **Chat/Messaging**: 6 fields
- **Notifications**: 5 fields
- **Location Updates**: 9 fields
- **Other Operations**: 15+ fields

**Total: 100+ data fields across all operations**

### Database Tables Required
1. users
2. driver_verifications
3. vehicles
4. rides
5. bookings
6. saved_routes
7. chats
8. chat_participants
9. messages
10. notifications
11. fcm_tokens
12. reviews
13. ride_locations (for tracking)

### New API Endpoints Needed
- 40+ endpoints (already defined in BACKEND_ARCHITECTURE.md)

### Key Validations Required
- Phone number format (India: +91 XXXXX XXXXX)
- Email format validation
- Date validations (DOB, expiry dates)
- Coordinate validation (latitude -90 to 90, longitude -180 to 180)
- Payment data encryption
- File upload size limits
- Rate limiting on sensitive endpoints

### Security Considerations
- Encrypt sensitive user data
- Validate all file uploads
- Implement rate limiting
- Use HTTPS for all API calls
- Validate Firebase tokens
- Implement proper CORS

---

## Implementation Notes

### Frontend to Backend Flow

1. **User Registration**
   - Collect: phone, name, DOB, gender, preference
   - Send to: `/api/v1/auth/register`
   - Receive: firebase_uid, auth_token

2. **Profile Setup**
   - Collect: profile photo, bio, preferences
   - Send to: `/api/v1/user/profile`
   - Store: profile_photo_url in Firebase Storage

3. **Ride Search**
   - Collect: from/to locations, date, time, passenger count
   - Send to: `/api/v1/rides` (GET with filters)
   - Receive: list of available rides

4. **Booking**
   - Collect: ride_id, seats, special instructions
   - Send to: `/api/v1/bookings` (POST)
   - Receive: booking_id, payment_url

5. **Ride Completion**
   - Collect: rating, review, photos
   - Send to: `/api/v1/reviews` (POST)
   - Receive: confirmation

### Data Validation Rules

| Field | Validation |
|-------|-----------|
| phone_number | Must be 10 digits (India) |
| email | Valid email format |
| date_of_birth | Must be 18+ years old |
| latitude | -90 to 90 |
| longitude | -180 to 180 |
| rating | 1-5 integer |
| price_per_seat | > 0, max 10000 |
| seats_booked | 1-8 |

---

**Document Version**: 1.0  
**Last Updated**: February 2026  
**Status**: Complete Specification

---

**Designed and Developed by Arush Sharma**
