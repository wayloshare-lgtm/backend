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


## Vehicle Endpoints

### 6. Create Vehicle
**POST** `/api/v1/vehicles`

**Request Body:**
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

**Response (201):**
```json
{
  "success": true,
  "message": "Vehicle created successfully",
  "vehicle": {
    "id": 1,
    "user_id": 1,
    "vehicle_name": "My Sedan",
    "vehicle_type": "sedan",
    "license_plate": "KA01AB1234",
    "vehicle_color": "Black",
    "vehicle_year": 2023,
    "seating_capacity": 5,
    "is_default": false,
    "is_active": true,
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

---

### 7. List Vehicles
**GET** `/api/v1/vehicles`

**Response (200):**
```json
{
  "success": true,
  "message": "Vehicles retrieved successfully",
  "vehicles": [
    {
      "id": 1,
      "user_id": 1,
      "vehicle_name": "My Sedan",
      "vehicle_type": "sedan",
      "license_plate": "KA01AB1234",
      "vehicle_color": "Black",
      "vehicle_year": 2023,
      "seating_capacity": 5,
      "is_default": true,
      "is_active": true,
      "created_at": "2024-01-01T00:00:00Z"
    }
  ],
  "count": 1
}
```

---

### 8. Get Vehicle Details
**GET** `/api/v1/vehicles/{id}`

**Response (200):**
```json
{
  "success": true,
  "message": "Vehicle retrieved successfully",
  "vehicle": {
    "id": 1,
    "user_id": 1,
    "vehicle_name": "My Sedan",
    "vehicle_type": "sedan",
    "license_plate": "KA01AB1234",
    "vehicle_color": "Black",
    "vehicle_year": 2023,
    "seating_capacity": 5,
    "is_default": true,
    "is_active": true,
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

---

### 9. Update Vehicle
**PUT** `/api/v1/vehicles/{id}`

**Request Body:**
```json
{
  "vehicle_name": "Updated Sedan",
  "vehicle_color": "Red"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Vehicle updated successfully",
  "vehicle": {
    "id": 1,
    "user_id": 1,
    "vehicle_name": "Updated Sedan",
    "vehicle_type": "sedan",
    "license_plate": "KA01AB1234",
    "vehicle_color": "Red",
    "vehicle_year": 2023,
    "seating_capacity": 5,
    "is_default": true,
    "is_active": true,
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

---

### 10. Delete Vehicle
**DELETE** `/api/v1/vehicles/{id}`

**Response (200):**
```json
{
  "success": true,
  "message": "Vehicle deleted successfully"
}
```

---

### 11. Upload Vehicle Photo
**POST** `/api/v1/vehicles/{id}/photo`

**Request Body (multipart/form-data):**
```
vehicle_photo: [image file]
```

**Response (200):**
```json
{
  "success": true,
  "message": "Vehicle photo uploaded successfully",
  "vehicle_photo_url": "https://example.com/storage/vehicles/uuid.jpg"
}
```

---

### 12. Set Default Vehicle
**POST** `/api/v1/vehicles/{id}/set-default`

**Response (200):**
```json
{
  "success": true,
  "message": "Vehicle set as default successfully",
  "vehicle": {
    "id": 1,
    "user_id": 1,
    "vehicle_name": "My Sedan",
    "vehicle_type": "sedan",
    "license_plate": "KA01AB1234",
    "vehicle_color": "Black",
    "vehicle_year": 2023,
    "seating_capacity": 5,
    "is_default": true,
    "is_active": true,
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

---

## Booking Endpoints

### 13. Create Booking
**POST** `/api/v1/bookings`

**Request Body:**
```json
{
  "ride_id": 1,
  "seats_booked": 2,
  "passenger_name": "John Doe",
  "passenger_phone": "9876543210",
  "special_instructions": "Please wait at the main entrance",
  "luggage_info": "1 small bag",
  "accessibility_requirements": "Wheelchair accessible"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Booking created successfully",
  "booking": {
    "id": 1,
    "ride_id": 1,
    "passenger_id": 1,
    "seats_booked": 2,
    "passenger_name": "John Doe",
    "passenger_phone": "9876543210",
    "special_instructions": "Please wait at the main entrance",
    "luggage_info": "1 small bag",
    "accessibility_requirements": "Wheelchair accessible",
    "booking_status": "pending",
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

**Error (409) - Ride not available:**
```json
{
  "success": false,
  "error": "Ride is not available for booking"
}
```

**Error (409) - Duplicate booking:**
```json
{
  "success": false,
  "error": "You already have a booking for this ride"
}
```

**Error (422) - Validation error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "seats_booked": ["The seats booked must be between 1 and 8"]
  }
}
```

---

### 14. List Bookings
**GET** `/api/v1/bookings`

**Query Parameters:**
- `status` (optional): Filter by booking status (pending, confirmed, completed, cancelled)
- `page` (optional): Pagination page number (default: 1)
- `per_page` (optional): Items per page (default: 15)

**Response (200):**
```json
{
  "success": true,
  "message": "Bookings retrieved successfully",
  "bookings": [
    {
      "id": 1,
      "ride_id": 1,
      "passenger_id": 1,
      "seats_booked": 2,
      "passenger_name": "John Doe",
      "passenger_phone": "9876543210",
      "special_instructions": "Please wait at the main entrance",
      "luggage_info": "1 small bag",
      "accessibility_requirements": "Wheelchair accessible",
      "booking_status": "pending",
      "created_at": "2024-01-01T00:00:00Z"
    }
  ],
  "pagination": {
    "total": 10,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1
  }
}
```

---

### 15. Get Booking Details
**GET** `/api/v1/bookings/{id}`

**Response (200):**
```json
{
  "success": true,
  "message": "Booking retrieved successfully",
  "booking": {
    "id": 1,
    "ride_id": 1,
    "passenger_id": 1,
    "seats_booked": 2,
    "passenger_name": "John Doe",
    "passenger_phone": "9876543210",
    "special_instructions": "Please wait at the main entrance",
    "luggage_info": "1 small bag",
    "accessibility_requirements": "Wheelchair accessible",
    "booking_status": "pending",
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

---

### 16. Cancel Booking
**POST** `/api/v1/bookings/{id}/cancel`

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
  "booking": {
    "id": 1,
    "ride_id": 1,
    "passenger_id": 1,
    "seats_booked": 2,
    "passenger_name": "John Doe",
    "passenger_phone": "9876543210",
    "special_instructions": "Please wait at the main entrance",
    "luggage_info": "1 small bag",
    "accessibility_requirements": "Wheelchair accessible",
    "booking_status": "cancelled",
    "cancellation_reason": "Change of plans",
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

---

### 17. Get Booking History
**GET** `/api/v1/bookings/history`

**Query Parameters:**
- `page` (optional): Pagination page number (default: 1)
- `per_page` (optional): Items per page (default: 15)

**Response (200):**
```json
{
  "success": true,
  "message": "Booking history retrieved successfully",
  "bookings": [
    {
      "id": 1,
      "ride_id": 1,
      "passenger_id": 1,
      "seats_booked": 2,
      "passenger_name": "John Doe",
      "passenger_phone": "9876543210",
      "special_instructions": "Please wait at the main entrance",
      "luggage_info": "1 small bag",
      "accessibility_requirements": "Wheelchair accessible",
      "booking_status": "completed",
      "created_at": "2024-01-01T00:00:00Z"
    }
  ],
  "pagination": {
    "total": 5,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1
  }
}
```


## Review Endpoints

### 18. Create Review
**POST** `/api/v1/reviews`

**Request Body:**
```json
{
  "ride_id": 1,
  "reviewee_id": 2,
  "rating": 5,
  "comment": "Great ride experience!",
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
  "review": {
    "id": 1,
    "ride_id": 1,
    "reviewer_id": 1,
    "reviewee_id": 2,
    "rating": 5,
    "comment": "Great ride experience!",
    "categories": {
      "cleanliness": 5,
      "driving": 4,
      "communication": 5,
      "comfort": 4
    },
    "photos": null,
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

**Error (422) - Validation error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "rating": ["The rating must be between 1 and 5"],
    "categories": ["The categories field must be valid JSON"]
  }
}
```

---

### 19. Get Review
**GET** `/api/v1/reviews/{id}`

**Response (200):**
```json
{
  "success": true,
  "message": "Review retrieved successfully",
  "review": {
    "id": 1,
    "ride_id": 1,
    "reviewer_id": 1,
    "reviewee_id": 2,
    "rating": 5,
    "comment": "Great ride experience!",
    "categories": {
      "cleanliness": 5,
      "driving": 4,
      "communication": 5,
      "comfort": 4
    },
    "photos": null,
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

---

### 20. Get Reviews for User
**GET** `/api/v1/reviews/user/{user_id}`

**Query Parameters:**
- `page` (optional): Pagination page number (default: 1)
- `per_page` (optional): Items per page (default: 15)

**Response (200):**
```json
{
  "success": true,
  "message": "Reviews retrieved successfully",
  "reviews": [
    {
      "id": 1,
      "ride_id": 1,
      "reviewer_id": 1,
      "reviewee_id": 2,
      "rating": 5,
      "comment": "Great ride experience!",
      "categories": {
        "cleanliness": 5,
        "driving": 4,
        "communication": 5,
        "comfort": 4
      },
      "photos": null,
      "created_at": "2024-01-01T00:00:00Z"
    }
  ],
  "pagination": {
    "total": 10,
    "per_page": 15,
    "current_page": 1,
    "last_page": 1
  }
}
```

---

### 21. Get Reviews for Ride
**GET** `/api/v1/reviews/ride/{ride_id}`

**Response (200):**
```json
{
  "success": true,
  "message": "Reviews retrieved successfully",
  "reviews": [
    {
      "id": 1,
      "ride_id": 1,
      "reviewer_id": 1,
      "reviewee_id": 2,
      "rating": 5,
      "comment": "Great ride experience!",
      "categories": {
        "cleanliness": 5,
        "driving": 4,
        "communication": 5,
        "comfort": 4
      },
      "photos": null,
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

---

## Categories Field Documentation

The `categories` field in reviews allows for flexible, category-based ratings. It stores JSON data with category names as keys and ratings (1-5) as values.

### Supported Categories (Examples)
- `cleanliness`: Rating for vehicle cleanliness
- `driving`: Rating for driving quality
- `communication`: Rating for communication
- `comfort`: Rating for ride comfort
- `safety`: Rating for safety
- Custom categories can be added as needed

### Categories Field Specifications
- **Type**: JSON (nullable)
- **Format**: Object with string keys and integer values (1-5)
- **Example**:
  ```json
  {
    "cleanliness": 5,
    "driving": 4,
    "communication": 5,
    "comfort": 4,
    "safety": 5
  }
  ```
- **Flexibility**: Supports any category names and flexible structures
- **Validation**: Each rating value must be between 1 and 5 (if provided)
- **Optional**: The entire categories field is optional and can be null



## Chat & Messaging Endpoints

### 22. Create Chat
**POST** `/api/v1/chats`

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
  "data": {
    "id": 1,
    "ride_id": 1,
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z"
  }
}
```

---

### 23. List Chats
**GET** `/api/v1/chats`

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "ride_id": 1,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z",
      "ride": {
        "id": 1,
        "driver_id": 1,
        "passenger_id": 2
      },
      "messages": [
        {
          "id": 1,
          "chat_id": 1,
          "sender_id": 1,
          "message": "Hello!",
          "message_type": "text",
          "attachment": null,
          "is_read": true,
          "created_at": "2024-01-01T00:00:00Z"
        }
      ]
    }
  ]
}
```

---

### 24. Send Message with Optional Attachment
**POST** `/api/v1/chats/{chat}/messages`

**Request Body (multipart/form-data):**
```
message: "Hello, how are you?"
message_type: "text"
attachment: [optional file - JPG, PNG, or PDF, max 10MB]
metadata: {"key": "value"}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Message sent successfully",
  "data": {
    "id": 1,
    "chat_id": 1,
    "sender_id": 1,
    "message": "Hello, how are you?",
    "message_type": "text",
    "attachment": "messages/550e8400-e29b-41d4-a716-446655440000.jpg",
    "metadata": {
      "key": "value"
    },
    "is_read": false,
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

**Error (422) - Validation error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "message_type": ["The message type must be one of: text, image, location"],
    "attachment": ["The attachment must be a file of type: jpeg, png, pdf"]
  }
}
```

**Error (422) - File upload failed:**
```json
{
  "success": false,
  "error": "FILE_UPLOAD_FAILED",
  "message": "File size exceeds 10MB limit"
}
```

### Attachment Field Specifications
- **Type**: String (file path, nullable)
- **Supported Formats**: JPG, PNG, PDF
- **Max Size**: 10MB
- **Storage**: Private disk (outside public directory)
- **Filename**: UUID-based for security
- **Access**: Via signed URLs through FileUploadService

---

### 25. Get Messages for Chat
**GET** `/api/v1/chats/{chat}/messages`

**Query Parameters:**
- `per_page` (optional): Items per page (default: 20)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "chat_id": 1,
        "sender_id": 1,
        "message": "Hello!",
        "message_type": "text",
        "attachment": null,
        "metadata": null,
        "is_read": true,
        "read_at": "2024-01-01T00:05:00Z",
        "created_at": "2024-01-01T00:00:00Z",
        "sender": {
          "id": 1,
          "name": "John Doe",
          "profile_photo_url": "https://example.com/photos/user1.jpg"
        }
      },
      {
        "id": 2,
        "chat_id": 1,
        "sender_id": 2,
        "message": "Hi there!",
        "message_type": "image",
        "attachment": "messages/550e8400-e29b-41d4-a716-446655440001.jpg",
        "metadata": null,
        "is_read": false,
        "read_at": null,
        "created_at": "2024-01-01T00:02:00Z",
        "sender": {
          "id": 2,
          "name": "Jane Smith",
          "profile_photo_url": "https://example.com/photos/user2.jpg"
        }
      }
    ],
    "pagination": {
      "total": 2,
      "per_page": 20,
      "current_page": 1,
      "last_page": 1
    }
  }
}
```

---

### 26. Mark Messages as Read
**POST** `/api/v1/chats/{chat}/mark-read`

**Response (200):**
```json
{
  "success": true,
  "message": "Messages marked as read"
}
```

---

### 27. Delete Chat
**DELETE** `/api/v1/chats/{chat}`

**Response (200):**
```json
{
  "success": true,
  "message": "Chat deleted successfully"
}
```

---

## Message Types

The `message_type` field supports the following types:

| Type | Description | Attachment Required |
|------|-------------|-------------------|
| `text` | Plain text message | No |
| `image` | Image message | Yes (JPG/PNG) |
| `location` | Location sharing message | No (metadata contains coordinates) |

---

## File Upload Best Practices

### For Attachments:
1. Always validate file type and size on client side before upload
2. Use multipart/form-data for file uploads
3. Include appropriate error handling for upload failures
4. Store returned file path for future reference
5. Use FileUploadService methods to retrieve signed URLs for access

### Security Considerations:
- Files are stored in private disk (not publicly accessible)
- Filenames are UUID-based to prevent enumeration
- Mime type validation prevents malicious uploads
- File size limits prevent storage abuse
- All uploads are logged for audit purposes


## Notification Endpoints

### 28. Register FCM Token
**POST** `/api/v1/notifications/fcm-token`

**Request Body:**
```json
{
  "fcm_token": "eJxlj91uwjAMhV9l5XoXEgMSV0hIXCBNV5MmTrOWJiR2QFXVd18CUi...",
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
  "data": {
    "id": 1,
    "fcm_token": "eJxlj91uwjAMhV9l5XoXEgMSV0hIXCBNV5MmTrOWJiR2QFXVd18CUi...",
    "device_type": "android",
    "device_id": "device_123456",
    "device_name": "Samsung Galaxy S21",
    "is_active": true,
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z"
  }
}
```

**Error (401) - Unauthorized:**
```json
{
  "success": false,
  "error": "User not authenticated"
}
```

**Error (422) - Validation error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "fcm_token": ["The fcm token field is required"],
    "device_type": ["The device type must be either android or ios"]
  }
}
```

### FCM Token Registration Details
- **Purpose**: Register device FCM tokens for push notifications
- **Authentication**: Required (Sanctum token)
- **Device Types**: `android` or `ios`
- **Optional Fields**: `device_id`, `device_name`
- **Behavior**: Creates new token or updates existing token for the same FCM token
- **Token Status**: Automatically marked as active upon registration

---

### 29. Get Notification Preferences
**GET** `/api/v1/notifications/preferences`

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "notification_type": "ride_updates",
      "is_enabled": true,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    },
    {
      "id": 2,
      "notification_type": "messages",
      "is_enabled": true,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

---

### 30. Update Notification Preferences
**POST** `/api/v1/notifications/preferences`

**Request Body:**
```json
{
  "preferences": [
    {
      "notification_type": "ride_updates",
      "is_enabled": true
    },
    {
      "notification_type": "messages",
      "is_enabled": false
    },
    {
      "notification_type": "reviews",
      "is_enabled": true
    },
    {
      "notification_type": "promotions",
      "is_enabled": false
    },
    {
      "notification_type": "system_alerts",
      "is_enabled": true
    },
    {
      "notification_type": "driver_requests",
      "is_enabled": true
    },
    {
      "notification_type": "booking_confirmations",
      "is_enabled": true
    }
  ]
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Notification preferences updated successfully",
  "data": [
    {
      "id": 1,
      "notification_type": "ride_updates",
      "is_enabled": true,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    },
    {
      "id": 2,
      "notification_type": "messages",
      "is_enabled": false,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

### Supported Notification Types
- `ride_updates`: Updates about ride status changes
- `messages`: New messages from other users
- `reviews`: New reviews received
- `promotions`: Promotional offers and discounts
- `system_alerts`: System-level alerts and notifications
- `driver_requests`: Driver ride requests
- `booking_confirmations`: Booking confirmation notifications

---

### 31. Get All Notifications
**GET** `/api/v1/notifications`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "fcm_tokens": [
      {
        "id": 1,
        "fcm_token": "eJxlj91uwjAMhV9l5XoXEgMSV0hIXCBNV5MmTrOWJiQ2QFXVd18CUi...",
        "device_type": "android",
        "device_id": "device_123456",
        "device_name": "Samsung Galaxy S21",
        "is_active": true,
        "created_at": "2024-01-01T00:00:00Z",
        "updated_at": "2024-01-01T00:00:00Z"
      },
      {
        "id": 2,
        "fcm_token": "eJxlj91uwjAMhV9l5XoXEgMSV0hIXCBNV5MmTrOWJiQ2QFXVd18CUi...",
        "device_type": "ios",
        "device_id": "device_789012",
        "device_name": "iPhone 13",
        "is_active": true,
        "created_at": "2024-01-01T00:00:00Z",
        "updated_at": "2024-01-01T00:00:00Z"
      }
    ],
    "preferences": [
      {
        "id": 1,
        "notification_type": "ride_updates",
        "is_enabled": true,
        "created_at": "2024-01-01T00:00:00Z",
        "updated_at": "2024-01-01T00:00:00Z"
      },
      {
        "id": 2,
        "notification_type": "messages",
        "is_enabled": true,
        "created_at": "2024-01-01T00:00:00Z",
        "updated_at": "2024-01-01T00:00:00Z"
      }
    ]
  }
}
```

---

## Location Tracking Endpoints

### 32. Update Location
**POST** `/api/v1/locations/update`

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
  "data": {
    "id": 1,
    "ride_id": 1,
    "latitude": 28.6139,
    "longitude": 77.2090,
    "accuracy": 5.0,
    "speed": 25.5,
    "heading": 180.0,
    "altitude": 100.0,
    "timestamp": "2024-01-01T00:00:00",
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

**Error (401) - Unauthorized:**
```json
{
  "success": false,
  "error": "User not authenticated"
}
```

**Error (403) - Forbidden:**
```json
{
  "success": false,
  "error": "Unauthorized",
  "message": "You are not authorized to update location for this ride"
}
```

**Error (422) - Validation error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "latitude": ["The latitude must be between -90 and 90"],
    "longitude": ["The longitude must be between -180 and 180"]
  }
}
```

### Location Update Details
- **Purpose**: Update driver location during an active ride
- **Authentication**: Required (Sanctum token)
- **Authorization**: Only the driver of the ride can update location
- **Required Fields**: `ride_id`, `latitude`, `longitude`
- **Optional Fields**: `accuracy`, `speed`, `heading`, `altitude`, `timestamp`
- **Default Timestamp**: Current server time if not provided
- **Coordinates**: WGS84 format (latitude: -90 to 90, longitude: -180 to 180)

---

### 33. Get Location History
**GET** `/api/v1/locations/history/{ride_id}`

**Query Parameters:**
- `limit` (optional): Number of records to return (default: 100, max: 1000)
- `offset` (optional): Number of records to skip (default: 0)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "ride_id": 1,
      "latitude": 28.6200,
      "longitude": 77.2100,
      "accuracy": 5.0,
      "speed": 30.0,
      "heading": 180.0,
      "altitude": 100.0,
      "timestamp": "2024-01-01T00:05:00",
      "created_at": "2024-01-01T00:05:00Z"
    },
    {
      "id": 4,
      "ride_id": 1,
      "latitude": 28.6180,
      "longitude": 77.2080,
      "accuracy": 5.0,
      "speed": 25.0,
      "heading": 180.0,
      "altitude": 100.0,
      "timestamp": "2024-01-01T00:04:00",
      "created_at": "2024-01-01T00:04:00Z"
    }
  ],
  "pagination": {
    "total": 10,
    "limit": 100,
    "offset": 0
  }
}
```

**Error (401) - Unauthorized:**
```json
{
  "success": false,
  "error": "User not authenticated"
}
```

**Error (403) - Forbidden:**
```json
{
  "success": false,
  "error": "Unauthorized",
  "message": "You are not authorized to view location history for this ride"
}
```

**Error (404) - Not Found:**
```json
{
  "success": false,
  "error": "Ride not found"
}
```

### Location History Details
- **Purpose**: Retrieve location history for a specific ride
- **Authentication**: Required (Sanctum token)
- **Authorization**: Only rider or driver of the ride can view
- **Ordering**: By timestamp (most recent first)
- **Pagination**: Supports limit and offset parameters
- **Response Fields**: All location fields including coordinates, accuracy, speed, heading, altitude, and timestamp

---

### 34. Get Current Location
**GET** `/api/v1/locations/current/{ride_id}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "ride_id": 1,
    "latitude": 28.6200,
    "longitude": 77.2100,
    "accuracy": 5.0,
    "speed": 30.0,
    "heading": 180.0,
    "altitude": 100.0,
    "timestamp": "2024-01-01T00:05:00",
    "created_at": "2024-01-01T00:05:00Z"
  }
}
```

**Error (401) - Unauthorized:**
```json
{
  "success": false,
  "error": "User not authenticated"
}
```

**Error (403) - Forbidden:**
```json
{
  "success": false,
  "error": "Unauthorized",
  "message": "You are not authorized to view current location for this ride"
}
```

**Error (404) - Not Found (Ride):**
```json
{
  "success": false,
  "error": "Ride not found"
}
```

**Error (404) - Not Found (Location):**
```json
{
  "success": false,
  "error": "No location data available",
  "message": "No location updates have been recorded for this ride yet"
}
```

### Current Location Details
- **Purpose**: Get the most recent location for a ride
- **Authentication**: Required (Sanctum token)
- **Authorization**: Only rider or driver of the ride can view
- **Returns**: Latest location record ordered by timestamp (descending)
- **Use Case**: Real-time driver location tracking during active rides

---

## Security Considerations:
- Files are stored in private disk (not publicly accessible)
- Filenames are UUID-based to prevent enumeration
- Mime type validation prevents malicious uploads
- File size limits prevent storage abuse
- All uploads are logged for audit purposes
- FCM tokens are securely stored and associated with user accounts
- Notification preferences are user-specific and encrypted
- Location data is only accessible to ride participants (driver and rider)
- Location history is indexed by ride_id and timestamp for efficient queries
