# Flutter API Endpoint Reference

## Complete Endpoint Listing

### Authentication Endpoints

#### 1. Login with Firebase Token
```
POST /api/v1/auth/login
```
**Headers:**
- `Authorization: Bearer {firebase_id_token}`
- `Content-Type: application/json`

**Response (200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "firebase_uid": "abc123...",
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "role": "rider"
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz..."
}
```

#### 2. Get Current User
```
GET /api/v1/auth/me
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "role": "rider"
  }
}
```

#### 3. Logout
```
POST /api/v1/auth/logout
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

#### 4. Delete Account
```
DELETE /api/v1/auth/delete-account
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Account deleted successfully"
}
```

### User Profile Endpoints

#### 5. Get User Profile
```
GET /api/v1/user/profile
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "display_name": "John",
    "email": "john@example.com",
    "phone": "9876543210",
    "date_of_birth": "1990-01-01",
    "gender": "male",
    "bio": "Love traveling",
    "profile_photo_url": "https://example.com/photos/user1.jpg",
    "user_preference": "both",
    "onboarding_completed": true,
    "profile_completed": true
  }
}
```

#### 6. Update User Profile
```
POST /api/v1/user/profile
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "display_name": "John",
  "date_of_birth": "1990-01-01",
  "gender": "male",
  "bio": "Love traveling",
  "user_preference": "both"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": { /* updated profile */ }
}
```

#### 7. Upload Profile Photo
```
POST /api/v1/user/profile/photo
Authorization: Bearer {sanctum_token}
Content-Type: multipart/form-data
```

**Request Body:**
```
profile_photo: [image file - JPG/PNG, max 10MB]
```

**Response (200):**
```json
{
  "success": true,
  "message": "Profile photo uploaded successfully",
  "profile_photo_url": "https://example.com/storage/profiles/uuid.jpg"
}
```

#### 8. Complete Onboarding
```
POST /api/v1/user/complete-onboarding
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Onboarding completed successfully"
}
```

#### 9. Get User Preferences
```
GET /api/v1/user/preferences
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "language": "english",
    "theme": "light",
    "notifications_enabled": true
  }
}
```

#### 10. Update User Preferences
```
POST /api/v1/user/preferences
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "language": "english",
  "theme": "dark",
  "notifications_enabled": true
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Preferences updated successfully",
  "data": { /* updated preferences */ }
}
```

#### 11. Get Privacy Settings
```
GET /api/v1/user/privacy
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "profile_visibility": "public",
    "show_phone": true,
    "show_email": false,
    "allow_messages": true
  }
}
```

#### 12. Update Privacy Settings
```
POST /api/v1/user/privacy
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "profile_visibility": "public",
  "show_phone": true,
  "show_email": false,
  "allow_messages": true
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Privacy settings updated successfully",
  "data": { /* updated settings */ }
}
```

### Vehicle Endpoints

#### 13. Create Vehicle
```
POST /api/v1/vehicles
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "vehicle_name": "My Sedan",
  "vehicle_type": "sedan",
  "license_plate": "KA01AB1234",
  "vehicle_color": "Black",
  "vehicle_year": 2023
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Vehicle created successfully",
  "data": {
    "id": 1,
    "vehicle_name": "My Sedan",
    "vehicle_type": "sedan",
    "license_plate": "KA01AB1234",
    "vehicle_color": "Black",
    "vehicle_year": 2023,
    "seating_capacity": 5,
    "is_default": false
  }
}
```

#### 14. List Vehicles
```
GET /api/v1/vehicles
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [ /* array of vehicles */ ],
  "count": 2
}
```

#### 15. Get Vehicle Details
```
GET /api/v1/vehicles/{id}
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": { /* vehicle details */ }
}
```

#### 16. Update Vehicle
```
PUT /api/v1/vehicles/{id}
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "vehicle_name": "Updated Name",
  "vehicle_color": "Red"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Vehicle updated successfully",
  "data": { /* updated vehicle */ }
}
```

#### 17. Delete Vehicle
```
DELETE /api/v1/vehicles/{id}
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Vehicle deleted successfully"
}
```

#### 18. Upload Vehicle Photo
```
POST /api/v1/vehicles/{id}/photo
Authorization: Bearer {sanctum_token}
Content-Type: multipart/form-data
```

**Request Body:**
```
vehicle_photo: [image file - JPG/PNG, max 10MB]
```

**Response (200):**
```json
{
  "success": true,
  "message": "Vehicle photo uploaded successfully",
  "vehicle_photo_url": "https://example.com/storage/vehicles/uuid.jpg"
}
```

#### 19. Set Default Vehicle
```
POST /api/v1/vehicles/{id}/set-default
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Vehicle set as default successfully"
}
```

