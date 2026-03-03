# WayloShare Flutter Integration Guide

Complete API reference for integrating the WayloShare backend with your Flutter app.

## 📋 Table of Contents

1. [Base URL & Configuration](#base-url--configuration)
2. [Authentication Flow](#authentication-flow)
3. [API Endpoints](#api-endpoints)
4. [Error Handling](#error-handling)
5. [Flutter Implementation Examples](#flutter-implementation-examples)

---

## Base URL & Configuration

### Development
```
Base URL: http://localhost:8000/api/v1
```

### Production
```
Base URL: https://api.wayloshare.com/api/v1
```

### Common Headers
```
Content-Type: application/json
Accept: application/json
```

---

## Authentication Flow

### Step 1: Get Firebase ID Token
Use Firebase Authentication in your Flutter app to get an ID token:

```dart
import 'package:firebase_auth/firebase_auth.dart';

final user = FirebaseAuth.instance.currentUser;
final idToken = await user?.getIdToken();
```

### Step 2: Exchange Firebase Token for Sanctum API Token
Send the Firebase ID token to the login endpoint to get a Sanctum API token.

### Step 3: Use Sanctum Token for All Requests
Include the Sanctum token in the Authorization header for all protected endpoints.

### Token Storage
Store the Sanctum token securely using Flutter Secure Storage:

```dart
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

const storage = FlutterSecureStorage();

// Save token
await storage.write(key: 'api_token', value: sanctumToken);

// Retrieve token
final token = await storage.read(key: 'api_token');

// Delete token on logout
await storage.delete(key: 'api_token');
```

---

## API Endpoints

### 1. Health Check (Public)

**Endpoint:** `GET /health`

**Headers:**
```
Content-Type: application/json
```

**Response (200):**
```json
{
  "status": "ok",
  "timestamp": "2024-01-01T00:00:00Z",
  "user_id": 1
}
```

**Use Case:** Check if API is running before making requests.

---

### 2. Login with Firebase Token

**Endpoint:** `POST /auth/login`

**Headers:**
```
Authorization: Bearer {firebase_id_token}
Content-Type: application/json
```

**Request Body:**
```json
{}
```

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
    "role": "rider",
    "is_active": true,
    "is_verified": false,
    "created_at": "2024-01-01T00:00:00Z"
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz..."
}
```

**Error (401):**
```json
{
  "success": false,
  "error": "Authentication failed",
  "message": "Token verification failed"
}
```

**Use Case:** Initial login after Firebase authentication.

---

### 3. Get Current User Profile

**Endpoint:** `GET /auth/me`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

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
    "role": "rider",
    "is_active": true,
    "is_verified": false,
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

**Use Case:** Fetch current user details after login.

---

### 4. Logout

**Endpoint:** `POST /auth/logout`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

**Use Case:** Revoke API token and logout user.

---

### 5. Delete Account

**Endpoint:** `DELETE /auth/delete-account`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Account deleted successfully"
}
```

**Use Case:** Permanently delete user account and all associated data.

---

## Ride Endpoints

### 6. Request a Ride

**Endpoint:** `POST /rides`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "pickup_location": "123 Main St, City",
  "pickup_lat": 32.7266,
  "pickup_lng": 74.857,
  "dropoff_location": "456 Oak Ave, City",
  "dropoff_lat": 32.71,
  "dropoff_lng": 74.85,
  "estimated_distance_km": 12.5,
  "estimated_duration_minutes": 20,
  "toll_amount": 0,
  "city": "Lahore"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Ride requested successfully",
  "ride": {
    "id": 1,
    "rider_id": 1,
    "driver_id": null,
    "pickup_location": "123 Main St, City",
    "pickup_lat": 32.7266,
    "pickup_lng": 74.857,
    "dropoff_location": "456 Oak Ave, City",
    "dropoff_lat": 32.71,
    "dropoff_lng": 74.85,
    "estimated_distance_km": 12.5,
    "estimated_duration_minutes": 20,
    "actual_distance_km": null,
    "actual_duration_minutes": null,
    "estimated_fare": 250.50,
    "actual_fare": null,
    "toll_amount": 0,
    "status": "pending",
    "cancellation_reason": null,
    "created_at": "2024-01-01T00:00:00Z",
    "accepted_at": null,
    "arrived_at": null,
    "started_at": null,
    "completed_at": null,
    "cancelled_at": null
  }
}
```

**Error (422):**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "pickup_lat": ["The pickup_lat field is required."]
  }
}
```

**Use Case:** Rider requests a new ride.

---

### 7. Get Ride Details

**Endpoint:** `GET /rides/{ride_id}`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Response (200):**
```json
{
  "success": true,
  "ride": {
    "id": 1,
    "rider_id": 1,
    "driver_id": 2,
    "pickup_location": "123 Main St, City",
    "pickup_lat": 32.7266,
    "pickup_lng": 74.857,
    "dropoff_location": "456 Oak Ave, City",
    "dropoff_lat": 32.71,
    "dropoff_lng": 74.85,
    "estimated_distance_km": 12.5,
    "estimated_duration_minutes": 20,
    "actual_distance_km": 12.3,
    "actual_duration_minutes": 19,
    "estimated_fare": 250.50,
    "actual_fare": 245.00,
    "toll_amount": 0,
    "status": "in_progress",
    "cancellation_reason": null,
    "created_at": "2024-01-01T00:00:00Z",
    "accepted_at": "2024-01-01T00:05:00Z",
    "arrived_at": "2024-01-01T00:10:00Z",
    "started_at": "2024-01-01T00:12:00Z",
    "completed_at": null,
    "cancelled_at": null
  }
}
```

**Use Case:** Get current ride status and details.

---

### 8. Accept Ride (Driver Only)

**Endpoint:** `POST /rides/{ride_id}/accept`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Ride accepted successfully",
  "ride": {
    "id": 1,
    "status": "accepted",
    "driver_id": 2,
    "accepted_at": "2024-01-01T00:05:00Z"
  }
}
```

**Error (409):**
```json
{
  "success": false,
  "error": "Ride already taken",
  "message": "Another driver has already accepted this ride"
}
```

**Use Case:** Driver accepts a pending ride.

---

### 9. Arrive at Pickup Location (Driver Only)

**Endpoint:** `POST /rides/{ride_id}/arrive`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Driver arrived at pickup location",
  "ride": {
    "id": 1,
    "status": "arrived",
    "arrived_at": "2024-01-01T00:10:00Z"
  }
}
```

**Use Case:** Driver notifies they've arrived at pickup location.

---

### 10. Start Ride (Driver Only)

**Endpoint:** `POST /rides/{ride_id}/start`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Ride started",
  "ride": {
    "id": 1,
    "status": "in_progress",
    "started_at": "2024-01-01T00:12:00Z"
  }
}
```

**Use Case:** Driver starts the ride (passenger is in vehicle).

---

### 11. Complete Ride (Driver Only)

**Endpoint:** `POST /rides/{ride_id}/complete`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "actual_distance_km": 12.3,
  "actual_duration_minutes": 19,
  "toll_amount": 0,
  "city": "Lahore"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Ride completed successfully",
  "ride": {
    "id": 1,
    "status": "completed",
    "actual_distance_km": 12.3,
    "actual_duration_minutes": 19,
    "actual_fare": 245.00,
    "completed_at": "2024-01-01T00:31:00Z"
  }
}
```

**Use Case:** Driver completes the ride and calculates final fare.

---

### 12. Cancel Ride

**Endpoint:** `POST /rides/{ride_id}/cancel`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "reason": "Driver is taking too long"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Ride cancelled successfully",
  "ride": {
    "id": 1,
    "status": "cancelled",
    "cancellation_reason": "Driver is taking too long",
    "cancelled_at": "2024-01-01T00:15:00Z"
  }
}
```

**Use Case:** Rider or driver cancels a ride.

---

## Driver Endpoints

### 13. Get Driver Profile

**Endpoint:** `GET /driver/profile`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Response (200):**
```json
{
  "success": true,
  "profile": {
    "id": 1,
    "user_id": 2,
    "license_number": "DL-12345",
    "vehicle_type": "sedan",
    "vehicle_number": "ABC-1234",
    "is_approved": true,
    "is_online": true,
    "current_lat": 32.7266,
    "current_lng": 74.857,
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z"
  }
}
```

**Error (404):**
```json
{
  "success": false,
  "error": "Driver profile not found",
  "message": "Please create a driver profile first"
}
```

**Use Case:** Get driver's profile information.

---

### 14. Create or Update Driver Profile

**Endpoint:** `POST /driver/profile`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "license_number": "DL-12345",
  "vehicle_type": "sedan",
  "vehicle_number": "ABC-1234",
  "current_lat": 32.7266,
  "current_lng": 74.857
}
```

