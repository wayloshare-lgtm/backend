# WayloShare API Endpoints

## Authentication Flow

### 1. Login with Firebase Token
**POST** `/api/v1/auth/login`

**Headers:**
```
Authorization: Bearer {firebase_id_token}
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
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz..."
}
```

**Error (401):**
```json
{
  "success": false,
  "error": "Authentication failed",
  "message": "Token verification failed: ..."
}
```

---

## Protected Endpoints (Require Sanctum Token)

All protected endpoints require the Sanctum token in the Authorization header:
```
Authorization: Bearer {sanctum_api_token}
```

### 2. Get Current User Profile
**GET** `/api/v1/auth/me`

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

---

### 3. Logout
**POST** `/api/v1/auth/logout`

**Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### 4. Delete Account
**DELETE** `/api/v1/auth/delete-account`

**Response (200):**
```json
{
  "success": true,
  "message": "Account deleted successfully"
}
```

---

### 5. Health Check
**GET** `/api/v1/health`

**Response (200):**
```json
{
  "status": "ok",
  "timestamp": "2024-01-01T00:00:00Z",
  "user_id": 1
}
```

---

## Architecture

### Token Flow
1. Client sends Firebase ID token to `/api/v1/auth/login`
2. Backend verifies Firebase token using Firebase Admin SDK
3. Backend creates/updates user in database
4. Backend generates Sanctum API token
5. Client receives Sanctum token and uses it for all future requests
6. All protected endpoints verify Sanctum token (not Firebase token)

### Security
- Firebase tokens are verified only at login
- Sanctum tokens are used for all subsequent requests
- Tokens are stored in `personal_access_tokens` table
- Tokens can be revoked on logout
- All tokens are deleted when user account is deleted

### Error Handling
- 401: Unauthorized (missing or invalid token)
- 400: Bad request (validation error)
- 500: Server error

---

## Testing with cURL

### Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Authorization: Bearer {firebase_token}" \
  -H "Content-Type: application/json"
```

### Get Profile (using Sanctum token)
```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer {sanctum_token}"
```

### Logout
```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer {sanctum_token}"
```

### Health Check
```bash
curl -X GET http://localhost:8000/api/v1/health \
  -H "Authorization: Bearer {sanctum_token}"
```

---

## Database Schema

### users table
- id (bigint, primary key)
- firebase_uid (string, unique)
- name (string, nullable)
- phone (string, nullable, indexed)
- email (string, nullable)
- role (enum: rider, driver)
- is_active (boolean, default: true)
- is_verified (boolean, default: false)
- created_at (timestamp)
- updated_at (timestamp)

### personal_access_tokens table (Sanctum)
- id (bigint, primary key)
- tokenable_type (string)
- tokenable_id (bigint)
- name (string)
- token (string, unique, hashed)
- abilities (json)
- last_used_at (timestamp, nullable)
- expires_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)