### Driver Verification Endpoints

#### 20. Submit Verification Documents
```
POST /api/v1/driver/verification
Authorization: Bearer {sanctum_token}
Content-Type: multipart/form-data
```

**Request Body:**
```
dl_number: "DL0123456789"
dl_expiry_date: "2025-12-31"
dl_front_image: [image file]
dl_back_image: [image file]
rc_number: "RC0123456789"
rc_front_image: [image file]
rc_back_image: [image file]
```

**Response (201):**
```json
{
  "success": true,
  "message": "Verification documents submitted successfully",
  "data": {
    "id": 1,
    "verification_status": "pending"
  }
}
```

#### 21. Get Verification Status
```
GET /api/v1/driver/verification/status
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "verification_status": "approved",
    "verified_at": "2024-01-15T10:30:00Z"
  }
}
```

#### 22. Get KYC Status
```
GET /api/v1/driver/kyc-status
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "kyc_status": "approved",
    "dl_verified": true,
    "rc_verified": true
  }
}
```

### Booking Endpoints

#### 23. Create Booking
```
POST /api/v1/bookings
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "ride_id": 1,
  "seats_booked": 2,
  "passenger_name": "John Doe",
  "passenger_phone": "9876543210",
  "special_instructions": "Please wait at entrance",
  "luggage_info": "1 small bag",
  "accessibility_requirements": "Wheelchair accessible"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Booking created successfully",
  "data": { /* booking details */ }
}
```

#### 24. List Bookings
```
GET /api/v1/bookings?page=1&per_page=15&status=pending
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [ /* bookings */ ],
  "pagination": { /* pagination info */ }
}
```

#### 25. Get Booking Details
```
GET /api/v1/bookings/{id}
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": { /* booking details */ }
}
```

#### 26. Cancel Booking
```
POST /api/v1/bookings/{id}/cancel
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "cancellation_reason": "Change of plans"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Booking cancelled successfully",
  "data": { /* updated booking */ }
}
```

#### 27. Get Booking History
```
GET /api/v1/bookings/history?page=1&per_page=15
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [ /* booking history */ ],
  "pagination": { /* pagination info */ }
}
```

### Review Endpoints

#### 28. Create Review
```
POST /api/v1/reviews
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "ride_id": 1,
  "reviewee_id": 2,
  "rating": 5,
  "comment": "Great ride!",
  "categories": {
    "cleanliness": 5,
    "driving": 4,
    "communication": 5,
    "comfort": 4
  }
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Review created successfully",
  "data": { /* review details */ }
}
```

#### 29. Get Review
```
GET /api/v1/reviews/{id}
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": { /* review details */ }
}
```

#### 30. Get Reviews for User
```
GET /api/v1/reviews/user/{user_id}?page=1&per_page=15
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [ /* reviews */ ],
  "pagination": { /* pagination info */ }
}
```

#### 31. Get Reviews for Ride
```
GET /api/v1/reviews/ride/{ride_id}
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [ /* reviews */ ]
}
```

### Chat & Messaging Endpoints

#### 32. Create Chat
```
POST /api/v1/chats
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "ride_id": 1
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Chat created successfully",
  "data": { /* chat details */ }
}
```

#### 33. List Chats
```
GET /api/v1/chats
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [ /* chats with messages */ ]
}
```

#### 34. Send Message
```
POST /api/v1/chats/{chat_id}/messages
Authorization: Bearer {sanctum_token}
Content-Type: multipart/form-data
```

**Request Body:**
```
message: "Hello!"
message_type: "text"
attachment: [optional file - JPG/PNG/PDF, max 10MB]
metadata: {"key": "value"}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Message sent successfully",
  "data": { /* message details */ }
}
```

#### 35. Get Messages
```
GET /api/v1/chats/{chat_id}/messages?per_page=20
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "data": [ /* messages */ ],
    "pagination": { /* pagination info */ }
  }
}
```

#### 36. Mark Messages as Read
```
POST /api/v1/chats/{chat_id}/mark-read
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Messages marked as read"
}
```

#### 37. Delete Chat
```
DELETE /api/v1/chats/{chat_id}
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Chat deleted successfully"
}
```

### Saved Routes Endpoints

#### 38. Save Route
```
POST /api/v1/saved-routes
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "from_location": "123 Main St, Delhi",
  "to_location": "456 Park Ave, Delhi"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Route saved successfully",
  "data": { /* route details */ }
}
```

#### 39. List Saved Routes
```
GET /api/v1/saved-routes
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [ /* saved routes */ ]
}
```

#### 40. Pin Route
```
POST /api/v1/saved-routes/{id}/pin
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Route pinned successfully"
}
```

