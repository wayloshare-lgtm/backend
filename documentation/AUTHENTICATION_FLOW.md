# WayloShare Authentication Flow Documentation

## Table of Contents
1. [Authentication Architecture](#authentication-architecture)
2. [Firebase Authentication Flow](#firebase-authentication-flow)
3. [Sanctum Token Flow](#sanctum-token-flow)
4. [Login Process](#login-process)
5. [Logout Process](#logout-process)
6. [Token Refresh](#token-refresh)
7. [Role-Based Access Control](#role-based-access-control)
8. [Error Handling](#error-handling)
9. [Security Best Practices](#security-best-practices)
10. [Flutter Integration Examples](#flutter-integration-examples)

## Authentication Architecture

WayloShare uses a **dual-layer authentication system** combining Firebase Authentication with Laravel Sanctum:

```
┌─────────────────────────────────────────────────────────────┐
│                    Flutter Mobile App                       │
│              (Firebase Authentication SDK)                  │
└────────────────────────┬────────────────────────────────────┘
                         │ Firebase ID Token
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                  Firebase Auth Service                      │
│              (Google Cloud Authentication)                  │
└────────────────────────┬────────────────────────────────────┘
                         │ Verified Token
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              Laravel Backend (API Server)                   │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  AuthController                                     │  │
│  │  - Verify Firebase Token                           │  │
│  │  - Generate Sanctum API Token                       │  │
│  │  - Manage User Sessions                            │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Middleware Layer                                   │  │
│  │  - VerifyFirebaseToken (Initial Auth)              │  │
│  │  - Sanctum Guard (API Token Verification)          │  │
│  │  - Authorization (Role-based Access)               │  │
│  └──────────────────────────────────────────────────────┘  │
└────────────────────────┬────────────────────────────────────┘
                         │ Sanctum API Token
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    MySQL Database                           │
│  - users table (with authentication fields)                │
│  - personal_access_tokens table (Sanctum tokens)           │
└─────────────────────────────────────────────────────────────┘
```

### Key Components

| Component | Purpose | Technology |
|-----------|---------|-----------|
| Firebase Auth | User identity verification | Google Firebase |
| Sanctum | API token management | Laravel Sanctum |
| AuthController | Authentication endpoints | Laravel Controller |
| VerifyFirebaseToken | Middleware for token validation | Custom Middleware |
| User Model | User data persistence | Eloquent ORM |


## Firebase Authentication Flow

Firebase provides the initial user authentication layer. The flow is as follows:

### Step 1: User Registration/Sign-In (Flutter App)
```
User opens Flutter app
    ↓
Firebase UI presents sign-in options:
  - Phone number (OTP)
  - Email/Password
  - Google Sign-In
  - Apple Sign-In
    ↓
User completes authentication
    ↓
Firebase generates ID Token (JWT)
    ↓
Token stored securely in Flutter app
```

### Step 2: Firebase Token Structure
Firebase ID tokens are JWT tokens containing:
```json
{
  "iss": "https://securetoken.google.com/waylo-share-project",
  "aud": "waylo-share-project",
  "auth_time": 1234567890,
  "user_id": "firebase_uid_12345",
  "sub": "firebase_uid_12345",
  "iat": 1234567890,
  "exp": 1234571490,
  "email": "user@example.com",
  "email_verified": true,
  "phone_number": "+919876543210",
  "name": "John Doe",
  "picture": "https://...",
  "firebase": {
    "identities": {
      "phone_number": ["+919876543210"]
    },
    "sign_in_provider": "phone"
  }
}
```

### Step 3: Token Verification (Backend)
```
Firebase ID Token sent to backend
    ↓
Backend receives token in Authorization header
    ↓
VerifyFirebaseToken middleware intercepts request
    ↓
Middleware verifies token signature using Firebase public keys
    ↓
Token claims extracted (uid, email, phone, name)
    ↓
User found or created in database
    ↓
Request proceeds with authenticated user
```

### Firebase Configuration
Location: `storage/firebase/firebase_credentials.json`

Required fields:
```json
{
  "type": "service_account",
  "project_id": "waylo-share-project",
  "private_key_id": "...",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
  "client_email": "firebase-adminsdk@waylo-share-project.iam.gserviceaccount.com",
  "client_id": "...",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "..."
}
```


## Sanctum Token Flow

After Firebase verification, the backend generates a Sanctum API token for subsequent requests.

### Why Sanctum?
- **Stateless**: No session storage needed
- **Secure**: Tokens are cryptographically signed
- **Flexible**: Can be revoked individually
- **Scalable**: Works across multiple servers
- **Mobile-friendly**: Perfect for API-based apps

### Token Generation Process
```
Firebase token verified
    ↓
User found/created in database
    ↓
Sanctum generates personal access token
    ↓
Token stored in personal_access_tokens table
    ↓
Token returned to Flutter app
    ↓
Flutter app stores token securely (Keychain/Keystore)
```

### Token Structure
Sanctum tokens are stored in the database with:
```
- id: Unique identifier
- tokenable_id: User ID
- tokenable_type: "App\Models\User"
- name: Token name (e.g., "flutter-app")
- token: Hashed token value
- abilities: Token permissions (e.g., ["*"])
- last_used_at: Last usage timestamp
- created_at: Creation timestamp
- updated_at: Update timestamp
```

### Token Lifecycle
```
Token Generated
    ↓
Token sent to Flutter app
    ↓
Flutter app includes in Authorization header: "Bearer {token}"
    ↓
Backend validates token on each request
    ↓
Token updated with last_used_at timestamp
    ↓
Token remains valid until:
  - Explicitly revoked (logout)
  - User deleted
  - Token expires (if configured)
```

### Token Validation Middleware
Location: `app/Http/Middleware/Authenticate.php` (via Sanctum guard)

Process:
1. Extract token from Authorization header
2. Look up token in personal_access_tokens table
3. Verify token hasn't been revoked
4. Load associated user
5. Attach user to request


## Login Process

### Complete Login Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    FLUTTER APP                              │
├─────────────────────────────────────────────────────────────┤
│ 1. User taps "Login"                                        │
│ 2. Firebase UI presented                                    │
│ 3. User enters credentials                                  │
│ 4. Firebase verifies credentials                            │
│ 5. Firebase generates ID Token                              │
│ 6. App extracts token from Firebase                         │
└────────────────────────┬────────────────────────────────────┘
                         │ POST /api/v1/auth/login
                         │ Authorization: Bearer {firebase_token}
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    BACKEND API                              │
├─────────────────────────────────────────────────────────────┤
│ 1. AuthController::login() receives request                 │
│ 2. Extract Firebase token from header                       │
│ 3. Call FirebaseService::verifyToken()                      │
│ 4. Verify token signature with Firebase keys                │
│ 5. Extract claims (uid, email, phone, name)                 │
│ 6. Find or create user in database                          │
│ 7. Update user with latest Firebase data                    │
│ 8. Generate Sanctum API token                               │
│ 9. Return user data + API token                             │
└────────────────────────┬────────────────────────────────────┘
                         │ 200 OK
                         │ {
                         │   "success": true,
                         │   "user": {...},
                         │   "token": "api_token_xyz"
                         │ }
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    FLUTTER APP                              │
├─────────────────────────────────────────────────────────────┤
│ 1. Receive API token                                        │
│ 2. Store token securely (Keychain/Keystore)                 │
│ 3. Store user data locally                                  │
│ 4. Navigate to home screen                                  │
│ 5. Use API token for all subsequent requests                │
└─────────────────────────────────────────────────────────────┘
```

### API Endpoint: POST /api/v1/auth/login

**Request:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Authorization: Bearer {firebase_id_token}" \
  -H "Content-Type: application/json"
```

**Success Response (200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "firebase_uid": "firebase_uid_12345",
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+919876543210",
    "role": "passenger",
    "is_active": true,
    "is_verified": true,
    "created_at": "2024-01-01T00:00:00Z"
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz123456789"
}
```

**Error Response (401):**
```json
{
  "success": false,
  "error": "Authentication failed",
  "message": "Invalid token"
}
```

### User Creation on First Login
When a user logs in for the first time:
1. Backend checks if user exists by firebase_uid
2. If not found, creates new user with:
   - firebase_uid (from Firebase)
   - email (from Firebase)
   - phone (from Firebase)
   - name (from Firebase)
   - role: "passenger" (default)
   - is_active: true
   - is_verified: false (until email/phone verified)

### Subsequent Logins
On subsequent logins:
1. User is found by firebase_uid
2. User data is updated with latest Firebase info
3. New Sanctum token is generated
4. Previous tokens remain valid (user can have multiple active sessions)


## Logout Process

### Complete Logout Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    FLUTTER APP                              │
├─────────────────────────────────────────────────────────────┤
│ 1. User taps "Logout"                                       │
│ 2. App prepares logout request                              │
└────────────────────────┬────────────────────────────────────┘
                         │ POST /api/v1/auth/logout
                         │ Authorization: Bearer {api_token}
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    BACKEND API                              │
├─────────────────────────────────────────────────────────────┤
│ 1. AuthController::logout() receives request                │
│ 2. Sanctum middleware validates API token                   │
│ 3. Load authenticated user                                  │
│ 4. Call TokenService::revokeAllTokens()                     │
│ 5. Delete all personal_access_tokens for user               │
│ 6. Return success response                                  │
└────────────────────────┬────────────────────────────────────┘
                         │ 200 OK
                         │ {
                         │   "success": true,
                         │   "message": "Logged out successfully"
                         │ }
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    FLUTTER APP                              │
├─────────────────────────────────────────────────────────────┤
│ 1. Receive success response                                 │
│ 2. Clear stored API token                                   │
│ 3. Clear stored user data                                   │
│ 4. Clear Firebase session                                   │
│ 5. Navigate to login screen                                 │
└─────────────────────────────────────────────────────────────┘
```

### API Endpoint: POST /api/v1/auth/logout

**Request:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer {api_token}" \
  -H "Content-Type: application/json"
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

**Error Response (401):**
```json
{
  "success": false,
  "error": "User not authenticated"
}
```

### What Happens on Logout
1. **Token Revocation**: All Sanctum tokens for the user are deleted from database
2. **Session Termination**: User cannot use any previous tokens
3. **Firebase Session**: Firebase session remains (user can re-login without Firebase re-auth)
4. **Data Cleanup**: Local app data should be cleared

### Multi-Device Logout
Since each device gets its own Sanctum token:
- Logout on one device revokes ALL tokens for that user
- User must re-login on all devices
- This is a security feature to prevent unauthorized access

### Account Deletion

**API Endpoint: DELETE /api/v1/auth/delete-account**

```bash
curl -X DELETE http://localhost:8000/api/v1/auth/delete-account \
  -H "Authorization: Bearer {api_token}" \
  -H "Content-Type: application/json"
```

**Process:**
1. Verify user is authenticated
2. Revoke all API tokens
3. Delete user record from database
4. Cascade delete related data (rides, bookings, etc.)
5. Return success response

**Response (200):**
```json
{
  "success": true,
  "message": "Account deleted successfully"
}
```


## Token Refresh

### Token Expiration Strategy

WayloShare uses a **long-lived token** approach:

| Token Type | Lifetime | Refresh Strategy |
|-----------|----------|------------------|
| Firebase ID Token | 1 hour | Automatically refreshed by Firebase SDK |
| Sanctum API Token | Indefinite* | Revoked on logout or account deletion |

*Tokens remain valid until explicitly revoked. This is suitable for mobile apps where users expect to stay logged in.

### Firebase Token Refresh (Automatic)

Firebase SDK automatically handles token refresh:

```
Firebase ID Token expires (1 hour)
    ↓
Firebase SDK detects expiration
    ↓
Firebase SDK uses refresh token to get new ID token
    ↓
New token automatically available in app
    ↓
No user action required
```

### Sanctum Token Refresh (Manual)

If implementing token expiration for Sanctum:

**Option 1: Re-login**
```
Sanctum token expires
    ↓
API returns 401 Unauthorized
    ↓
Flutter app detects 401
    ↓
App triggers re-login flow
    ↓
User logs in again
    ↓
New Sanctum token generated
```

**Option 2: Refresh Endpoint (Future)**
```
Sanctum token expires
    ↓
API returns 401 Unauthorized
    ↓
Flutter app calls refresh endpoint
    ↓
Backend validates Firebase token
    ↓
Backend generates new Sanctum token
    ↓
App uses new token
```

### Token Validation on Each Request

```
Flutter app makes API request
    ↓
Includes Sanctum token in Authorization header
    ↓
Backend receives request
    ↓
Sanctum middleware validates token:
  - Token exists in database
  - Token not revoked
  - Token not expired (if expiration enabled)
    ↓
If valid: Request proceeds
If invalid: Return 401 Unauthorized
```

### Handling Token Expiration in Flutter

**Recommended Implementation:**

```dart
// Interceptor for API requests
class AuthInterceptor extends Interceptor {
  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    final token = getStoredToken();
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }

  @override
  void onError(DioError err, ErrorInterceptorHandler handler) {
    if (err.response?.statusCode == 401) {
      // Token expired or invalid
      handleTokenExpiration();
    }
    handler.next(err);
  }
}

void handleTokenExpiration() {
  // Clear stored token
  clearStoredToken();
  
  // Clear user data
  clearUserData();
  
  // Navigate to login
  navigateToLogin();
}
```


## Role-Based Access Control (RBAC)

### User Roles

WayloShare supports multiple user roles:

| Role | Description | Permissions |
|------|-------------|-------------|
| passenger | Regular user requesting rides | View rides, book rides, rate drivers, chat |
| driver | User offering rides | Create rides, accept bookings, rate passengers, chat |
| admin | System administrator | All permissions + user management |

### Role Assignment

**On First Login:**
- Default role: `passenger`
- User can change to `driver` after completing driver verification

**Role Change Flow:**
```
User completes driver verification
    ↓
DriverVerification status = "approved"
    ↓
User can update user_preference to "driver" or "both"
    ↓
User gains driver permissions
```

### User Preference Field

The `user_preference` field allows users to have multiple roles:

```json
{
  "user_preference": "both"  // Can act as both driver and passenger
}
```

Options:
- `passenger`: Only passenger features
- `driver`: Only driver features
- `both`: Access to both passenger and driver features

### Authorization Middleware

**Location:** `app/Http/Middleware/Authorize.php` (custom)

**Usage in Routes:**
```php
Route::middleware(['auth:sanctum', 'authorize:driver'])->group(function () {
    Route::post('/rides/offer', [RideController::class, 'offer']);
});
```

### Permission Checks in Controllers

**Example: Driver-Only Endpoint**
```php
public function offerRide(Request $request)
{
    $user = auth()->user();
    
    // Check if user is a driver
    if (!in_array($user->user_preference, ['driver', 'both'])) {
        return response()->json([
            'success' => false,
            'error' => 'Unauthorized',
            'message' => 'Only drivers can offer rides'
        ], 403);
    }
    
    // Proceed with ride offering logic
}
```

### Protected Endpoints by Role

**Passenger Endpoints:**
- POST /api/v1/rides (request a ride)
- GET /api/v1/rides (search rides)
- POST /api/v1/bookings (book a ride)
- POST /api/v1/reviews (rate driver)

**Driver Endpoints:**
- POST /api/v1/rides/offer (offer a ride)
- POST /api/v1/rides/{id}/accept (accept booking)
- POST /api/v1/rides/{id}/complete (complete ride)
- POST /api/v1/reviews (rate passenger)

**Admin Endpoints:**
- GET /api/v1/admin/users
- POST /api/v1/admin/users/{id}/verify
- DELETE /api/v1/admin/users/{id}

### Checking User Role in Flutter

```dart
// Get current user role
final user = await getCurrentUser();
final isDriver = user.userPreference == 'driver' || user.userPreference == 'both';
final isPassenger = user.userPreference == 'passenger' || user.userPreference == 'both';

// Show/hide UI based on role
if (isDriver) {
  showDriverFeatures();
}
if (isPassenger) {
  showPassengerFeatures();
}
```


## Error Handling

### Authentication Error Codes

| Code | HTTP Status | Meaning | Action |
|------|------------|---------|--------|
| MISSING_TOKEN | 401 | No token provided | Redirect to login |
| INVALID_TOKEN | 401 | Token is malformed | Redirect to login |
| EXPIRED_TOKEN | 401 | Token has expired | Refresh token or re-login |
| REVOKED_TOKEN | 401 | Token was revoked | Redirect to login |
| INVALID_FIREBASE_TOKEN | 401 | Firebase token invalid | Re-authenticate with Firebase |
| USER_NOT_FOUND | 401 | User doesn't exist | Create account |
| USER_INACTIVE | 403 | User account disabled | Contact support |
| UNAUTHORIZED | 403 | User lacks permissions | Show permission error |

### Common Error Responses

**Missing Authorization Header:**
```json
{
  "success": false,
  "error": "MISSING_TOKEN",
  "message": "Authorization header is missing"
}
```

**Invalid Token:**
```json
{
  "success": false,
  "error": "INVALID_TOKEN",
  "message": "The provided token is invalid or malformed"
}
```

**Expired Token:**
```json
{
  "success": false,
  "error": "EXPIRED_TOKEN",
  "message": "Token has expired. Please login again."
}
```

**Insufficient Permissions:**
```json
{
  "success": false,
  "error": "UNAUTHORIZED",
  "message": "You do not have permission to access this resource"
}
```

**Firebase Token Invalid:**
```json
{
  "success": false,
  "error": "INVALID_FIREBASE_TOKEN",
  "message": "Firebase token verification failed"
}
```

### Error Handling in Flutter

**Recommended Implementation:**

```dart
class ApiClient {
  Future<T> request<T>(
    String method,
    String endpoint, {
    Map<String, dynamic>? data,
  }) async {
    try {
      final response = await dio.request(
        endpoint,
        options: Options(method: method),
        data: data,
      );
      
      return handleResponse<T>(response);
    } on DioError catch (e) {
      return handleError(e);
    }
  }

  void handleError(DioError error) {
    final statusCode = error.response?.statusCode;
    final errorCode = error.response?.data['error'];

    switch (statusCode) {
      case 401:
        // Authentication failed
        if (errorCode == 'EXPIRED_TOKEN') {
          // Try to refresh token
          refreshToken();
        } else {
          // Redirect to login
          navigateToLogin();
        }
        break;
      case 403:
        // Permission denied
        showErrorDialog('You do not have permission to perform this action');
        break;
      case 422:
        // Validation error
        showValidationErrors(error.response?.data['errors']);
        break;
      case 429:
        // Rate limited
        showErrorDialog('Too many requests. Please try again later.');
        break;
      default:
        showErrorDialog('An error occurred. Please try again.');
    }
  }
}
```

### Retry Logic

**Automatic Retry for Transient Errors:**

```dart
Future<T> requestWithRetry<T>(
  Future<T> Function() request, {
  int maxRetries = 3,
  Duration delay = const Duration(seconds: 1),
}) async {
  int retries = 0;
  
  while (retries < maxRetries) {
    try {
      return await request();
    } on DioError catch (e) {
      // Don't retry authentication errors
      if (e.response?.statusCode == 401) {
        rethrow;
      }
      
      // Retry on network errors or 5xx errors
      if (e.type == DioErrorType.connectionTimeout ||
          e.response?.statusCode == 500) {
        retries++;
        if (retries >= maxRetries) rethrow;
        await Future.delayed(delay * retries);
        continue;
      }
      
      rethrow;
    }
  }
}
```


## Security Best Practices

### 1. Token Storage (Flutter)

**DO:**
- Store tokens in secure storage (Keychain on iOS, Keystore on Android)
- Use `flutter_secure_storage` package
- Never log tokens
- Clear tokens on logout

**DON'T:**
- Store tokens in SharedPreferences (unencrypted)
- Store tokens in plain text files
- Log tokens in debug output
- Share tokens between apps

**Implementation:**
```dart
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class TokenStorage {
  static const _storage = FlutterSecureStorage();
  static const _tokenKey = 'api_token';

  static Future<void> saveToken(String token) async {
    await _storage.write(key: _tokenKey, value: token);
  }

  static Future<String?> getToken() async {
    return await _storage.read(key: _tokenKey);
  }

  static Future<void> deleteToken() async {
    await _storage.delete(key: _tokenKey);
  }
}
```

### 2. HTTPS Only

**Requirements:**
- All API calls must use HTTPS
- Certificate pinning recommended for production
- Never allow HTTP fallback

**Implementation:**
```dart
final dio = Dio();
dio.options.baseUrl = 'https://api.waylo-share.com';

// Certificate pinning
final SecurityContext securityContext = SecurityContext.defaultContext;
securityContext.setTrustedCertificates('assets/certificates/ca.pem');
```

### 3. Token Transmission

**DO:**
- Send token in Authorization header: `Authorization: Bearer {token}`
- Use standard Bearer scheme
- Include token in all authenticated requests

**DON'T:**
- Send token in URL query parameters
- Send token in request body
- Send token in cookies (for mobile apps)

**Implementation:**
```dart
final headers = {
  'Authorization': 'Bearer $token',
  'Content-Type': 'application/json',
};
```

### 4. Firebase Security Rules

**Firestore Rules (if using Firestore):**
```
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    match /users/{userId} {
      allow read, write: if request.auth.uid == userId;
    }
  }
}
```

### 5. Backend Security

**Rate Limiting:**
- Limit login attempts: 5 attempts per 15 minutes
- Limit API calls: 100 requests per minute per user
- Implement exponential backoff

**Configuration:**
```php
// config/rate-limiting.php
'login' => '5,15', // 5 attempts per 15 minutes
'api' => '100,60', // 100 requests per minute
```

### 6. Input Validation

**Always validate:**
- Email format
- Phone number format
- Token format
- Request data types

**Implementation:**
```php
$validated = $request->validate([
    'email' => 'required|email',
    'phone' => 'required|regex:/^[0-9]{10}$/',
]);
```

### 7. CORS Configuration

**Allowed Origins:**
```php
// config/cors.php
'allowed_origins' => [
    'https://waylo-share.com',
    'https://app.waylo-share.com',
],
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
'allowed_headers' => ['Content-Type', 'Authorization'],
```

### 8. Logging & Monitoring

**DO:**
- Log authentication events (login, logout, failed attempts)
- Monitor for suspicious patterns
- Alert on multiple failed login attempts
- Track token usage

**DON'T:**
- Log sensitive data (passwords, tokens, PII)
- Log full request/response bodies
- Store logs in plain text

**Implementation:**
```php
Log::info('User login successful', [
    'user_id' => $user->id,
    'ip_address' => request()->ip(),
    'timestamp' => now(),
]);

Log::warning('Failed login attempt', [
    'email' => $email,
    'ip_address' => request()->ip(),
    'attempts' => $attempts,
]);
```

### 9. Session Management

**Best Practices:**
- One token per device
- Allow multiple active sessions
- Provide "logout from all devices" option
- Track device information

**Implementation:**
```php
// Store device info with token
$token = $user->createToken('flutter-app', ['*'], [
    'device_id' => $request->input('device_id'),
    'device_name' => $request->input('device_name'),
    'device_type' => $request->input('device_type'),
]);
```

### 10. Encryption

**Encrypt:**
- Payment information
- Sensitive user data
- API keys and secrets

**Implementation:**
```php
// Encrypt payment details
$encrypted = encrypt($paymentDetails);

// Decrypt when needed
$decrypted = decrypt($encrypted);
```

### 11. Firebase Security

**Enable:**
- Email verification
- Phone number verification
- Two-factor authentication (optional)
- Account recovery options

**Configuration:**
```javascript
// Firebase Console Settings
- Require email verification
- Enable phone number sign-in
- Set password requirements
- Enable account recovery
```

### 12. API Key Management

**DO:**
- Store API keys in environment variables
- Rotate keys regularly
- Use different keys for different environments
- Restrict key permissions

**DON'T:**
- Commit API keys to version control
- Share API keys
- Use same key for all environments

**Implementation:**
```php
// .env
FIREBASE_PROJECT_ID=waylo-share-project
FIREBASE_CREDENTIALS_FILE=storage/firebase/credentials.json

// Usage
$projectId = config('firebase.project_id');
```


## Flutter Integration Examples

### 1. Setup Firebase in Flutter

**pubspec.yaml:**
```yaml
dependencies:
  firebase_core: ^2.24.0
  firebase_auth: ^4.14.0
  flutter_secure_storage: ^9.0.0
  dio: ^5.3.0
```

**Initialize Firebase:**
```dart
import 'package:firebase_core/firebase_core.dart';
import 'firebase_options.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp(
    options: DefaultFirebaseOptions.currentPlatform,
  );
  runApp(const MyApp());
}
```

### 2. Firebase Authentication

**Sign Up with Phone Number:**
```dart
class AuthService {
  final FirebaseAuth _auth = FirebaseAuth.instance;

  Future<void> signUpWithPhone(String phoneNumber) async {
    try {
      await _auth.verifyPhoneNumber(
        phoneNumber: phoneNumber,
        verificationCompleted: (PhoneAuthCredential credential) async {
          await _auth.signInWithCredential(credential);
        },
        verificationFailed: (FirebaseAuthException e) {
          print('Verification failed: ${e.message}');
        },
        codeSent: (String verificationId, int? resendToken) {
          // Show OTP input screen
          showOTPDialog(verificationId);
        },
        codeAutoRetrievalTimeout: (String verificationId) {},
      );
    } catch (e) {
      print('Error: $e');
    }
  }

  Future<void> verifyOTP(String verificationId, String otp) async {
    try {
      PhoneAuthCredential credential = PhoneAuthProvider.credential(
        verificationId: verificationId,
        smsCode: otp,
      );
      await _auth.signInWithCredential(credential);
    } catch (e) {
      print('Error: $e');
    }
  }
}
```

**Sign In with Email:**
```dart
Future<void> signInWithEmail(String email, String password) async {
  try {
    await _auth.signInWithEmailAndPassword(
      email: email,
      password: password,
    );
  } catch (e) {
    print('Error: $e');
  }
}
```

**Sign In with Google:**
```dart
import 'package:google_sign_in/google_sign_in.dart';

Future<void> signInWithGoogle() async {
  try {
    final GoogleSignInAccount? googleUser = await GoogleSignIn().signIn();
    final GoogleSignInAuthentication? googleAuth = 
        await googleUser?.authentication;
    
    final credential = GoogleAuthProvider.credential(
      accessToken: googleAuth?.accessToken,
      idToken: googleAuth?.idToken,
    );
    
    await _auth.signInWithCredential(credential);
  } catch (e) {
    print('Error: $e');
  }
}
```

### 3. Get Firebase ID Token

```dart
Future<String?> getFirebaseToken() async {
  try {
    final user = FirebaseAuth.instance.currentUser;
    if (user != null) {
      final token = await user.getIdToken();
      return token;
    }
  } catch (e) {
    print('Error getting token: $e');
  }
  return null;
}
```

### 4. Backend Login

```dart
class ApiAuthService {
  final Dio _dio = Dio();
  final TokenStorage _tokenStorage = TokenStorage();

  Future<User?> login() async {
    try {
      // Get Firebase token
      final firebaseToken = await getFirebaseToken();
      if (firebaseToken == null) {
        throw Exception('Failed to get Firebase token');
      }

      // Call backend login endpoint
      final response = await _dio.post(
        'https://api.waylo-share.com/api/v1/auth/login',
        options: Options(
          headers: {
            'Authorization': 'Bearer $firebaseToken',
            'Content-Type': 'application/json',
          },
        ),
      );

      if (response.statusCode == 200) {
        final data = response.data;
        
        // Save API token
        await _tokenStorage.saveToken(data['token']);
        
        // Save user data
        final user = User.fromJson(data['user']);
        await saveUserLocally(user);
        
        return user;
      }
    } catch (e) {
      print('Login error: $e');
    }
    return null;
  }
}
```

### 5. API Client with Token

```dart
class ApiClient {
  final Dio _dio = Dio();
  final TokenStorage _tokenStorage = TokenStorage();

  ApiClient() {
    _dio.interceptors.add(AuthInterceptor(_tokenStorage));
  }

  Future<T> get<T>(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      final response = await _dio.get(
        endpoint,
        queryParameters: queryParameters,
      );
      return response.data as T;
    } catch (e) {
      handleError(e);
      rethrow;
    }
  }

  Future<T> post<T>(
    String endpoint, {
    Map<String, dynamic>? data,
  }) async {
    try {
      final response = await _dio.post(endpoint, data: data);
      return response.data as T;
    } catch (e) {
      handleError(e);
      rethrow;
    }
  }

  void handleError(dynamic error) {
    if (error is DioError) {
      if (error.response?.statusCode == 401) {
        // Token expired or invalid
        _tokenStorage.deleteToken();
        navigateToLogin();
      }
    }
  }
}

class AuthInterceptor extends Interceptor {
  final TokenStorage _tokenStorage;

  AuthInterceptor(this._tokenStorage);

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final token = await _tokenStorage.getToken();
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }

  @override
  void onError(DioError err, ErrorInterceptorHandler handler) {
    if (err.response?.statusCode == 401) {
      _tokenStorage.deleteToken();
      navigateToLogin();
    }
    handler.next(err);
  }
}
```

### 6. Logout

```dart
Future<void> logout() async {
  try {
    // Call backend logout
    await _apiClient.post('/api/v1/auth/logout');
    
    // Clear local storage
    await _tokenStorage.deleteToken();
    await clearUserData();
    
    // Sign out from Firebase
    await FirebaseAuth.instance.signOut();
    
    // Navigate to login
    navigateToLogin();
  } catch (e) {
    print('Logout error: $e');
  }
}
```

### 7. Check Authentication Status

```dart
class AuthProvider extends ChangeNotifier {
  User? _user;
  bool _isLoading = true;

  User? get user => _user;
  bool get isLoading => _isLoading;
  bool get isAuthenticated => _user != null;

  Future<void> checkAuthStatus() async {
    try {
      final token = await _tokenStorage.getToken();
      if (token != null) {
        // Get current user from backend
        final response = await _apiClient.get('/api/v1/auth/me');
        _user = User.fromJson(response);
      }
    } catch (e) {
      print('Error checking auth status: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}
```

### 8. Protected Routes

```dart
class ProtectedRoute extends StatelessWidget {
  final Widget child;

  const ProtectedRoute({required this.child});

  @override
  Widget build(BuildContext context) {
    return Consumer<AuthProvider>(
      builder: (context, authProvider, _) {
        if (authProvider.isLoading) {
          return const Scaffold(
            body: Center(child: CircularProgressIndicator()),
          );
        }

        if (!authProvider.isAuthenticated) {
          return const LoginScreen();
        }

        return child;
      },
    );
  }
}

// Usage
ProtectedRoute(
  child: HomeScreen(),
)
```

### 9. Complete Login Flow Example

```dart
class LoginScreen extends StatefulWidget {
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _phoneController = TextEditingController();
  final _authService = ApiAuthService();
  bool _isLoading = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Login')),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            TextField(
              controller: _phoneController,
              decoration: const InputDecoration(
                labelText: 'Phone Number',
                hintText: '+919876543210',
              ),
            ),
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: _isLoading ? null : _handleLogin,
              child: _isLoading
                  ? const CircularProgressIndicator()
                  : const Text('Login'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _handleLogin() async {
    setState(() => _isLoading = true);
    try {
      final user = await _authService.login();
      if (user != null) {
        Navigator.of(context).pushReplacementNamed('/home');
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Login failed: $e')),
      );
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  void dispose() {
    _phoneController.dispose();
    super.dispose();
  }
}
```

### 10. Error Handling Example

```dart
Future<void> handleApiError(DioError error) async {
  final statusCode = error.response?.statusCode;
  final errorCode = error.response?.data['error'];

  switch (statusCode) {
    case 401:
      if (errorCode == 'EXPIRED_TOKEN') {
        showDialog(
          context: context,
          builder: (context) => AlertDialog(
            title: const Text('Session Expired'),
            content: const Text('Please login again'),
            actions: [
              TextButton(
                onPressed: () => navigateToLogin(),
                child: const Text('OK'),
              ),
            ],
          ),
        );
      }
      break;
    case 403:
      showSnackBar('You do not have permission to perform this action');
      break;
    case 422:
      final errors = error.response?.data['errors'];
      showValidationErrors(errors);
      break;
    case 429:
      showSnackBar('Too many requests. Please try again later.');
      break;
    default:
      showSnackBar('An error occurred. Please try again.');
  }
}
```


## Quick Reference

### API Endpoints

| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|----------------|
| POST | /api/v1/auth/login | Login with Firebase token | No |
| GET | /api/v1/auth/me | Get current user | Yes |
| POST | /api/v1/auth/logout | Logout user | Yes |
| DELETE | /api/v1/auth/delete-account | Delete account | Yes |

### Request Headers

```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Environment Variables

```
FIREBASE_PROJECT_ID=waylo-share-project
FIREBASE_CREDENTIALS_FILE=storage/firebase/firebase_credentials.json
AUTH_GUARD=sanctum
AUTH_MODEL=App\Models\User
```

### Common Issues & Solutions

**Issue: "Token not provided"**
- Solution: Ensure Authorization header is included in request
- Check: `Authorization: Bearer {token}`

**Issue: "Invalid token"**
- Solution: Token may be expired or malformed
- Action: Re-login to get new token

**Issue: "User not found"**
- Solution: User doesn't exist in database
- Action: Create account first

**Issue: "Unauthorized"**
- Solution: User lacks required permissions
- Action: Check user role and permissions

**Issue: Firebase token verification failed**
- Solution: Firebase credentials may be invalid
- Action: Verify Firebase configuration

### Testing Authentication

**Using cURL:**
```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Authorization: Bearer {firebase_token}" \
  -H "Content-Type: application/json"

# Get current user
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer {api_token}" \
  -H "Content-Type: application/json"

# Logout
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer {api_token}" \
  -H "Content-Type: application/json"
```

**Using Postman:**
1. Create new request
2. Set method to POST
3. Set URL to `http://localhost:8000/api/v1/auth/login`
4. Go to Headers tab
5. Add: `Authorization: Bearer {firebase_token}`
6. Add: `Content-Type: application/json`
7. Send request

### Debugging Tips

**Enable Debug Logging:**
```php
// config/logging.php
'channels' => [
    'auth' => [
        'driver' => 'single',
        'path' => storage_path('logs/auth.log'),
        'level' => 'debug',
    ],
],

// Usage
Log::channel('auth')->debug('Auth event', ['user_id' => $user->id]);
```

**Monitor Token Usage:**
```php
// Check active tokens for user
$tokens = $user->tokens()->where('revoked', false)->get();

// Revoke specific token
$user->tokens()->where('id', $tokenId)->update(['revoked' => true]);

// Revoke all tokens
$user->tokens()->update(['revoked' => true]);
```

**Test Firebase Token:**
```php
// Verify Firebase token manually
$firebaseService = app(FirebaseService::class);
try {
    $claims = $firebaseService->verifyToken($token);
    dd($claims);
} catch (Exception $e) {
    dd($e->getMessage());
}
```

### Performance Optimization

**Cache User Data:**
```php
// Cache user for 30 minutes
$user = Cache::remember("user.{$userId}", 30 * 60, function () use ($userId) {
    return User::find($userId);
});
```

**Optimize Token Queries:**
```php
// Add index to personal_access_tokens table
Schema::table('personal_access_tokens', function (Blueprint $table) {
    $table->index('token');
    $table->index('tokenable_id');
});
```

**Rate Limiting:**
```php
// Limit login attempts
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,15'); // 5 attempts per 15 minutes
```

### Related Documentation

- [API Endpoints](./API_ENDPOINTS.md)
- [Error Handling](./ERROR_HANDLING.md)
- [Security Best Practices](./PRODUCTION_HARDENING.md)
- [Flutter Integration Guide](./FLUTTER_INTEGRATION_GUIDE.md)
- [CORS Configuration](./CORS_CONFIGURATION.md)

