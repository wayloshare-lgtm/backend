# WayloShare Backend API - Postman Collection

## Overview

This Postman collection provides comprehensive testing for the WayloShare ride-sharing backend API with 68+ endpoints across 13 categories, covering all Flutter app alignment requirements.

## Collection Details

- **Total Endpoints**: 68+
- **Total Categories**: 13
- **Authentication**: Firebase + Sanctum tokens
- **Base URL**: `http://127.0.0.1:8000` (configurable)
- **Format**: Postman Collection v2.1.0

## Categories

1. **Health Check** (1 endpoint)
   - Backend health status verification

2. **Authentication** (3 endpoints)
   - Login with Firebase token
   - Get current user profile
   - Logout

3. **User Profile** (8 endpoints)
   - Get/Update profile
   - Upload profile photo
   - Complete onboarding
   - Manage preferences and privacy settings

4. **Driver Verification** (6 endpoints)
   - Create verification
   - Upload documents (DL, RC)
   - Check verification status
   - Get KYC status

5. **Vehicles** (6 endpoints)
   - CRUD operations for vehicles
   - Set default vehicle
   - Manage multiple vehicles per driver

6. **Rides** (12 endpoints)
   - Request rides
   - Search available rides
   - Accept/Complete rides
   - Driver ride offerings
   - Ride status management

7. **Bookings** (6 endpoints)
   - Create bookings
   - List/Get booking details
   - Cancel bookings
   - Booking history

8. **Reviews** (4 endpoints)
   - Create reviews with ratings
   - Get reviews by user or ride
   - Category-based ratings

9. **Chat & Messaging** (6 endpoints)
   - Create chats
   - Send messages with attachments
   - Get message history
   - Mark messages as read

10. **Saved Routes** (5 endpoints)
    - Save frequently used routes
    - Pin favorite routes
    - Update/Delete routes

11. **Notifications** (4 endpoints)
    - Register FCM tokens
    - Manage notification preferences
    - Get all notifications

12. **Location Tracking** (3 endpoints)
    - Update driver location
    - Get location history
    - Get current location

13. **Payment Methods** (5 endpoints)
    - Add/Update payment methods
    - List payment methods
    - Set default payment method
    - Delete payment methods

## Setup Instructions

### 1. Import Collection

1. Open Postman
2. Click "Import" button
3. Select `POSTMAN_COLLECTION_COMPLETE.json`
4. Collection will be imported with all endpoints

### 2. Configure Environment Variables

The collection uses the following environment variables:

```
base_url              = http://127.0.0.1:8000
firebase_token        = [Your Firebase ID token]
sanctum_token         = [Auto-populated after login]
user_id               = [Auto-populated after login]
ride_id               = [Auto-populated after ride creation]
vehicle_id            = [Auto-populated after vehicle creation]
booking_id            = [Auto-populated after booking creation]
review_id             = [Auto-populated after review creation]
chat_id               = [Auto-populated after chat creation]
saved_route_id        = [Auto-populated after route creation]
payment_method_id     = [Auto-populated after payment method creation]
driver_id             = [Driver user ID for testing]
verification_id       = [Verification ID for testing]
```

### 3. Set Firebase Token

1. Get a valid Firebase ID token from your Firebase project
2. In Postman, go to "Environments"
3. Select or create an environment
4. Set `firebase_token` variable with your token
5. Save the environment

### 4. Login to Get Sanctum Token

1. Go to "Authentication" folder
2. Run "POST /auth/login" request
3. Token will be automatically saved to `sanctum_token` variable
4. All subsequent requests will use this token

## Authentication Flow

```
1. User provides Firebase ID token
   ↓
2. POST /api/v1/auth/login (with Firebase token)
   ↓
3. Backend verifies Firebase token
   ↓
4. Backend creates/updates user in database
   ↓
5. Backend generates Sanctum API token
   ↓
6. Client receives Sanctum token
   ↓
7. All future requests use Sanctum token in Authorization header
```

## Request Examples

### Login with Firebase Token

```bash
POST /api/v1/auth/login
Authorization: Bearer {firebase_token}
Content-Type: application/json

Response:
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

### Create a Ride Request

```bash
POST /api/v1/rides
Authorization: Bearer {sanctum_token}
Content-Type: application/json

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

