# WayloShare Postman Collection - Complete Guide

## Quick Start

### Step 1: Import Collection
1. Open Postman
2. Click "Import" → Select `POSTMAN_COLLECTION_COMPLETE.json`
3. Collection imported with 68+ endpoints

### Step 2: Set Environment Variables
1. Create new environment or use existing
2. Set `base_url`: `http://127.0.0.1:8000`
3. Set `firebase_token`: Your Firebase ID token
4. Save environment

### Step 3: Login
1. Go to "Authentication" folder
2. Click "POST /auth/login"
3. Click "Send"
4. `sanctum_token` auto-saved to environment
5. Ready to test other endpoints!

## Endpoint Categories & Usage

### 1. Health Check
**Purpose**: Verify backend is running

```
GET /api/v1/health
```

**Response**: Backend status, database connection, Redis status

**When to use**: 
- Before running tests
- Verify server is up
- Check service health

---

### 2. Authentication (3 endpoints)

#### Login with Firebase Token
```
POST /api/v1/auth/login
Authorization: Bearer {firebase_token}
```

**Auto-saves**: `sanctum_token`, `user_id`

**Use case**: Initial authentication

#### Get Current User
```
GET /api/v1/auth/me
Authorization: Bearer {sanctum_token}
```

**Use case**: Verify logged-in user

#### Logout
```
POST /api/v1/auth/logout
Authorization: Bearer {sanctum_token}
```

**Use case**: End session, revoke token

---

### 3. User Profile (8 endpoints)

#### Get Profile
```
GET /api/v1/user/profile
```

**Returns**: All user profile fields

#### Update Profile
```
POST /api/v1/user/profile
```

**Body**:
```json
{
  "display_name": "Johnny",
  "date_of_birth": "1990-01-15",
  "gender": "male",
  "bio": "Friendly driver",
  "user_preference": "both"
}
```

#### Upload Profile Photo
```
POST /api/v1/user/profile/photo
Content-Type: multipart/form-data
```

**Form Data**: `profile_photo` (JPG/PNG, max 10MB)

#### Complete Onboarding
```
POST /api/v1/user/complete-onboarding
```

**Body**:
```json
{
  "user_preference": "driver"
}
```

#### Preferences
```
GET /api/v1/user/preferences
POST /api/v1/user/preferences
```

**Body**:
```json
{
  "language": "english",
  "theme": "dark",
  "allow_messages": true
}
```

#### Privacy Settings
```
GET /api/v1/user/privacy
POST /api/v1/user/privacy
```

**Body**:
```json
{
  "profile_visibility": "public",
  "show_phone": true,
  "show_email": false,
  "allow_messages": true
}
```

---

### 4. Driver Verification (6 endpoints)

#### Create Verification
```
POST /api/v1/driver/verification
```

**Body**:
```json
{
  "dl_number": "DL123456789",
  "dl_expiry_date": "2025-12-31",
  "rc_number": "RC123456789"
}
```

#### Get Verification Status
```
GET /api/v1/driver/verification/status
```

#### Upload Documents
```
POST /api/v1/driver/verification/documents
Content-Type: multipart/form-data
```

**Form Data**:
- `dl_front_image` (JPG/PNG)
- `dl_back_image` (JPG/PNG)
- `rc_front_image` (JPG/PNG)
- `rc_back_image` (JPG/PNG)

#### Get Documents
```
GET /api/v1/driver/verification/documents
```

#### Submit Verification
```
POST /api/v1/driver/verification/submit
```

#### Get KYC Status
```
GET /api/v1/driver/kyc-status
```

---

### 5. Vehicles (6 endpoints)

#### Create Vehicle
```
POST /api/v1/vehicles
```

**Body**:
```json
{
  "vehicle_name": "My Sedan",
  "vehicle_type": "sedan",
  "license_plate": "KA01AB1234",
  "vehicle_color": "Black",
  "vehicle_year": 2023,
  "seating_capacity": 5
}
```

**Auto-saves**: `vehicle_id`

#### List Vehicles
```
GET /api/v1/vehicles
```

#### Get Vehicle Details
```
GET /api/v1/vehicles/{vehicle_id}
```

#### Update Vehicle
```
PUT /api/v1/vehicles/{vehicle_id}
```

**Body**: Any fields to update

#### Delete Vehicle
```
DELETE /api/v1/vehicles/{vehicle_id}
```

#### Set Default Vehicle
```
POST /api/v1/vehicles/{vehicle_id}/set-default
```

---

### 6. Rides (12 endpoints)

#### Request Ride
```
POST /api/v1/rides
```

