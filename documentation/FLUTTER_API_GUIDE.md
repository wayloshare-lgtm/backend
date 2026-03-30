# WayloShare Flutter API Guide

## Overview

This comprehensive guide provides Flutter developers with all necessary information to integrate with the WayloShare backend API. The API is built with Laravel 11 and provides 40+ endpoints across 12 major feature areas.

## Quick Start

### Base URL
```
Production: https://api.wayloshare.com/api/v1
Development: http://localhost:8000/api/v1
```

### Authentication
All endpoints (except login) require a Sanctum API token in the Authorization header:
```
Authorization: Bearer {sanctum_api_token}
```

### Response Format
All responses follow a consistent JSON structure:

**Success Response:**
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { /* response data */ },
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "ERROR_CODE",
  "message": "Human readable error message",
  "errors": { /* validation errors */ },
  "timestamp": "2024-01-01T00:00:00Z"
}
```

## HTTP Status Codes

| Code | Meaning | When Used |
|------|---------|-----------|
| 200 | OK | Successful GET, PUT, POST |
| 201 | Created | Successful resource creation |
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Missing or invalid token |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource doesn't exist |
| 409 | Conflict | Race condition or duplicate |
| 422 | Unprocessable Entity | Validation error |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Server Error | Internal server error |

## Authentication Flow

### Step 1: Get Firebase ID Token
Use Firebase SDK to authenticate user:
```dart
final userCredential = await FirebaseAuth.instance.signInWithPhoneNumber(
  phoneNumber: '+91XXXXXXXXXX',
  verificationCompleted: (PhoneAuthCredential credential) async {
    await FirebaseAuth.instance.signInWithCredential(credential);
  },
);
```

### Step 2: Exchange Firebase Token for Sanctum Token
```dart
final firebaseToken = await FirebaseAuth.instance.currentUser?.getIdToken();
final response = await http.post(
  Uri.parse('$baseUrl/auth/login'),
  headers: {
    'Authorization': 'Bearer $firebaseToken',
    'Content-Type': 'application/json',
  },
);