**Response (201 - Create / 200 - Update):**
```json
{
  "success": true,
  "message": "Driver profile created successfully",
  "profile": {
    "id": 1,
    "user_id": 2,
    "license_number": "DL-12345",
    "vehicle_type": "sedan",
    "vehicle_number": "ABC-1234",
    "is_approved": false,
    "is_online": false,
    "current_lat": 32.7266,
    "current_lng": 74.857,
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z"
  }
}
```

**Use Case:** Create or update driver profile with vehicle information.

---

### 15. Update Driver Location

**Endpoint:** `POST /driver/location`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "current_lat": 32.7266,
  "current_lng": 74.857
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Location updated successfully",
  "location": {
    "current_lat": 32.7266,
    "current_lng": 74.857
  }
}
```

**Use Case:** Update driver's current location (call frequently for real-time tracking).

---

### 16. Toggle Driver Online Status

**Endpoint:** `POST /driver/toggle-online`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Driver is now online",
  "is_online": true
}
```

**Use Case:** Toggle driver's online/offline status.

---

## Admin Endpoints

### 17. Get Fare Configuration

**Endpoint:** `GET /admin/fare?city=Lahore`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Query Parameters:**
- `city` (optional): City name for fare configuration

**Response (200):**
```json
{
  "success": true,
  "fare_config": {
    "id": 1,
    "base_fare": 50.00,
    "per_km_rate": 15.00,
    "per_minute_rate": 2.00,
    "fuel_surcharge_per_km": 1.50,
    "platform_fee_percentage": 10.0,
    "toll_enabled": true,
    "night_multiplier": 1.5,
    "surge_multiplier": 1.0,
    "city": "Lahore",
    "is_active": true,
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z"
  }
}
```