**Body**:
```json
{
  "pickup_location": "123 Main St",
  "pickup_lat": 32.7266,
  "pickup_lng": 74.8570,
  "dropoff_location": "456 Oak Ave",
  "dropoff_lat": 32.7100,
  "dropoff_lng": 74.8500,
  "estimated_distance_km": 12,
  "estimated_duration_minutes": 20
}
```

**Auto-saves**: `ride_id`

#### Search Available Rides
```
GET /api/v1/rides/available?seats_needed=2&price_max=500
```

**Query Parameters**:
- `from_location`: Pickup location
- `to_location`: Dropoff location
- `seats_needed`: Number of seats
- `price_min`, `price_max`: Price range
- `ac_available`: Boolean
- `wifi_available`: Boolean
- `smoking_allowed`: Boolean
- `sort_by`: price|rating|departure_time
- `page`, `per_page`: Pagination

#### Get Ride Details
```
GET /api/v1/rides/{ride_id}
```

#### Accept Ride
```
POST /api/v1/rides/{ride_id}/accept
```

#### Driver Arrives
```
POST /api/v1/rides/{ride_id}/arrive
```

#### Start Ride
```
POST /api/v1/rides/{ride_id}/start
```

#### Complete Ride
```
POST /api/v1/rides/{ride_id}/complete
```

**Body**:
```json
{
  "actual_distance_km": 12.5,
  "actual_duration_minutes": 22
}
```

#### Cancel Ride
```
POST /api/v1/rides/{ride_id}/cancel
```

**Body**:
```json
{
  "reason": "Driver taking too long"
}
```

#### Offer Ride (Driver)
```
POST /api/v1/rides/offer
```

**Body**:
```json
{
  "pickup_location": "Downtown",
  "pickup_lat": 32.7266,
  "pickup_lng": 74.8570,
  "dropoff_location": "Airport",
  "dropoff_lat": 32.7100,
  "dropoff_lng": 74.8500,
  "available_seats": 3,
  "price_per_seat": 250,
  "ac_available": true,
  "smoking_allowed": false
}
```

#### Update Ride Status
```
POST /api/v1/rides/{ride_id}/update-status
```

**Body**:
```json
{
  "status": "started"
}
```

#### Get Ride History
```
GET /api/v1/rides/{ride_id}/history
```

---

### 7. Bookings (6 endpoints)

#### Create Booking
```
POST /api/v1/bookings
```

**Body**:
```json
{
  "ride_id": "{{ride_id}}",
  "seats_booked": 2,
  "passenger_name": "John Doe",
  "passenger_phone": "9876543210",
  "special_instructions": "Wait at main entrance",
  "luggage_info": "1 small bag"
}
```

**Auto-saves**: `booking_id`

#### List Bookings
```
GET /api/v1/bookings
```

**Query Parameters**:
- `status`: pending|confirmed|completed|cancelled
- `page`, `per_page`: Pagination

#### Get Booking Details
```
GET /api/v1/bookings/{booking_id}
```

#### Cancel Booking
```
POST /api/v1/bookings/{booking_id}/cancel
```

**Body**:
```json
{
  "cancellation_reason": "Change of plans"
}
```

#### Get Booking History
```
GET /api/v1/bookings/history
```

#### Get Booking Details (Alternative)
```
GET /api/v1/bookings/{booking_id}/details
```

---

### 8. Reviews (4 endpoints)

#### Create Review
```
POST /api/v1/reviews
```

**Body**:
```json
{
  "ride_id": "{{ride_id}}",
  "reviewee_id": "{{driver_id}}",
  "rating": 5,
  "comment": "Great ride!",
  "categories": {
    "cleanliness": 5,
    "driving": 4,
    "communication": 5
  }
}
```

**Auto-saves**: `review_id`

#### Get Review
```
GET /api/v1/reviews/{review_id}
```

#### Get User Reviews
```
GET /api/v1/reviews/user/{user_id}
```

#### Get Ride Reviews
```
GET /api/v1/reviews/ride/{ride_id}
```

---

### 9. Chat & Messaging (6 endpoints)

#### Create Chat
```
POST /api/v1/chats
```

**Body**:
```json
{
  "ride_id": "{{ride_id}}"
}
```

**Auto-saves**: `chat_id`

#### List Chats
```
GET /api/v1/chats
```

#### Send Message
```
POST /api/v1/chats/{chat_id}/messages
Content-Type: multipart/form-data
```