final data = jsonDecode(response.body);
final sanctumToken = data['token'];
```

### Step 3: Use Sanctum Token for All Requests
```dart
final response = await http.get(
  Uri.parse('$baseUrl/auth/me'),
  headers: {
    'Authorization': 'Bearer $sanctumToken',
    'Content-Type': 'application/json',
  },
);
```

## Feature Areas

### 1. Authentication (4 endpoints)
- Login with Firebase token
- Get current user profile
- Logout
- Delete account

### 2. User Profile (8 endpoints)
- Get/update profile
- Upload profile photo
- Complete onboarding
- Manage preferences and privacy settings

### 3. Driver Verification (6 endpoints)
- Submit verification documents
- Check verification status
- Upload DL and RC documents
- Get KYC status

### 4. Vehicles (6 endpoints)
- Create/read/update/delete vehicles
- Upload vehicle photos
- Set default vehicle

### 5. Rides (12 endpoints)
- Request rides
- Search available rides
- Accept/complete rides
- Offer rides as driver
- Cancel rides

### 6. Bookings (6 endpoints)
- Create bookings
- List bookings
- Cancel bookings
- View booking history

### 7. Reviews (4 endpoints)
- Create reviews
- Get reviews for user
- Get reviews for ride
- View review details

### 8. Chat & Messaging (6 endpoints)
- Create chats
- Send messages with attachments
- Get message history
- Mark messages as read

### 9. Saved Routes (5 endpoints)
- Save routes
- List saved routes
- Pin/unpin routes
- Update and delete routes

### 10. Notifications (4 endpoints)
- Register FCM tokens
- Get notification preferences
- Update notification preferences
- Get all notifications

### 11. Location Tracking (3 endpoints)
- Update location
- Get location history
- Get current location

### 12. Payment Methods (5 endpoints)
- Add payment methods
- List payment methods
- Update payment methods
- Delete payment methods
- Set default payment method

## Common Patterns

### Pagination
Endpoints that return lists support pagination:
```dart
final response = await http.get(
  Uri.parse('$baseUrl/bookings?page=1&per_page=15'),
  headers: {'Authorization': 'Bearer $token'},
);
```

Response includes pagination metadata:
```json
{
  "success": true,
  "data": [ /* items */ ],
  "pagination": {
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7
  }
}
```

### File Uploads
Use multipart/form-data for file uploads:
```dart
var request = http.MultipartRequest(
  'POST',
  Uri.parse('$baseUrl/vehicles/1/photo'),
);
request.headers['Authorization'] = 'Bearer $token';
request.files.add(await http.MultipartFile.fromPath(
  'vehicle_photo',
  filePath,
));
var response = await request.send();
```

### Error Handling
Always check the response status and error field:
```dart
if (response.statusCode == 422) {
  final data = jsonDecode(response.body);
  final errors = data['errors']; // Validation errors
  // Handle validation errors
} else if (response.statusCode == 401) {
  // Token expired, refresh or redirect to login
} else if (response.statusCode >= 500) {
  // Server error, retry with exponential backoff
}
```

### Rate Limiting
The API implements rate limiting on sensitive endpoints. If you receive a 429 response:
```dart
// Retry after the specified delay
final retryAfter = int.parse(response.headers['retry-after'] ?? '60');
await Future.delayed(Duration(seconds: retryAfter));
```

## Data Validation Rules

### User Profile
- **display_name**: Max 255 characters
- **date_of_birth**: Must be 18+ years old
- **bio**: Max 500 characters
- **phone**: 10 digits (India format: 9876543210)
- **email**: Valid email format

### Vehicles
- **vehicle_name**: Max 255 characters
- **license_plate**: Unique, max 255 characters
- **seating_capacity**: Auto-determined by vehicle type
  - Sedan: 5 seats
  - SUV: 7 seats
  - Hatchback: 5 seats
  - MUV: 8 seats
  - Compact SUV: 5 seats

### Rides
- **price_per_seat**: > 0, max 10000
- **available_seats**: 1-8
- **latitude**: -90 to 90
- **longitude**: -180 to 180

### Bookings
- **seats_booked**: 1-8
- **passenger_phone**: 10 digits (India format)

### Reviews
- **rating**: 1-5 integer
- **comment**: Max 500 characters
- **categories**: JSON object with ratings 1-5

### File Uploads
- **Max size**: 10MB
- **Allowed types**: JPG, PNG, PDF
- **Validation**: Performed on both client and server

## Security Best Practices

### Token Management
1. Store Sanctum token securely (use platform-specific secure storage)
2. Refresh token before expiry
3. Clear token on logout
4. Never log or expose tokens

### Data Encryption
- Payment data is encrypted server-side
- Use HTTPS for all requests
- Validate SSL certificates

### Input Validation
- Validate all user inputs on client side
- Sanitize text inputs
- Validate file types and sizes
- Validate coordinates and dates

### Error Messages
- Don't expose sensitive information in error messages
- Log errors securely for debugging
- Show user-friendly error messages

## Common Use Cases

### User Onboarding Flow
1. Firebase authentication
2. Login to get Sanctum token
3. Update user profile
4. Upload profile photo
5. Complete onboarding
6. Set user preferences

### Driver Setup Flow
1. Create vehicle
2. Upload vehicle photo
3. Submit driver verification documents
4. Wait for verification approval
5. Set vehicle as default

### Ride Request Flow
1. Search available rides
2. Create booking
3. Wait for driver acceptance
4. Track driver location
5. Complete ride
6. Leave review

### Driver Offering Flow
1. Create ride offer
2. Wait for passenger bookings
3. Accept bookings
4. Update ride status
5. Complete ride
6. Receive reviews

## Troubleshooting

### 401 Unauthorized
- Token has expired: Get new token via login
- Token is invalid: Clear stored token and re-authenticate
- Token format is wrong: Ensure "Bearer " prefix

### 422 Validation Error
- Check error response for specific field errors
- Validate data before sending
- Ensure all required fields are present
- Check data types and formats

### 429 Rate Limited
- Implement exponential backoff retry logic
- Reduce request frequency
- Cache responses when possible
- Use pagination for large datasets

### 500 Server Error
- Retry with exponential backoff
- Check server status
- Contact support if persists
- Log error details for debugging

## API Endpoints Reference

See the complete endpoint documentation in `API_ENDPOINTS.md` for detailed request/response examples for all 40+ endpoints.

## Support

For issues or questions:
- Check this guide and API_ENDPOINTS.md
- Review integration tests in `tests/Integration/`
- Contact the backend team
- Check server logs for detailed error information

## Version History

- **v1.0** (Current): Initial API release with 40+ endpoints
- All endpoints are stable and production-ready