Response:
{
  "success": true,
  "ride": {
    "id": 1,
    "rider_id": 1,
    "pickup_location": "123 Main St",
    "dropoff_location": "456 Oak Ave",
    "status": "requested",
    "estimated_fare": 250.00,
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

### Upload Profile Photo

```bash
POST /api/v1/user/profile/photo
Authorization: Bearer {sanctum_token}
Content-Type: multipart/form-data

Form Data:
- profile_photo: [image file]

Response:
{
  "success": true,
  "message": "Profile photo uploaded successfully",
  "profile_photo_url": "profile-photos/550e8400-e29b-41d4-a716-446655440000.jpg"
}
```

### Send Chat Message with Attachment

```bash
POST /api/v1/chats/{chat_id}/messages
Authorization: Bearer {sanctum_token}
Content-Type: multipart/form-data

Form Data:
- message: "Hello!"
- message_type: "text"
- attachment: [optional file]

Response:
{
  "success": true,
  "message": "Message sent successfully",
  "data": {
    "id": 1,
    "chat_id": 1,
    "sender_id": 1,
    "message": "Hello!",
    "message_type": "text",
    "is_read": false,
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

## Response Format

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

## HTTP Status Codes

- **200**: Success (GET, PUT, POST without creation)
- **201**: Created (POST with resource creation)
- **400**: Bad Request (validation error)
- **401**: Unauthorized (missing or invalid token)
- **403**: Forbidden (insufficient permissions)
- **404**: Not Found (resource doesn't exist)
- **409**: Conflict (race condition or duplicate)
- **422**: Unprocessable Entity (validation error)
- **429**: Too Many Requests (rate limited)
- **500**: Internal Server Error

## Testing Workflow

### 1. Basic Flow

```
1. Health Check
   ↓
2. Login (get Sanctum token)
   ↓
3. Get User Profile
   ↓
4. Update Profile
   ↓
5. Upload Profile Photo
```

### 2. Ride Request Flow

```
1. Login
   ↓
2. Request Ride
   ↓
3. Get Ride Details
   ↓
4. Accept Ride (as driver)
   ↓
5. Arrive at Pickup
   ↓
6. Start Ride
   ↓
7. Complete Ride
   ↓
8. Create Review
```

### 3. Driver Offering Flow

```
1. Login
   ↓
2. Create Vehicle
   ↓
3. Offer Ride
   ↓
4. Search Available Rides (as passenger)
   ↓
5. Create Booking
   ↓
6. Complete Ride
   ↓
7. Create Review
```

### 4. Chat Flow

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
```

## Pre-request Scripts

The collection includes pre-request scripts that:

1. **Auto-save tokens** after login
2. **Auto-populate IDs** after resource creation
3. **Validate required variables** before requests
4. **Format request bodies** correctly

## Test Scripts

The collection includes test scripts that:

1. **Validate response status codes**
2. **Check response structure**
3. **Extract and save IDs** for chaining requests
4. **Verify data types**
5. **Check required fields**

## File Upload Validation

The collection validates file uploads with:

- **Supported Formats**: JPG, PNG, PDF
- **Max Size**: 10MB
- **Mime Type Validation**: Automatic
- **Storage**: Private disk with UUID filenames

## Rate Limiting

The API implements rate limiting on sensitive endpoints:

- **Default**: 60 requests per minute
- **Sensitive Endpoints**: 10 requests per minute
- **Response Header**: `X-RateLimit-Remaining`

## Security Features

1. **Firebase Token Verification**: Only at login
2. **Sanctum Token**: Used for all subsequent requests
3. **CORS Configuration**: Properly configured
4. **Input Sanitization**: All inputs sanitized
5. **Payment Encryption**: Payment details encrypted
6. **Request Logging**: All requests logged
7. **HTTPS**: Required in production

## Troubleshooting

### Issue: 401 Unauthorized

**Solution**: 
1. Ensure Firebase token is valid
2. Run login request to get Sanctum token
3. Check that `sanctum_token` variable is set

### Issue: 404 Not Found

**Solution**:
1. Verify resource ID is correct
2. Check that resource exists in database
3. Ensure you have permission to access resource

### Issue: 422 Validation Error

**Solution**:
1. Check request body format
2. Verify all required fields are present
3. Validate field values against constraints
4. Check error message for specific field issues

### Issue: File Upload Failed

**Solution**:
1. Verify file format (JPG, PNG, PDF)
2. Check file size (max 10MB)
3. Ensure file is not corrupted
4. Try uploading a different file

## Best Practices

1. **Always login first** before making other requests
2. **Use environment variables** for sensitive data
3. **Test endpoints in order** (create before read/update)
4. **Check response status codes** for errors
5. **Validate response data** matches expectations
6. **Use pagination** for large result sets
7. **Handle errors gracefully** in your app
8. **Log all API calls** for debugging

## API Documentation

For detailed API documentation, see:
- `documentation/API_ENDPOINTS.md` - Complete endpoint reference
- `documentation/FLUTTER_INTEGRATION_GUIDE.md` - Flutter integration guide
- `.kiro/specs/flutter-backend-alignment/design.md` - API design document

## Support

For issues or questions:
1. Check the API documentation
2. Review error messages carefully
3. Verify request format and parameters
4. Check server logs for detailed errors
5. Contact the development team

## Version History

- **v1.0** (2024-01-01): Initial collection with 68+ endpoints
  - Authentication endpoints
  - User profile management
  - Driver verification
  - Vehicle management
  - Ride management
  - Bookings system
  - Reviews and ratings
  - Chat and messaging
  - Saved routes
  - Notifications and FCM
  - Location tracking
  - Payment methods

## License

This Postman collection is part of the WayloShare project and follows the same license terms.