**Form Data**:
- `message`: Message text
- `message_type`: text|image|location
- `attachment`: Optional file (JPG/PNG/PDF, max 10MB)
- `metadata`: Optional JSON

#### Get Messages
```
GET /api/v1/chats/{chat_id}/messages
```

**Query Parameters**:
- `per_page`: Items per page (default: 20)

#### Mark as Read
```
POST /api/v1/chats/{chat_id}/mark-read
```

#### Delete Chat
```
DELETE /api/v1/chats/{chat_id}
```

---

### 10. Saved Routes (5 endpoints)

#### Save Route
```
POST /api/v1/saved-routes
```

**Body**:
```json
{
  "from_location": "Home",
  "to_location": "Office"
}
```

**Auto-saves**: `saved_route_id`

#### List Saved Routes
```
GET /api/v1/saved-routes
```

#### Pin Route
```
POST /api/v1/saved-routes/{saved_route_id}/pin
```

#### Update Route
```
PUT /api/v1/saved-routes/{saved_route_id}
```

**Body**:
```json
{
  "from_location": "New Home",
  "to_location": "New Office"
}
```

#### Delete Route
```
DELETE /api/v1/saved-routes/{saved_route_id}
```

---

### 11. Notifications (4 endpoints)

#### Register FCM Token
```
POST /api/v1/notifications/fcm-token
```

**Body**:
```json
{
  "fcm_token": "eJxlj91uwjAMhV9l5XoXEgMSV0hIXCBNV5MmTrOWJiQ2QFXVd18CUi...",
  "device_type": "android",
  "device_id": "device_123456",
  "device_name": "Samsung Galaxy S21"
}
```

#### Get Notification Preferences
```
GET /api/v1/notifications/preferences
```

#### Update Preferences
```
POST /api/v1/notifications/preferences
```

**Body**:
```json
{
  "preferences": [
    {"notification_type": "ride_updates", "is_enabled": true},
    {"notification_type": "messages", "is_enabled": true},
    {"notification_type": "reviews", "is_enabled": true},
    {"notification_type": "promotions", "is_enabled": false},
    {"notification_type": "system_alerts", "is_enabled": true},
    {"notification_type": "driver_requests", "is_enabled": true},
    {"notification_type": "booking_confirmations", "is_enabled": true}
  ]
}
```

#### Get All Notifications
```
GET /api/v1/notifications
```

---

### 12. Location Tracking (3 endpoints)

#### Update Location
```
POST /api/v1/locations/update
```

**Body**:
```json
{
  "ride_id": "{{ride_id}}",
  "latitude": 28.6139,
  "longitude": 77.2090,
  "accuracy": 5.0,
  "speed": 25.5,
  "heading": 180.0,
  "altitude": 100.0
}
```

#### Get Location History
```
GET /api/v1/locations/history/{ride_id}
```

**Query Parameters**:
- `limit`: Number of records (default: 100, max: 1000)
- `offset`: Skip records (default: 0)

#### Get Current Location
```
GET /api/v1/locations/current/{ride_id}
```

---

### 13. Payment Methods (5 endpoints)

#### Add Payment Method
```
POST /api/v1/payment-methods
```

**Body**:
```json
{
  "payment_type": "card",
  "payment_details": {
    "card_number": "****1234",
    "expiry": "12/25",
    "holder_name": "John Doe"
  },
  "is_default": false
}
```

**Auto-saves**: `payment_method_id`

**Payment Types**: card|wallet|upi

#### List Payment Methods
```
GET /api/v1/payment-methods
```

#### Update Payment Method
```
PUT /api/v1/payment-methods/{payment_method_id}
```

**Body**: Any fields to update

#### Delete Payment Method
```
DELETE /api/v1/payment-methods/{payment_method_id}
```

#### Set Default Payment Method
```
POST /api/v1/payment-methods/{payment_method_id}/set-default
```

---

## Testing Workflows

### Workflow 1: Complete Ride Request Flow

```
1. Health Check
   ↓
2. Login (get token)
   ↓
3. Get User Profile
   ↓
4. Request Ride
   ↓
5. Get Ride Details
   ↓
6. [As Driver] Accept Ride
   ↓
7. [As Driver] Arrive at Pickup
   ↓
8. [As Driver] Start Ride
   ↓
9. [As Driver] Update Location (multiple times)
   ↓
10. [As Driver] Complete Ride
    ↓
11. [As Passenger] Create Review
    ↓
12. [As Passenger] Get Review
```

### Workflow 2: Driver Offering Flow

