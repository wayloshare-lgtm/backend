# WayloShare API - Error Handling Documentation

## Overview

This document provides comprehensive guidance on error handling in the WayloShare API. All API responses follow a consistent error format to ensure predictable client-side error handling.

## Standard Error Response Format

All error responses follow this structure:

```json
{
  "success": false,
  "error": "ERROR_CODE",
  "message": "Human-readable error description",
  "errors": {
    "field_name": ["Validation error message"]
  },
  "timestamp": "2024-01-01T00:00:00Z"
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `success` | boolean | Always `false` for errors |
| `error` | string | Machine-readable error code |
| `message` | string | Human-readable error description |
| `errors` | object | Field-level validation errors (optional) |
| `timestamp` | string | ISO 8601 timestamp of error occurrence |

## HTTP Status Codes

### 2xx Success Codes

| Code | Name | Usage |
|------|------|-------|
| 200 | OK | Successful GET, PUT, PATCH requests |
| 201 | Created | Successful POST requests creating resources |
| 204 | No Content | Successful DELETE requests |

### 4xx Client Error Codes

| Code | Name | Error Code | Usage |
|------|------|-----------|-------|
| 400 | Bad Request | `BAD_REQUEST` | Malformed request syntax |
| 401 | Unauthorized | `UNAUTHORIZED` | Missing or invalid authentication |
| 403 | Forbidden | `FORBIDDEN` | Authenticated but lacks permission |
| 404 | Not Found | `NOT_FOUND` | Resource doesn't exist |
| 409 | Conflict | `CONFLICT` | Race condition or duplicate resource |
| 422 | Unprocessable Entity | `VALIDATION_ERROR` | Validation failures |
| 429 | Too Many Requests | `RATE_LIMIT_EXCEEDED` | Rate limit exceeded |

### 5xx Server Error Codes

| Code | Name | Error Code | Usage |
|------|------|-----------|-------|
| 500 | Internal Server Error | `INTERNAL_ERROR` | Unexpected server error |
| 503 | Service Unavailable | `SERVICE_UNAVAILABLE` | Server temporarily unavailable |

## Error Codes Reference

### Authentication Errors

#### UNAUTHORIZED (401)
**When:** Missing or invalid authentication token

**Example Response:**
```json
{
  "success": false,
  "error": "UNAUTHORIZED",
  "message": "Authentication token is missing or invalid",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:** 
- Verify token is included in Authorization header
- Refresh token if expired
- Re-authenticate if token is invalid

---

#### INVALID_TOKEN (401)
**When:** Token format is invalid or corrupted

**Example Response:**
```json
{
  "success": false,
  "error": "INVALID_TOKEN",
  "message": "The provided token is invalid or malformed",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Clear stored token
- Redirect to login screen
- Request new authentication

---

#### TOKEN_EXPIRED (401)
**When:** Authentication token has expired

**Example Response:**
```json
{
  "success": false,
  "error": "TOKEN_EXPIRED",
  "message": "Your session has expired. Please log in again",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Refresh token using refresh endpoint
- If refresh fails, redirect to login

---

#### FIREBASE_AUTH_FAILED (401)
**When:** Firebase token verification fails

**Example Response:**
```json
{
  "success": false,
  "error": "FIREBASE_AUTH_FAILED",
  "message": "Firebase token verification failed: Invalid signature",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Request new Firebase token
- Re-authenticate with Firebase
- Check device time synchronization

---

### Authorization Errors

#### FORBIDDEN (403)
**When:** User lacks required permissions

**Example Response:**
```json
{
  "success": false,
  "error": "FORBIDDEN",
  "message": "You do not have permission to access this resource",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Verify user role/permissions
- Contact administrator if access is needed
- Check if user needs to complete onboarding

---

#### INSUFFICIENT_ROLE (403)
**When:** User role doesn't match endpoint requirements

**Example Response:**
```json
{
  "success": false,
  "error": "INSUFFICIENT_ROLE",
  "message": "This action requires driver role. Your current role is: passenger",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Switch to appropriate user role
- Complete driver verification if needed
- Contact support if role is incorrect

---

### Validation Errors

#### VALIDATION_ERROR (422)
**When:** Request data fails validation

**Example Response:**
```json
{
  "success": false,
  "error": "VALIDATION_ERROR",
  "message": "The given data was invalid",
  "errors": {
    "email": ["The email field must be a valid email address"],
    "phone": ["The phone field must be 10 digits"],
    "date_of_birth": ["You must be at least 18 years old"]
  },
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Display field-level errors to user
- Correct invalid fields
- Resubmit request

---

#### INVALID_PHONE_FORMAT (422)
**When:** Phone number doesn't match expected format

**Example Response:**
```json
{
  "success": false,
  "error": "INVALID_PHONE_FORMAT",
  "message": "Phone number must be 10 digits in Indian format",
  "errors": {
    "phone": ["Invalid phone format. Expected: 10 digits"]
  },
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Validate phone format before submission
- Show format hint to user
- Accept only valid Indian phone numbers

---

#### INVALID_EMAIL_FORMAT (422)
**When:** Email doesn't match valid email pattern

**Example Response:**
```json
{
  "success": false,
  "error": "INVALID_EMAIL_FORMAT",
  "message": "The email address format is invalid",
  "errors": {
    "email": ["Invalid email format"]
  },
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Validate email format client-side
- Show format hint to user
- Verify email is correctly typed

---

#### INVALID_DATE_OF_BIRTH (422)
**When:** User is under 18 years old

**Example Response:**
```json
{
  "success": false,
  "error": "INVALID_DATE_OF_BIRTH",
  "message": "You must be at least 18 years old to use this service",
  "errors": {
    "date_of_birth": ["User must be 18 or older"]
  },
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Show age requirement message
- Prevent submission of invalid dates
- Validate age client-side

---

#### INVALID_COORDINATES (422)
**When:** Latitude or longitude values are out of range

**Example Response:**
```json
{
  "success": false,
  "error": "INVALID_COORDINATES",
  "message": "Invalid geographic coordinates provided",
  "errors": {
    "latitude": ["Latitude must be between -90 and 90"],
    "longitude": ["Longitude must be between -180 and 180"]
  },
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Verify GPS coordinates are valid
- Check device location services
- Retry location update

---

#### INVALID_FILE_UPLOAD (422)
**When:** Uploaded file fails validation

**Example Response:**
```json
{
  "success": false,
  "error": "INVALID_FILE_UPLOAD",
  "message": "File upload validation failed",
  "errors": {
    "profile_photo": [
      "File size must not exceed 10MB",
      "File type must be: jpg, jpeg, png"
    ]
  },
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Check file size (max 10MB)
- Verify file type (JPG, PNG, PDF)
- Compress image if needed
- Retry upload

---

### Resource Errors

#### NOT_FOUND (404)
**When:** Requested resource doesn't exist

**Example Response:**
```json
{
  "success": false,
  "error": "NOT_FOUND",
  "message": "The requested resource was not found",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Verify resource ID is correct
- Check if resource was deleted
- Refresh resource list

---

#### RIDE_NOT_FOUND (404)
**When:** Specified ride doesn't exist

**Example Response:**
```json
{
  "success": false,
  "error": "RIDE_NOT_FOUND",
  "message": "Ride with ID 123 not found",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Verify ride ID
- Refresh ride list
- Check if ride was cancelled

---

#### USER_NOT_FOUND (404)
**When:** Specified user doesn't exist

**Example Response:**
```json
{
  "success": false,
  "error": "USER_NOT_FOUND",
  "message": "User with ID 456 not found",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Verify user ID
- Check if user account was deleted
- Refresh user list

---

#### VEHICLE_NOT_FOUND (404)
**When:** Specified vehicle doesn't exist

**Example Response:**
```json
{
  "success": false,
  "error": "VEHICLE_NOT_FOUND",
  "message": "Vehicle with ID 789 not found",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Verify vehicle ID
- Refresh vehicle list
- Check if vehicle was deleted

---

### Conflict Errors

#### CONFLICT (409)
**When:** Request conflicts with current resource state

**Example Response:**
```json
{
  "success": false,
  "error": "CONFLICT",
  "message": "The request conflicts with the current state of the resource",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Refresh resource state
- Retry operation
- Contact support if issue persists

---

#### RIDE_ALREADY_TAKEN (409)
**When:** Attempting to accept an already-accepted ride

**Example Response:**
```json
{
  "success": false,
  "error": "RIDE_ALREADY_TAKEN",
  "message": "This ride has already been accepted by another driver",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Refresh available rides list
- Select different ride
- Show notification to user

---

#### INSUFFICIENT_SEATS (409)
**When:** Booking more seats than available

**Example Response:**
```json
{
  "success": false,
  "error": "INSUFFICIENT_SEATS",
  "message": "Only 2 seats available, but 5 were requested",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Show available seat count
- Allow user to reduce booking quantity
- Suggest alternative rides

---

#### DUPLICATE_RESOURCE (409)
**When:** Attempting to create duplicate resource

**Example Response:**
```json
{
  "success": false,
  "error": "DUPLICATE_RESOURCE",
  "message": "A vehicle with license plate ABC123 already exists",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Check existing resources
- Use different unique identifier
- Update existing resource instead

---

### Rate Limiting Errors

#### RATE_LIMIT_EXCEEDED (429)
**When:** Too many requests from same user/IP

**Example Response:**
```json
{
  "success": false,
  "error": "RATE_LIMIT_EXCEEDED",
  "message": "Too many requests. Please try again in 60 seconds",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Response Headers:**
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1704067260
```

**Client Action:**
- Wait before retrying (check X-RateLimit-Reset header)
- Implement exponential backoff
- Batch requests if possible
- Contact support if limits are too restrictive

---

### Server Errors

#### INTERNAL_ERROR (500)
**When:** Unexpected server error occurs

**Example Response:**
```json
{
  "success": false,
  "error": "INTERNAL_ERROR",
  "message": "An unexpected error occurred. Please try again later",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Retry request after delay
- Check server status page
- Contact support with timestamp
- Log error for debugging

---

#### SERVICE_UNAVAILABLE (503)
**When:** Server is temporarily unavailable

**Example Response:**
```json
{
  "success": false,
  "error": "SERVICE_UNAVAILABLE",
  "message": "The service is temporarily unavailable. Please try again later",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Implement retry logic with exponential backoff
- Show maintenance message to user
- Check server status page
- Retry after 30-60 seconds

---

#### DATABASE_ERROR (500)
**When:** Database operation fails

**Example Response:**
```json
{
  "success": false,
  "error": "DATABASE_ERROR",
  "message": "Database operation failed. Please try again",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

**Client Action:**
- Retry operation
- Check network connectivity
- Contact support if persists

---

## Error Handling Best Practices

### Client-Side Error Handling

#### 1. Always Check Response Status
```javascript
// Good
if (response.status === 200) {
  // Handle success
} else if (response.status === 401) {
  // Handle authentication error
} else if (response.status === 422) {
  // Handle validation error
}

// Better - Use error code
if (response.data.error === 'UNAUTHORIZED') {
  // Redirect to login
}
```

#### 2. Display User-Friendly Messages
```javascript
// Show message from API
showError(response.data.message);

// Or use error code to show localized message
const errorMessages = {
  'UNAUTHORIZED': 'Please log in again',
  'VALIDATION_ERROR': 'Please check your input',
  'RATE_LIMIT_EXCEEDED': 'Too many requests. Please wait'
};
showError(errorMessages[response.data.error]);
```

#### 3. Handle Validation Errors
```javascript
if (response.status === 422) {
  // Display field-level errors
  Object.entries(response.data.errors).forEach(([field, messages]) => {
    showFieldError(field, messages[0]);
  });
}
```

#### 4. Implement Retry Logic
```javascript
async function retryRequest(fn, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await fn();
    } catch (error) {
      if (error.response?.status === 429) {
        // Rate limited - wait and retry
        await sleep(Math.pow(2, i) * 1000);
      } else if (error.response?.status >= 500) {
        // Server error - wait and retry
        await sleep(Math.pow(2, i) * 1000);
      } else {
        // Client error - don't retry
        throw error;
      }
    }
  }
}
```

#### 5. Handle Authentication Errors
```javascript
if (response.status === 401) {
  // Clear stored token
  localStorage.removeItem('token');
  
  // Redirect to login
  window.location.href = '/login';
  
  // Or refresh token if available
  const newToken = await refreshToken();
  if (newToken) {
    // Retry original request with new token
    return retryRequest();
  }
}
```

### Server-Side Error Handling

#### 1. Validate Input Early
```php
// In controller
$validated = $request->validate([
    'email' => 'required|email',
    'phone' => 'required|phone_number',
    'date_of_birth' => 'required|date_of_birth'
]);
```

#### 2. Use Consistent Error Responses
```php
// In controller
try {
    $ride = Ride::findOrFail($id);
} catch (ModelNotFoundException $e) {
    return response()->json([
        'success' => false,
        'error' => 'RIDE_NOT_FOUND',
        'message' => 'Ride not found',
        'timestamp' => now()->toIso8601String()
    ], 404);
}
```

#### 3. Log Errors for Debugging
```php
// In exception handler
Log::error('API Error', [
    'error_code' => $error->getCode(),
    'message' => $error->getMessage(),
    'user_id' => auth()->id(),
    'endpoint' => request()->path(),
    'timestamp' => now()
]);
```

#### 4. Don't Expose Sensitive Information
```php
// Bad - exposes database details
'message' => 'SQLSTATE[HY000]: General error: 1030 Got error...'

// Good - generic message
'message' => 'Database operation failed. Please try again'
```

#### 5. Handle Race Conditions
```php
// Use database transactions
DB::transaction(function () {
    $ride = Ride::lockForUpdate()->find($id);
    
    if ($ride->status !== 'pending') {
        throw new InvalidRideTransitionException('Ride already accepted');
    }
    
    $ride->update(['status' => 'accepted']);
});
```

## Error Handling by Feature

### Authentication Errors
- UNAUTHORIZED - Missing/invalid token
- INVALID_TOKEN - Malformed token
- TOKEN_EXPIRED - Session expired
- FIREBASE_AUTH_FAILED - Firebase verification failed

### Validation Errors
- VALIDATION_ERROR - General validation failure
- INVALID_PHONE_FORMAT - Phone format invalid
- INVALID_EMAIL_FORMAT - Email format invalid
- INVALID_DATE_OF_BIRTH - Age requirement not met
- INVALID_COORDINATES - GPS coordinates invalid
- INVALID_FILE_UPLOAD - File validation failed

### Authorization Errors
- FORBIDDEN - Insufficient permissions
- INSUFFICIENT_ROLE - Wrong user role

### Resource Errors
- NOT_FOUND - Resource doesn't exist
- RIDE_NOT_FOUND - Ride doesn't exist
- USER_NOT_FOUND - User doesn't exist
- VEHICLE_NOT_FOUND - Vehicle doesn't exist

### Conflict Errors
- CONFLICT - State conflict
- RIDE_ALREADY_TAKEN - Ride accepted by another driver
- INSUFFICIENT_SEATS - Not enough seats available
- DUPLICATE_RESOURCE - Resource already exists

### Rate Limiting
- RATE_LIMIT_EXCEEDED - Too many requests

### Server Errors
- INTERNAL_ERROR - Unexpected error
- SERVICE_UNAVAILABLE - Server temporarily down
- DATABASE_ERROR - Database operation failed

## Testing Error Scenarios

### Unit Test Example
```php
public function test_invalid_phone_format_returns_validation_error()
{
    $response = $this->postJson('/api/v1/user/profile', [
        'phone' => '123'  // Invalid format
    ]);
    
    $response->assertStatus(422);
    $response->assertJsonPath('error', 'VALIDATION_ERROR');
    $response->assertJsonPath('errors.phone.0', 'Invalid phone format');
}
```

### Feature Test Example
```php
public function test_unauthorized_request_returns_401()
{
    $response = $this->getJson('/api/v1/user/profile');
    
    $response->assertStatus(401);
    $response->assertJsonPath('error', 'UNAUTHORIZED');
}
```

### Integration Test Example
```php
public function test_ride_already_taken_error()
{
    $ride = Ride::factory()->create(['status' => 'accepted']);
    
    $response = $this->actingAs($user)
        ->postJson("/api/v1/rides/{$ride->id}/accept");
    
    $response->assertStatus(409);
    $response->assertJsonPath('error', 'RIDE_ALREADY_TAKEN');
}
```

## Monitoring and Alerting

### Error Metrics to Track
- Error rate by endpoint
- Error rate by error code
- Response time by endpoint
- Rate limit violations
- Authentication failures
- Validation errors

### Alert Thresholds
- Error rate > 1% → Alert
- 5xx errors > 0.1% → Critical alert
- Rate limit violations > 100/hour → Alert
- Authentication failures > 50/hour → Alert

## Troubleshooting Guide

### Common Issues and Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| 401 Unauthorized | Missing token | Add Authorization header |
| 401 Unauthorized | Expired token | Refresh token |
| 422 Validation Error | Invalid input | Check field values |
| 404 Not Found | Wrong resource ID | Verify ID is correct |
| 409 Conflict | Race condition | Retry request |
| 429 Rate Limited | Too many requests | Wait and retry |
| 500 Internal Error | Server error | Retry after delay |
| 503 Unavailable | Maintenance | Check status page |

## References

- [HTTP Status Codes](https://httpwg.org/specs/rfc7231.html#status.codes)
- [JSON API Error Handling](https://jsonapi.org/examples/#error-objects)
- [REST API Best Practices](https://restfulapi.net/http-status-codes/)