#### 41. Update Route
```
PUT /api/v1/saved-routes/{id}
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "from_location": "Updated location",
  "to_location": "Updated location"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Route updated successfully",
  "data": { /* updated route */ }
}
```

#### 42. Delete Route
```
DELETE /api/v1/saved-routes/{id}
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Route deleted successfully"
}
```

### Notification Endpoints

#### 43. Register FCM Token
```
POST /api/v1/notifications/fcm-token
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "fcm_token": "eJxlj91uwjAMhV9l5XoXEgMSV0hIXCBNV5MmTrOWJiQ2QFXVd18CUi...",
  "device_type": "android",
  "device_id": "device_123456",
  "device_name": "Samsung Galaxy S21"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "FCM token registered successfully",
  "data": { /* token details */ }
}
```

#### 44. Get Notification Preferences
```
GET /api/v1/notifications/preferences
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [ /* preferences */ ]
}
```

#### 45. Update Notification Preferences
```
POST /api/v1/notifications/preferences
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "preferences": [
    {"notification_type": "ride_updates", "is_enabled": true},
    {"notification_type": "messages", "is_enabled": false}
  ]
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Preferences updated successfully",
  "data": [ /* updated preferences */ ]
}
```

#### 46. Get All Notifications
```
GET /api/v1/notifications
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "fcm_tokens": [ /* tokens */ ],
    "preferences": [ /* preferences */ ]
  }
}
```

### Location Tracking Endpoints

#### 47. Update Location
```
POST /api/v1/locations/update
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "ride_id": 1,
  "latitude": 28.6139,
  "longitude": 77.2090,
  "accuracy": 5.0,
  "speed": 25.5,
  "heading": 180.0,
  "altitude": 100.0,
  "timestamp": "2024-01-01T00:00:00"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Location updated successfully",
  "data": { /* location details */ }
}
```

#### 48. Get Location History
```
GET /api/v1/locations/history/{ride_id}?limit=100&offset=0
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [ /* location history */ ],
  "pagination": { /* pagination info */ }
}
```

#### 49. Get Current Location
```
GET /api/v1/locations/current/{ride_id}
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": { /* current location */ }
}
```

### Payment Methods Endpoints

#### 50. Add Payment Method
```
POST /api/v1/payment-methods
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "payment_type": "card",
  "payment_details": {
    "card_number": "4111111111111111",
    "expiry": "12/25",
    "cvv": "123"
  }
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Payment method added successfully",
  "data": { /* payment method */ }
}
```

#### 51. List Payment Methods
```
GET /api/v1/payment-methods
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [ /* payment methods */ ]
}
```

#### 52. Update Payment Method
```
PUT /api/v1/payment-methods/{id}
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "payment_details": { /* updated details */ }
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Payment method updated successfully",
  "data": { /* updated method */ }
}
```

#### 53. Delete Payment Method
```
DELETE /api/v1/payment-methods/{id}
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Payment method deleted successfully"
}
```

#### 54. Set Default Payment Method
```
POST /api/v1/payment-methods/{id}/set-default
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Payment method set as default successfully"
}
```

## Ride Endpoints

#### 55. Request Ride
```
POST /api/v1/rides
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "from_location": "123 Main St",
  "to_location": "456 Park Ave",
  "from_latitude": 28.6139,
  "from_longitude": 77.2090,
  "to_latitude": 28.6200,
  "to_longitude": 77.2100,
  "ride_type": "passenger"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Ride requested successfully",
  "data": { /* ride details */ }
}
```

#### 56. Search Available Rides
```
GET /api/v1/rides/available?from_latitude=28.6139&from_longitude=77.2090&to_latitude=28.6200&to_longitude=77.2100
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [ /* available rides */ ]
}
```

#### 57. Get Ride Details
```
GET /api/v1/rides/{id}
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": { /* ride details */ }
}
```

#### 58. Accept Ride
```
POST /api/v1/rides/{id}/accept
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Ride accepted successfully",
  "data": { /* updated ride */ }
}
```

#### 59. Complete Ride
```
POST /api/v1/rides/{id}/complete
Authorization: Bearer {sanctum_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Ride completed successfully",
  "data": { /* updated ride */ }
}
```

#### 60. Cancel Ride
```
POST /api/v1/rides/{id}/cancel
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "cancellation_reason": "Emergency"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Ride cancelled successfully",
  "data": { /* updated ride */ }
}
```

## Summary

- **Total Endpoints**: 60+
- **Authentication**: Firebase + Sanctum token
- **Response Format**: Consistent JSON structure
- **Error Handling**: Comprehensive error codes and messages
- **File Uploads**: Multipart/form-data with validation
- **Pagination**: Supported on list endpoints
- **Rate Limiting**: Implemented on sensitive endpoints