```
1. Login
   ↓
2. Create Vehicle
   ↓
3. Offer Ride
   ↓
4. [As Passenger] Search Available Rides
   ↓
5. [As Passenger] Create Booking
   ↓
6. [As Driver] Accept Ride
   ↓
7. Complete Ride
   ↓
8. Create Review
```

### Workflow 3: Chat Flow

```
1. Login
   ↓
2. Create Chat (for a ride)
   ↓
3. Send Message
   ↓
4. Get Messages
   ↓
5. Mark as Read
   ↓
6. Send Another Message
   ↓
7. Delete Chat
```

### Workflow 4: Driver Verification Flow

```
1. Login
   ↓
2. Create Verification
   ↓
3. Get Verification Status
   ↓
4. Upload Documents
   ↓
5. Get Documents
   ↓
6. Submit Verification
   ↓
7. Get KYC Status
```

---

## Error Handling

### Common Errors

| Status | Error | Solution |
|--------|-------|----------|
| 401 | Unauthorized | Login first, check token |
| 404 | Not Found | Verify resource ID exists |
| 422 | Validation Error | Check request body format |
| 429 | Rate Limited | Wait before retrying |
| 500 | Server Error | Check server logs |

### Validation Errors

```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

**Common validations**:
- Phone: 10 digits (India format)
- Email: Valid email format
- Date of Birth: 18+ years old
- Latitude: -90 to 90
- Longitude: -180 to 180
- Rating: 1-5 integer
- File size: Max 10MB
- File types: JPG, PNG, PDF

---

## Tips & Tricks

### 1. Use Environment Variables
- Store sensitive data in environment
- Easy to switch between dev/staging/prod
- Secure token management

### 2. Chain Requests
- Use auto-saved IDs from responses
- Build complex workflows
- Test end-to-end flows

### 3. Use Pre-request Scripts
- Validate required variables
- Format request bodies
- Set dynamic headers

### 4. Use Test Scripts
- Validate response status
- Extract and save IDs
- Check response structure
- Verify data types

### 5. Use Collections
- Organize endpoints by category
- Easy navigation
- Reusable requests

### 6. Use Environments
- Different configs per environment
- Easy switching
- Consistent variable names

### 7. Use Postman Monitors
- Schedule automated tests
- Monitor API health
- Get alerts on failures

### 8. Export Results
- Generate reports
- Share with team
- Document API behavior

---

## Performance Testing

### Load Testing
1. Use Postman Collection Runner
2. Set iterations: 100+
3. Monitor response times
4. Check for errors

### Stress Testing
1. Increase concurrent requests
2. Monitor server resources
3. Check error rates
4. Identify bottlenecks

### Soak Testing
1. Run collection for extended period
2. Monitor memory usage
3. Check for memory leaks
4. Verify stability

---

## Security Testing

### Authentication
- Test with invalid tokens
- Test with expired tokens
- Test without tokens
- Test with wrong user tokens

### Authorization
- Test accessing other user's data
- Test with insufficient permissions
- Test role-based access

### Input Validation
- Test with invalid data types
- Test with oversized inputs
- Test with special characters
- Test with SQL injection attempts

### File Upload
- Test with invalid file types
- Test with oversized files
- Test with malicious files
- Test with corrupted files

---

## Debugging

### Enable Logging
1. Postman → Settings → General
2. Enable "Request logging"
3. View logs in Console

### Check Response
1. Click "Response" tab
2. View response body
3. Check response headers
4. Check response time

### Use Console
1. View → Show Postman Console
2. See all requests/responses
3. Check for errors
4. Debug scripts

### Use Network Tab
1. Open browser DevTools
2. Check network requests
3. Verify headers
4. Check response data

---

## Best Practices

1. **Always login first** before making requests
2. **Use environment variables** for configuration
3. **Test endpoints in order** (create before read)
4. **Check response status codes** for errors
5. **Validate response data** matches expectations
6. **Use pagination** for large result sets
7. **Handle errors gracefully** in your app
8. **Log all API calls** for debugging
9. **Test edge cases** and error scenarios
10. **Document API behavior** for team

---

## Support & Resources

- **API Documentation**: `documentation/API_ENDPOINTS.md`
- **Flutter Guide**: `documentation/FLUTTER_INTEGRATION_GUIDE.md`
- **Design Document**: `.kiro/specs/flutter-backend-alignment/design.md`
- **Postman Docs**: https://learning.postman.com/
- **REST API Best Practices**: https://restfulapi.net/

---

## Version History

- **v1.0** (2024-01-01): Initial collection with 68+ endpoints

---

**Last Updated**: 2024-01-01
**Collection Version**: 1.0
**API Version**: v1