**Use Case:** Get current fare settings for fare calculation.

---

### 18. Calculate Fare Estimate

**Endpoint:** `POST /admin/fare/calculate`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "distance_km": 12.5,
  "duration_minutes": 20,
  "toll_amount": 0,
  "is_night_time": false,
  "city": "Lahore"
}
```

**Response (200):**
```json
{
  "success": true,
  "fare_estimate": {
    "base_fare": 50.00,
    "distance_charge": 187.50,
    "time_charge": 40.00,
    "fuel_surcharge": 18.75,
    "subtotal": 296.25,
    "platform_fee": 29.63,
    "toll_amount": 0.00,
    "night_multiplier": 1.0,
    "surge_multiplier": 1.0,
    "total_fare": 325.88
  }
}
```

**Use Case:** Calculate estimated fare before requesting a ride.

---

## Error Handling

### Common HTTP Status Codes

| Status | Meaning | Example |
|--------|---------|---------|
| 200 | Success | Request completed successfully |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request data |
| 401 | Unauthorized | Missing or invalid token |
| 403 | Forbidden | User doesn't have permission |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Ride already taken, invalid state |
| 422 | Validation Error | Validation failed |
| 500 | Server Error | Internal server error |

### Error Response Format

```json
{
  "success": false,
  "error": "Error title",
  "message": "Detailed error message",
  "errors": {
    "field_name": ["Error message for field"]
  }
}
```

### Handling Errors in Flutter

```dart
try {
  final response = await http.post(
    Uri.parse('$baseUrl/rides'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
    body: jsonEncode(rideData),
  );

  if (response.statusCode == 201) {
    // Success
    final data = jsonDecode(response.body);
    print('Ride created: ${data['ride']['id']}');
  } else if (response.statusCode == 422) {
    // Validation error
    final data = jsonDecode(response.body);
    print('Validation errors: ${data['errors']}');
  } else if (response.statusCode == 401) {
    // Unauthorized - refresh token or logout
    print('Token expired, please login again');
  } else {
    // Other errors
    final data = jsonDecode(response.body);
    print('Error: ${data['error']}');
  }
} catch (e) {
  print('Network error: $e');
}
```

---

## Flutter Implementation Examples

### 1. HTTP Client Setup

```dart
import 'package:http/http.dart' as http;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ApiClient {
  static const String baseUrl = 'https://api.wayloshare.com/api/v1';
  static const storage = FlutterSecureStorage();

  static Future<Map<String, String>> getHeaders() async {
    final token = await storage.read(key: 'api_token');
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  static Future<http.Response> get(String endpoint) async {
    final headers = await getHeaders();
    return http.get(
      Uri.parse('$baseUrl$endpoint'),
      headers: headers,
    );
  }

  static Future<http.Response> post(String endpoint, Map<String, dynamic> body) async {
    final headers = await getHeaders();
    return http.post(
      Uri.parse('$baseUrl$endpoint'),
      headers: headers,
      body: jsonEncode(body),
    );
  }
}
```

### 2. Login Implementation

```dart
import 'package:firebase_auth/firebase_auth.dart';
import 'dart:convert';

Future<void> loginWithFirebase() async {
  try {
    // Get Firebase ID token
    final user = FirebaseAuth.instance.currentUser;
    final idToken = await user?.getIdToken();

    if (idToken == null) {
      throw Exception('Failed to get Firebase token');
    }

    // Exchange for Sanctum token
    final response = await http.post(
      Uri.parse('${ApiClient.baseUrl}/auth/login'),
      headers: {
        'Authorization': 'Bearer $idToken',
        'Content-Type': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      final sanctumToken = data['token'];
      final user = data['user'];

      // Save token
      await ApiClient.storage.write(key: 'api_token', value: sanctumToken);
      await ApiClient.storage.write(key: 'user_id', value: user['id'].toString());

      print('Login successful');
    } else {
      throw Exception('Login failed');
    }
  } catch (e) {
    print('Error: $e');
  }
}
```

### 3. Request Ride Implementation

```dart
Future<void> requestRide({
  required String pickupLocation,
  required double pickupLat,
  required double pickupLng,
  required String dropoffLocation,
  required double dropoffLat,
  required double dropoffLng,
  required double estimatedDistance,
  required int estimatedDuration,
}) async {
  try {
    final response = await ApiClient.post('/rides', {
      'pickup_location': pickupLocation,
      'pickup_lat': pickupLat,
      'pickup_lng': pickupLng,
      'dropoff_location': dropoffLocation,
      'dropoff_lat': dropoffLat,
      'dropoff_lng': dropoffLng,
      'estimated_distance_km': estimatedDistance,
      'estimated_duration_minutes': estimatedDuration,
      'toll_amount': 0,
      'city': 'Lahore',
    });

    if (response.statusCode == 201) {
      final data = jsonDecode(response.body);
      final rideId = data['ride']['id'];
      print('Ride requested: $rideId');
    } else {
      final data = jsonDecode(response.body);
      print('Error: ${data['error']}');
    }
  } catch (e) {
    print('Error: $e');
  }
}
```

### 4. Get Ride Status Implementation

```dart
Future<Map<String, dynamic>> getRideStatus(int rideId) async {
  try {
    final response = await ApiClient.get('/rides/$rideId');

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return data['ride'];
    } else {
      throw Exception('Failed to fetch ride');
    }
  } catch (e) {
    print('Error: $e');
    rethrow;
  }
}
```

### 5. Update Driver Location Implementation

```dart
Future<void> updateDriverLocation(double lat, double lng) async {
  try {
    final response = await ApiClient.post('/driver/location', {
      'current_lat': lat,
      'current_lng': lng,
    });

    if (response.statusCode == 200) {
      print('Location updated');
    } else {
      print('Failed to update location');
    }
  } catch (e) {
    print('Error: $e');
  }
}
```

### 6. Calculate Fare Implementation

```dart
Future<Map<String, dynamic>> calculateFare({
  required double distanceKm,
  required int durationMinutes,
  required String city,
}) async {
  try {
    final response = await ApiClient.post('/admin/fare/calculate', {
      'distance_km': distanceKm,
      'duration_minutes': durationMinutes,
      'toll_amount': 0,
      'is_night_time': false,
      'city': city,
    });

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return data['fare_estimate'];
    } else {
      throw Exception('Failed to calculate fare');
    }
  } catch (e) {
    print('Error: $e');
    rethrow;
  }
}
```

---

## Rate Limiting

The API implements rate limiting to prevent abuse:

- **Limit:** 60 requests per minute per user
- **Headers:** Check `X-RateLimit-Remaining` and `X-RateLimit-Reset` headers
- **Error:** 429 Too Many Requests when limit exceeded

---

## CORS Configuration

The API is configured to accept requests from:
- `localhost:3000` (development)
- `127.0.0.1:8000` (local testing)
- `api.wayloshare.com` (production)

---

## Best Practices

1. **Token Management**
   - Store tokens securely using Flutter Secure Storage
   - Refresh tokens before they expire
   - Clear tokens on logout

2. **Error Handling**
   - Always check response status codes
   - Handle validation errors gracefully
   - Implement retry logic for network errors

3. **Performance**
   - Cache user profile data
   - Batch API requests when possible
   - Use pagination for large datasets

4. **Security**
   - Never log sensitive data (tokens, passwords)
   - Use HTTPS in production
   - Validate input before sending to API

5. **User Experience**
   - Show loading indicators during API calls
   - Display user-friendly error messages
   - Implement offline support with local caching

---

## Support & Documentation

- **API Documentation:** See [API_ENDPOINTS.md](API_ENDPOINTS.md)
- **Backend Architecture:** See [BACKEND_ARCHITECTURE.md](BACKEND_ARCHITECTURE.md)
- **Deployment Guide:** See [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)

---

**Last Updated:** March 2026  
**API Version:** 1.0  
**Status:** Production Ready ✅
