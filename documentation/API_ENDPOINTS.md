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


## Payment Methods Endpoints

### 35. Add Payment Method
**POST** `/api/v1/payment-methods`

**Request Body:**
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

**Response (201):**
```json
{
  "success": true,
  "message": "Payment method added successfully",
  "payment_method": {
    "id": 1,
    "user_id": 1,
    "payment_type": "card",
    "payment_details": {
      "card_number": "****1234",
      "expiry": "12/25",
      "holder_name": "John Doe"
    },
    "is_default": true,
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
    "payment_type": ["The payment type must be one of: card, wallet, upi"],
    "payment_details": ["The payment details field is required"]
  }
}
```

### Payment Method Details
- **Purpose**: Add a new payment method for the authenticated user
- **Authentication**: Required (Sanctum token)
- **Payment Types**: `card`, `wallet`, `upi`
- **Default Behavior**: First payment method is automatically set as default
- **Encryption**: Payment details are encrypted in the database
- **is_default**: If true, other payment methods are automatically unset as default

---

### 36. Get Payment Methods
**GET** `/api/v1/payment-methods`

**Response (200):**
```json
{
  "success": true,
  "message": "Payment methods retrieved successfully",
  "payment_methods": [
    {
      "id": 1,
      "user_id": 1,
      "payment_type": "card",
      "payment_details": {
        "card_number": "****1234",
        "expiry": "12/25",
        "holder_name": "John Doe"
      },
      "is_default": true,
      "is_active": true,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    },
    {
      "id": 2,
      "user_id": 1,
      "payment_type": "upi",
      "payment_details": {
        "upi_id": "user@bank"
      },
      "is_default": false,
      "is_active": true,
      "created_at": "2024-01-01T00:01:00Z",
      "updated_at": "2024-01-01T00:01:00Z"
    }
  ],
  "count": 2
}
```

**Error (401) - Unauthorized:**
```json
{
  "success": false,
  "error": "User not authenticated"
}
```

### Payment Methods List Details
- **Purpose**: Retrieve all active payment methods for the authenticated user
- **Authentication**: Required (Sanctum token)
- **Ordering**: Default payment methods first, then by creation date (newest first)
- **Filtering**: Only active payment methods are returned
- **Encryption**: Payment details are automatically decrypted when retrieved

---

### 37. Update Payment Method
**PUT** `/api/v1/payment-methods/{id}`

**Request Body:**
```json
{
  "payment_type": "wallet",
  "payment_details": {
    "wallet_id": "wallet123"
  },
  "is_active": true
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Payment method updated successfully",
  "payment_method": {
    "id": 1,
    "user_id": 1,
    "payment_type": "wallet",
    "payment_details": {
      "wallet_id": "wallet123"
    },
    "is_default": true,
    "is_active": true,
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:05:00Z"
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
  "message": "You do not have permission to update this payment method"
}
```

**Error (422) - Validation error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "payment_type": ["The payment type must be one of: card, wallet, upi"]
  }
}
```

### Update Payment Method Details
- **Purpose**: Update an existing payment method
- **Authentication**: Required (Sanctum token)
- **Authorization**: Only the owner of the payment method can update it
- **Updatable Fields**: `payment_type`, `payment_details`, `is_active`
- **Encryption**: Updated payment details are automatically encrypted

---

### 38. Delete Payment Method
**DELETE** `/api/v1/payment-methods/{id}`

**Response (200):**
```json
{
  "success": true,
  "message": "Payment method deleted successfully"
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
  "message": "You do not have permission to delete this payment method"
}
```

**Error (404) - Not Found:**
```json
{
  "success": false,
  "error": "Payment method not found"
}
```

### Delete Payment Method Details
- **Purpose**: Delete a payment method for the authenticated user
- **Authentication**: Required (Sanctum token)
- **Authorization**: Only the owner of the payment method can delete it
- **Default Handling**: If the deleted payment method was default, the most recently created payment method becomes the new default
- **Soft Delete**: Payment methods are permanently deleted (not soft deleted)

---

### 39. Set Default Payment Method
**POST** `/api/v1/payment-methods/{id}/set-default`

**Response (200):**
```json
{
  "success": true,
  "message": "Payment method set as default successfully",
  "payment_method": {
    "id": 2,
    "user_id": 1,
    "payment_type": "upi",
    "payment_details": {
      "upi_id": "user@bank"
    },
    "is_default": true,
    "is_active": true,
    "created_at": "2024-01-01T00:01:00Z",
    "updated_at": "2024-01-01T00:10:00Z"
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
  "message": "You do not have permission to update this payment method"
}
```

**Error (404) - Not Found:**
```json
{
  "success": false,
  "error": "Payment method not found"
}
```

### Set Default Payment Method Details
- **Purpose**: Set a payment method as the default for the authenticated user
- **Authentication**: Required (Sanctum token)
- **Authorization**: Only the owner of the payment method can set it as default
- **Behavior**: All other payment methods for the user are automatically unset as default
- **Response**: Returns the updated payment method with `is_default: true`

---

## Payment Methods Database Schema

### payment_methods table
- id (bigint, primary key)
- user_id (bigint, foreign key to users)
- payment_type (enum: card, wallet, upi)
- payment_details (longtext, encrypted JSON)
- is_default (boolean, default: false)
- is_active (boolean, default: true)
- created_at (timestamp)
- updated_at (timestamp)

### Indexes
- user_id (for filtering by user)
- is_default (for finding default payment method)

### Encryption
- payment_details field is encrypted using Laravel's encryption
- Automatically decrypted when accessed through the model
- Automatically encrypted when saved to the database



## Search Available Rides Endpoint

### Search Available Rides (Offered by Drivers)
**GET** `/api/v1/rides/available`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Query Parameters:**
```
from_location (optional): string - Filter by pickup location (partial match)
to_location (optional): string - Filter by dropoff location (partial match)
date (optional): string (Y-m-d format) - Filter by ride date
time_from (optional): string (Y-m-d H:i:s format) - Filter rides from this time
time_to (optional): string (Y-m-d H:i:s format) - Filter rides until this time
seats_needed (optional): integer (1-8) - Minimum seats required
price_min (optional): numeric - Minimum price per seat
price_max (optional): numeric - Maximum price per seat
ac_available (optional): boolean (1/0 or true/false) - Filter by AC availability
wifi_available (optional): boolean (1/0 or true/false) - Filter by WiFi availability
smoking_allowed (optional): boolean (1/0 or true/false) - Filter by smoking policy
sort_by (optional): string (price|rating|departure_time) - Sort field (default: price)
sort_order (optional): string (asc|desc) - Sort order (default: asc)
page (optional): integer - Page number for pagination (default: 1)
per_page (optional): integer (1-100) - Results per page (default: 15)
```

**Response (200):**
```json
{
  "success": true,
  "message": "Available rides retrieved successfully",
  "data": [
    {
      "id": 1,
      "pickup_location": "Downtown Station",
      "pickup_lat": 12.9716,
      "pickup_lng": 77.5946,
      "dropoff_location": "Airport",
      "dropoff_lat": 13.1939,
      "dropoff_lng": 77.7068,
      "estimated_distance_km": 25.5,
      "estimated_duration_minutes": 45,
      "estimated_fare": 500.00,
      "available_seats": 3,
      "price_per_seat": 250.00,
      "description": "Comfortable sedan with AC",
      "preferences": {
        "music_genre": "classical",
        "temperature": 22
      },
      "ac_available": true,
      "wifi_available": false,
      "music_preference": "Bollywood",
      "smoking_allowed": false,
      "requested_at": "2024-01-01T10:00:00Z",
      "driver": {
        "id": 5,
        "name": "John Driver",
        "phone": "+1234567890",
        "profile_photo_url": "https://example.com/photo.jpg",
        "rating": 4.8,
        "total_rides": 150
      }
    }
  ],
  "pagination": {
    "total": 25,
    "per_page": 15,
    "current_page": 1,
    "last_page": 2,
    "from": 1,
    "to": 15
  }
}
```

**Error (422) - Validation Failed:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "seats_needed": ["The seats needed field must be between 1 and 8."],
    "sort_by": ["The sort by field must be one of: price, rating, departure_time."]
  }
}
```

**Error (401) - Unauthorized:**
```json
{
  "success": false,
  "error": "Unauthorized"
}
```

**Use Cases:**
- Passengers search for available rides offered by drivers
- Filter by location, date, time, seats, price, and amenities
- Sort results by price, driver rating, or departure time
- Paginate through large result sets
- View driver information and vehicle details

**Notes:**
- Only returns rides with status "offered" and available_seats > 0
- Boolean query parameters accept: 1/0, true/false
- Location filtering uses partial string matching (case-insensitive)
- Results are sorted by price (ascending) by default
- Pagination defaults to 15 results per page
- Driver information includes rating and total rides completed


## Get Ride Details Endpoint

### Get Ride Details
**GET** `/api/v1/rides/{ride_id}`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Path Parameters:**
- `ride_id` (required): integer - The ID of the ride to retrieve

**Response (200):**
```json
{
  "success": true,
  "ride": {
    "id": 1,
    "rider_id": 2,
    "driver_id": 3,
    "pickup_location": "Downtown Station",
    "pickup_lat": 12.9716,
    "pickup_lng": 77.5946,
    "dropoff_location": "Airport",
    "dropoff_lat": 13.1939,
    "dropoff_lng": 77.7068,
    "estimated_distance_km": 25.5,
    "estimated_duration_minutes": 45,
    "estimated_fare": 500.00,
    "actual_distance_km": 26.0,
    "actual_duration_minutes": 48,
    "actual_fare": 520.00,
    "toll_amount": 50.00,
    "status": "completed",
    "cancellation_reason": null,
    "requested_at": "2024-01-01T10:00:00Z",
    "accepted_at": "2024-01-01T10:05:00Z",
    "arrived_at": "2024-01-01T10:45:00Z",
    "started_at": "2024-01-01T10:50:00Z",
    "completed_at": "2024-01-01T11:38:00Z",
    "cancelled_at": null,
    "available_seats": 3,
    "price_per_seat": 250.00,
    "description": "Comfortable sedan with AC",
    "preferences": {
      "music_genre": "classical",
      "temperature": 22
    },
    "ac_available": true,
    "wifi_available": false,
    "music_preference": "Bollywood",
    "smoking_allowed": false,
    "created_at": "2024-01-01T10:00:00Z",
    "updated_at": "2024-01-01T11:38:00Z"
  }
}
```

**Error (401) - Unauthorized:**
```json
{
  "success": false,
  "error": "Unauthorized"
}
```

**Error (403) - Forbidden (Not rider or driver of this ride):**
```json
{
  "success": false,
  "error": "Unauthorized"
}
```

**Error (404) - Not Found:**
```json
{
  "success": false,
  "error": "Not found"
}
```

**Error (400) - Server Error:**
```json
{
  "success": false,
  "error": "Failed to fetch ride",
  "message": "Error details"
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Unique ride identifier |
| rider_id | integer | ID of the passenger who requested the ride |
| driver_id | integer | ID of the driver who accepted the ride (null if not accepted) |
| pickup_location | string | Name/address of pickup location |
| pickup_lat | float | Latitude of pickup location (-90 to 90) |
| pickup_lng | float | Longitude of pickup location (-180 to 180) |
| dropoff_location | string | Name/address of dropoff location |
| dropoff_lat | float | Latitude of dropoff location (-90 to 90) |
| dropoff_lng | float | Longitude of dropoff location (-180 to 180) |
| estimated_distance_km | float | Estimated distance in kilometers |
| estimated_duration_minutes | integer | Estimated duration in minutes |
| estimated_fare | float | Estimated fare amount |
| actual_distance_km | float | Actual distance traveled (null if not completed) |
| actual_duration_minutes | integer | Actual duration in minutes (null if not completed) |
| actual_fare | float | Actual fare charged (null if not completed) |
| toll_amount | float | Toll charges if applicable |
| status | string | Current ride status (requested, accepted, arrived, started, completed, cancelled, offered) |
| cancellation_reason | string | Reason for cancellation (null if not cancelled) |
| requested_at | datetime | Timestamp when ride was requested |
| accepted_at | datetime | Timestamp when driver accepted (null if not accepted) |
| arrived_at | datetime | Timestamp when driver arrived at pickup (null if not arrived) |
| started_at | datetime | Timestamp when ride started (null if not started) |
| completed_at | datetime | Timestamp when ride completed (null if not completed) |
| cancelled_at | datetime | Timestamp when ride was cancelled (null if not cancelled) |
| available_seats | integer | Number of available seats (for offered rides) |
| price_per_seat | float | Price per seat (for offered rides) |
| description | string | Ride description/notes |
| preferences | object | JSON object with ride preferences (music genre, temperature, etc.) |
| ac_available | boolean | Whether AC is available in the vehicle |
| wifi_available | boolean | Whether WiFi is available in the vehicle |
| music_preference | string | Driver's music preference |
| smoking_allowed | boolean | Whether smoking is allowed |
| created_at | datetime | Timestamp when ride record was created |
| updated_at | datetime | Timestamp when ride record was last updated |

### Authorization
- Only the rider or driver of the ride can view ride details
- Returns 403 Forbidden if the authenticated user is neither the rider nor the driver

### Use Cases
- Rider views details of their requested/completed ride
- Driver views details of their accepted/completed ride
- View ride status and progress
- Access pickup and dropoff locations
- View fare information and ride history
- Check vehicle amenities and preferences
- Access ride timestamps for tracking

### Notes
- Null values are returned for fields that haven't been set yet (e.g., actual_fare before ride completion)
- Coordinates are in WGS84 format (latitude: -90 to 90, longitude: -180 to 180)
- Timestamps are in ISO 8601 format with UTC timezone
- Preferences field contains flexible JSON data based on ride type
- For offered rides, rider_id may be null until a passenger books

---

## Cancel Ride Endpoint

### Cancel Ride
**POST** `/api/v1/rides/{ride_id}/cancel`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Path Parameters:**
- `ride_id` (required): integer - The ID of the ride to cancel

**Request Body:**
```json
{
  "reason": "Driver taking too long"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Ride cancelled successfully",
  "ride": {
    "id": 1,
    "rider_id": 2,
    "driver_id": null,
    "pickup_location": "Downtown Station",
    "pickup_lat": 12.9716,
    "pickup_lng": 77.5946,
    "dropoff_location": "Airport",
    "dropoff_lat": 13.1939,
    "dropoff_lng": 77.7068,
    "estimated_distance_km": 25.5,
    "estimated_duration_minutes": 45,
    "estimated_fare": 500.00,
    "actual_distance_km": null,
    "actual_duration_minutes": null,
    "actual_fare": null,
    "toll_amount": null,
    "status": "cancelled",
    "cancellation_reason": "Driver taking too long",
    "requested_at": "2024-01-01T10:00:00Z",
    "accepted_at": null,
    "arrived_at": null,
    "started_at": null,
    "completed_at": null,
    "cancelled_at": "2024-01-01T10:05:00Z",
    "available_seats": null,
    "price_per_seat": null,
    "description": null,
    "preferences": null,
    "ac_available": false,
    "wifi_available": false,
    "music_preference": null,
    "smoking_allowed": false,
    "created_at": "2024-01-01T10:00:00Z",
    "updated_at": "2024-01-01T10:05:00Z"
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

**Error (409) - Invalid State Transition:**
```json
{
  "success": false,
  "error": "Cannot cancel ride",
  "message": "Cannot cancel ride in current status."
}
```

**Error (422) - Validation Error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "reason": ["The reason field is required"]
  }
}
```

**Error (400) - Server Error:**
```json
{
  "success": false,
  "error": "Failed to cancel ride",
  "message": "Error details"
}
```

### Request Fields

| Field | Type | Required | Constraints | Description |
|-------|------|----------|-------------|-------------|
| reason | string | Yes | Max 500 characters | Reason for cancellation |

### Response Fields
Same as Get Ride Details endpoint, with status updated to "cancelled" and cancellation_reason populated.

### Cancellation Rules
- **Allowed Statuses**: Can only cancel rides with status "requested" or "accepted"
- **Blocked Statuses**: Cannot cancel rides with status "started", "completed", or "cancelled"
- **Timestamp**: Sets `cancelled_at` to current server time
- **Reason**: Stores the cancellation reason for audit and analytics

### Authorization
- Any authenticated user can cancel a ride (rider or driver)
- The cancellation is recorded with the authenticated user's ID in logs

### Use Cases
- Rider cancels a ride request before driver accepts
- Driver cancels an accepted ride before starting
- Record cancellation reason for analytics
- Prevent cancellation of in-progress or completed rides

### Notes
- Cancellation is atomic - either fully succeeds or fully fails
- Cancelled rides cannot be reactivated
- Cancellation reason is stored for audit trail
- Timestamps are in ISO 8601 format with UTC timezone
- Maximum reason length is 500 characters to prevent abuse

---

## User Profile Endpoints

### Get User Profile
**GET** `/api/v1/user/profile`

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
    "name": "John Doe",
    "display_name": "Johnny",
    "email": "john@example.com",
    "phone": "+1234567890",
    "role": "driver",
    "gender": "male",
    "date_of_birth": "1990-01-01",
    "bio": "Experienced driver with 5 years experience",
    "profile_photo_url": "profile-photos/john.jpg",
    "user_preference": "driver",
    "onboarding_completed": true,
    "profile_completed": true,
    "profile_visibility": "public",
    "show_phone": true,
    "show_email": false,
    "allow_messages": true,
    "language": "english",
    "theme": "dark",
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

### Get User Profile Details
- **Purpose**: Retrieve complete user profile information
- **Authentication**: Required (Sanctum token)
- **Authorization**: Users can only view their own profile
- **Response Fields**: All user profile fields including preferences and privacy settings

---

### Update User Profile
**POST** `/api/v1/user/profile`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "display_name": "Johnny",
  "gender": "male",
  "date_of_birth": "1990-01-01",
  "bio": "Experienced driver with 5 years experience",
  "user_preference": "driver",
  "languages_spoken": ["english", "hindi"],
  "emergency_contact": "9876543210",
  "insurance_provider": "HDFC Insurance",
  "insurance_policy_number": "POL123456789"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "profile": {
    "id": 1,
    "name": "John Doe",
    "display_name": "Johnny",
    "email": "john@example.com",
    "phone": "+1234567890",
    "role": "driver",
    "gender": "male",
    "date_of_birth": "1990-01-01",
    "bio": "Experienced driver with 5 years experience",
    "profile_photo_url": "profile-photos/john.jpg",
    "user_preference": "driver",
    "onboarding_completed": true,
    "profile_completed": true,
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

**Error (422) - Validation Error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "date_of_birth": ["You must be at least 18 years old"],
    "bio": ["The bio must not exceed 500 characters"]
  }
}
```

### Update User Profile Details
- **Purpose**: Update user profile information
- **Authentication**: Required (Sanctum token)
- **Partial Updates**: All fields are optional - only provided fields are updated
- **Validation**:
  - `display_name`: Max 255 characters
  - `bio`: Max 500 characters
  - `date_of_birth`: Must be 18+ years old
  - `gender`: Must be one of: male, female, other
  - `user_preference`: Must be one of: driver, passenger, both
  - `languages_spoken`: JSON array of language codes
  - `emergency_contact`: Valid phone number format
  - `insurance_provider`: Max 255 characters
  - `insurance_policy_number`: Max 255 characters

---

### Upload Profile Photo
**POST** `/api/v1/user/profile/photo`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: multipart/form-data
```

**Request Body (multipart/form-data):**
```
profile_photo: [image file - JPG, PNG, max 10MB]
```

**Response (200):**
```json
{
  "success": true,
  "message": "Profile photo uploaded successfully",
  "profile_photo_url": "profile-photos/550e8400-e29b-41d4-a716-446655440000.jpg"
}
```

**Error (401) - Unauthorized:**
```json
{
  "success": false,
  "error": "User not authenticated"
}
```

**Error (422) - Validation Error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "profile_photo": ["The profile photo must be a file of type: jpeg, png"]
  }
}
```

**Error (422) - File Size Error:**
```json
{
  "success": false,
  "error": "FILE_UPLOAD_FAILED",
  "message": "File size exceeds 10MB limit"
}
```

### Upload Profile Photo Details
- **Purpose**: Upload or update user profile photo
- **Authentication**: Required (Sanctum token)
- **Supported Formats**: JPG, PNG
- **Max Size**: 10MB
- **Storage**: Private disk with UUID-based filenames
- **Response**: Returns signed URL for accessing the photo

---

### Complete Onboarding
**POST** `/api/v1/user/complete-onboarding`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "user_preference": "driver"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Onboarding completed successfully",
  "profile": {
    "id": 1,
    "name": "John Doe",
    "onboarding_completed": true,
    "profile_completed": true,
    "user_preference": "driver"
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

**Error (422) - Validation Error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "user_preference": ["The user_preference field must be one of: driver, passenger, both"]
  }
}
```

### Complete Onboarding Details
- **Purpose**: Mark onboarding as complete and set user preference
- **Authentication**: Required (Sanctum token)
- **Sets**: `onboarding_completed` to true and `profile_completed` to true
- **User Preference**: Sets the user's role preference (driver, passenger, or both)

---

## User Privacy Settings Endpoints

### Get Privacy Settings
**GET** `/api/v1/user/privacy`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Response (200):**
```json
{
  "success": true,
  "privacy": {
    "profile_visibility": "public",
    "show_phone": true,
    "show_email": false,
    "allow_messages": true
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

### Privacy Settings Details
- **Purpose**: Retrieve current privacy settings for the authenticated user
- **Authentication**: Required (Sanctum token)
- **Fields**:
  - `profile_visibility`: Controls who can see the profile (public, private, friends_only)
  - `show_phone`: Whether phone number is visible to others
  - `show_email`: Whether email is visible to others
  - `allow_messages`: Whether other users can send messages

---

### Update Privacy Settings
**POST** `/api/v1/user/privacy`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "profile_visibility": "private",
  "show_phone": false,
  "show_email": true,
  "allow_messages": false
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Privacy settings updated successfully",
  "privacy": {
    "profile_visibility": "private",
    "show_phone": false,
    "show_email": true,
    "allow_messages": false
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

**Error (422) - Validation Error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "profile_visibility": ["The profile visibility must be one of: public, private, friends_only"]
  }
}
```

### Update Privacy Settings Details
- **Purpose**: Update privacy settings for the authenticated user
- **Authentication**: Required (Sanctum token)
- **Partial Updates**: All fields are optional - only provided fields are updated
- **Validation**:
  - `profile_visibility`: Must be one of `public`, `private`, `friends_only`
  - `show_phone`: Must be boolean
  - `show_email`: Must be boolean
  - `allow_messages`: Must be boolean
- **Behavior**: Null values are ignored, existing values are preserved

### Privacy Settings Options

| Setting | Values | Default | Description |
|---------|--------|---------|-------------|
| profile_visibility | public, private, friends_only | public | Controls profile visibility |
| show_phone | true, false | true | Show phone number to others |
| show_email | true, false | false | Show email to others |
| allow_messages | true, false | true | Allow other users to send messages |

### Use Cases
- User wants to make profile private
- User wants to hide phone number from other users
- User wants to disable message notifications
- User wants to control who can see their contact information

### Notes
- Privacy settings are user-specific
- Changes take effect immediately
- All fields are optional in update requests
- Default values are applied on user creation

## User Preferences Endpoints

### Get User Preferences
**GET** `/api/v1/user/preferences`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Response (200):**
```json
{
  "success": true,
  "preferences": {
    "user_preference": "driver",
    "language": "english",
    "theme": "dark"
  }
}
```

**Error (401):**
```json
{
  "success": false,
  "error": "User not authenticated"
}
```

### Get User Preferences Details
- **Purpose**: Retrieve current user preferences (role preference, language, theme)
- **Authentication**: Required (Sanctum token)
- **Fields**:
  - `user_preference`: User's role preference (driver, passenger, both)
  - `language`: Preferred language (english, hindi, regional)
  - `theme`: Preferred UI theme (light, dark, auto)

---

### Update User Preferences
**POST** `/api/v1/user/preferences`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "user_preference": "passenger",
  "language": "hindi",
  "theme": "light"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Preferences updated successfully",
  "preferences": {
    "user_preference": "passenger",
    "language": "hindi",
    "theme": "light"
  }
}
```

**Error (422):**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "user_preference": ["The user_preference field must be one of: driver, passenger, both."]
  }
}
```

### Update User Preferences Details
- **Purpose**: Update user preferences for the authenticated user
- **Authentication**: Required (Sanctum token)
- **Partial Updates**: All fields are optional - only provided fields are updated
- **Validation**:
  - `user_preference`: Must be one of: driver, passenger, both
  - `language`: Must be one of: english, hindi, regional
  - `theme`: Must be one of: light, dark, auto
- **Behavior**: Null values are ignored, existing values are preserved

### User Preferences Options

| Preference | Values | Default | Description |
|-----------|--------|---------|-------------|
| user_preference | driver, passenger, both | passenger | User's role preference in the app |
| language | english, hindi, regional | english | Preferred language for the app |
| theme | light, dark, auto | auto | Preferred UI theme (auto follows system) |

### Use Cases
- User wants to switch from passenger to driver mode
- User wants to change app language
- User wants to switch between light and dark theme
- User wants to set theme to auto (follows system preference)

### Notes
- Preferences are user-specific
- Changes take effect immediately
- All fields are optional in update requests
- Default values are applied on user creation
- Theme "auto" will follow the device's system theme preference

---

## Driver Verification Endpoints

### Create Driver Verification
**POST** `/api/v1/driver/verification`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "dl_number": "DL0120170123456",
  "dl_expiry_date": "2025-12-31",
  "rc_number": "KA01AB1234"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Driver verification initiated successfully",
  "verification": {
    "id": 1,
    "user_id": 1,
    "dl_number": "DL0120170123456",
    "dl_expiry_date": "2025-12-31",
    "rc_number": "KA01AB1234",
    "verification_status": "pending",
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

**Error (422) - Validation Error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "dl_number": ["The dl number field is required"],
    "dl_expiry_date": ["The dl expiry date must be a future date"]
  }
}
```

### Create Driver Verification Details
- **Purpose**: Initiate driver verification process
- **Authentication**: Required (Sanctum token)
- **Required Fields**: `dl_number`, `dl_expiry_date`, `rc_number`
- **Initial Status**: Set to "pending"
- **Validation**:
  - `dl_number`: Valid driving license format
  - `dl_expiry_date`: Must be a future date
  - `rc_number`: Valid registration certificate format

---

### Get Verification Status
**GET** `/api/v1/driver/verification/status`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Response (200):**
```json
{
  "success": true,
  "verification": {
    "id": 1,
    "user_id": 1,
    "dl_number": "DL0120170123456",
    "dl_expiry_date": "2025-12-31",
    "dl_front_image": "verifications/dl_front_550e8400.jpg",
    "dl_back_image": "verifications/dl_back_550e8400.jpg",
    "rc_number": "KA01AB1234",
    "rc_front_image": "verifications/rc_front_550e8400.jpg",
    "rc_back_image": "verifications/rc_back_550e8400.jpg",
    "verification_status": "pending",
    "rejection_reason": null,
    "verified_at": null,
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

**Error (404) - Not Found:**
```json
{
  "success": false,
  "error": "Verification not found"
}
```

### Get Verification Status Details
- **Purpose**: Retrieve current verification status
- **Authentication**: Required (Sanctum token)
- **Status Values**: pending, approved, rejected
- **Rejection Reason**: Only populated if status is "rejected"

---

### Upload Verification Documents
**POST** `/api/v1/driver/verification/documents`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: multipart/form-data
```

**Request Body (multipart/form-data):**
```
dl_front: [image file - JPG, PNG, max 10MB]
dl_back: [image file - JPG, PNG, max 10MB]
rc_front: [image file - JPG, PNG, max 10MB]
rc_back: [image file - JPG, PNG, max 10MB]
```

**Response (200):**
```json
{
  "success": true,
  "message": "Documents uploaded successfully",
  "verification": {
    "id": 1,
    "user_id": 1,
    "dl_front_image": "verifications/dl_front_550e8400.jpg",
    "dl_back_image": "verifications/dl_back_550e8400.jpg",
    "rc_front_image": "verifications/rc_front_550e8400.jpg",
    "rc_back_image": "verifications/rc_back_550e8400.jpg",
    "verification_status": "pending",
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

**Error (422) - Validation Error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "dl_front": ["The dl front must be a file of type: jpeg, png"],
    "dl_back": ["The dl back field is required"]
  }
}
```

### Upload Verification Documents Details
- **Purpose**: Upload driving license and registration certificate documents
- **Authentication**: Required (Sanctum token)
- **Supported Formats**: JPG, PNG
- **Max Size**: 10MB per file
- **Required Files**: All four files (dl_front, dl_back, rc_front, rc_back)
- **Storage**: Private disk with UUID-based filenames

---

### Get Verification Documents
**GET** `/api/v1/driver/verification/documents`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Response (200):**
```json
{
  "success": true,
  "documents": {
    "dl_front": "verifications/dl_front_550e8400.jpg",
    "dl_back": "verifications/dl_back_550e8400.jpg",
    "rc_front": "verifications/rc_front_550e8400.jpg",
    "rc_back": "verifications/rc_back_550e8400.jpg"
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

**Error (404) - Not Found:**
```json
{
  "success": false,
  "error": "Documents not found"
}
```

### Get Verification Documents Details
- **Purpose**: Retrieve uploaded verification documents
- **Authentication**: Required (Sanctum token)
- **Returns**: File paths for all uploaded documents
- **Access**: Via signed URLs through FileUploadService

---

### Submit Verification
**POST** `/api/v1/driver/verification/submit`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Response (200):**
```json
{
  "success": true,
  "message": "Verification submitted successfully",
  "verification": {
    "id": 1,
    "user_id": 1,
    "verification_status": "pending",
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

**Error (409) - Incomplete Verification:**
```json
{
  "success": false,
  "error": "Verification incomplete",
  "message": "All required documents must be uploaded before submission"
}
```

### Submit Verification Details
- **Purpose**: Submit verification for admin review
- **Authentication**: Required (Sanctum token)
- **Validation**: All documents must be uploaded
- **Status Change**: Remains "pending" until admin reviews

---

### Get KYC Status
**GET** `/api/v1/driver/kyc-status`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Response (200):**
```json
{
  "success": true,
  "kyc_status": {
    "verification_status": "approved",
    "verified_at": "2024-01-05T10:30:00Z",
    "is_kyc_complete": true,
    "can_drive": true
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

**Error (404) - Not Found:**
```json
{
  "success": false,
  "error": "KYC verification not found"
}
```

### Get KYC Status Details
- **Purpose**: Check KYC verification status and eligibility to drive
- **Authentication**: Required (Sanctum token)
- **Status Values**: pending, approved, rejected
- **can_drive**: Boolean indicating if user is verified to drive
- **verified_at**: Timestamp when verification was approved (null if not approved)


## Saved Routes Endpoints

### Create Saved Route
**POST** `/api/v1/saved-routes`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "from_location": "Downtown Station",
  "to_location": "Airport Terminal 1",
  "is_pinned": false
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Route saved successfully",
  "route": {
    "id": 1,
    "user_id": 1,
    "from_location": "Downtown Station",
    "to_location": "Airport Terminal 1",
    "is_pinned": false,
    "last_used_at": null,
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

**Error (422) - Validation Error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "from_location": ["The from location field is required"],
    "to_location": ["The to location field is required"]
  }
}
```

### Create Saved Route Details
- **Purpose**: Save a frequently used route
- **Authentication**: Required (Sanctum token)
- **Required Fields**: `from_location`, `to_location`
- **Optional Fields**: `is_pinned` (default: false)
- **Validation**:
  - `from_location`: Max 255 characters
  - `to_location`: Max 255 characters
  - `is_pinned`: Must be boolean

---

### Get Saved Routes
**GET** `/api/v1/saved-routes`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Query Parameters:**
- `page` (optional): Pagination page number (default: 1)
- `per_page` (optional): Items per page (default: 15)
- `sort_by` (optional): Sort field (created_at, last_used_at, is_pinned) (default: is_pinned)

**Response (200):**
```json
{
  "success": true,
  "message": "Saved routes retrieved successfully",
  "routes": [
    {
      "id": 1,
      "user_id": 1,
      "from_location": "Downtown Station",
      "to_location": "Airport Terminal 1",
      "is_pinned": true,
      "last_used_at": "2024-01-01T10:30:00Z",
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    },
    {
      "id": 2,
      "user_id": 1,
      "from_location": "Home",
      "to_location": "Office",
      "is_pinned": false,
      "last_used_at": "2024-01-01T08:00:00Z",
      "created_at": "2024-01-01T00:05:00Z",
      "updated_at": "2024-01-01T00:05:00Z"
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

**Error (401) - Unauthorized:**
```json
{
  "success": false,
  "error": "User not authenticated"
}
```

### Get Saved Routes Details
- **Purpose**: Retrieve all saved routes for the authenticated user
- **Authentication**: Required (Sanctum token)
- **Ordering**: Pinned routes first, then by last_used_at (most recent first)
- **Pagination**: Supports page and per_page parameters
- **Filtering**: Only returns routes for the authenticated user

---

### Pin Route
**POST** `/api/v1/saved-routes/{id}/pin`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Response (200):**
```json
{
  "success": true,
  "message": "Route pinned successfully",
  "route": {
    "id": 1,
    "user_id": 1,
    "from_location": "Downtown Station",
    "to_location": "Airport Terminal 1",
    "is_pinned": true,
    "last_used_at": "2024-01-01T10:30:00Z",
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

**Error (403) - Forbidden:**
```json
{
  "success": false,
  "error": "Unauthorized",
  "message": "You do not have permission to pin this route"
}
```

**Error (404) - Not Found:**
```json
{
  "success": false,
  "error": "Route not found"
}
```

### Pin Route Details
- **Purpose**: Pin a route to favorites for quick access
- **Authentication**: Required (Sanctum token)
- **Authorization**: Only the owner of the route can pin it
- **Behavior**: Sets `is_pinned` to true
- **Ordering**: Pinned routes appear first in the list

---

### Update Saved Route
**PUT** `/api/v1/saved-routes/{id}`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "from_location": "New Downtown Station",
  "to_location": "Airport Terminal 2",
  "is_pinned": true
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Route updated successfully",
  "route": {
    "id": 1,
    "user_id": 1,
    "from_location": "New Downtown Station",
    "to_location": "Airport Terminal 2",
    "is_pinned": true,
    "last_used_at": "2024-01-01T10:30:00Z",
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:05:00Z"
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
  "message": "You do not have permission to update this route"
}
```

**Error (404) - Not Found:**
```json
{
  "success": false,
  "error": "Route not found"
}
```

**Error (422) - Validation Error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "from_location": ["The from location must not exceed 255 characters"]
  }
}
```

### Update Saved Route Details
- **Purpose**: Update an existing saved route
- **Authentication**: Required (Sanctum token)
- **Authorization**: Only the owner of the route can update it
- **Partial Updates**: All fields are optional - only provided fields are updated
- **Validation**:
  - `from_location`: Max 255 characters
  - `to_location`: Max 255 characters
  - `is_pinned`: Must be boolean

---

### Delete Saved Route
**DELETE** `/api/v1/saved-routes/{id}`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Response (200):**
```json
{
  "success": true,
  "message": "Route deleted successfully"
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
  "message": "You do not have permission to delete this route"
}
```

**Error (404) - Not Found:**
```json
{
  "success": false,
  "error": "Route not found"
}
```

### Delete Saved Route Details
- **Purpose**: Delete a saved route
- **Authentication**: Required (Sanctum token)
- **Authorization**: Only the owner of the route can delete it
- **Behavior**: Permanently removes the route from the database
- **Cascade**: No dependent records are affected

---

## Ride Offering Endpoints

### Create Ride Offering
**POST** `/api/v1/rides/offer`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "pickup_location": "Downtown Station",
  "pickup_lat": 12.9716,
  "pickup_lng": 77.5946,
  "dropoff_location": "Airport",
  "dropoff_lat": 13.1939,
  "dropoff_lng": 77.7068,
  "estimated_distance_km": 25.5,
  "estimated_duration_minutes": 45,
  "estimated_fare": 500.00,
  "available_seats": 3,
  "price_per_seat": 250.00,
  "description": "Comfortable sedan with AC",
  "preferences": {
    "music_genre": "classical",
    "temperature": 22
  },
  "ac_available": true,
  "wifi_available": false,
  "music_preference": "Bollywood",
  "smoking_allowed": false
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Ride offered successfully",
  "ride": {
    "id": 1,
    "driver_id": 1,
    "pickup_location": "Downtown Station",
    "pickup_lat": 12.9716,
    "pickup_lng": 77.5946,
    "dropoff_location": "Airport",
    "dropoff_lat": 13.1939,
    "dropoff_lng": 77.7068,
    "estimated_distance_km": 25.5,
    "estimated_duration_minutes": 45,
    "estimated_fare": 500.00,
    "available_seats": 3,
    "price_per_seat": 250.00,
    "description": "Comfortable sedan with AC",
    "preferences": {
      "music_genre": "classical",
      "temperature": 22
    },
    "ac_available": true,
    "wifi_available": false,
    "music_preference": "Bollywood",
    "smoking_allowed": false,
    "status": "offered",
    "requested_at": "2024-01-01T10:00:00Z",
    "created_at": "2024-01-01T10:00:00Z"
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

**Error (422) - Validation Error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "available_seats": ["The available seats must be between 1 and 8"],
    "price_per_seat": ["The price per seat must be greater than 0"],
    "pickup_lat": ["The pickup lat must be between -90 and 90"]
  }
}
```

### Create Ride Offering Details
- **Purpose**: Driver offers a ride to passengers
- **Authentication**: Required (Sanctum token)
- **Required Fields**: pickup/dropoff locations and coordinates, available_seats, price_per_seat
- **Status**: Set to "offered"
- **Validation**:
  - `available_seats`: 1-8
  - `price_per_seat`: > 0, max 10000
  - `pickup_lat`/`dropoff_lat`: -90 to 90
  - `pickup_lng`/`dropoff_lng`: -180 to 180
  - `estimated_distance_km`: > 0
  - `estimated_duration_minutes`: > 0

---

### Update Ride Status
**POST** `/api/v1/rides/{id}/update-status`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "status": "started"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Ride status updated successfully",
  "ride": {
    "id": 1,
    "driver_id": 1,
    "status": "started",
    "started_at": "2024-01-01T10:50:00Z",
    "updated_at": "2024-01-01T10:50:00Z"
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

**Error (409) - Invalid State Transition:**
```json
{
  "success": false,
  "error": "Invalid status transition",
  "message": "Cannot transition from current status to requested status"
}
```

**Error (422) - Validation Error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "status": ["The status must be one of: accepted, arrived, started, completed"]
  }
}
```

### Update Ride Status Details
- **Purpose**: Update ride status through its lifecycle
- **Authentication**: Required (Sanctum token)
- **Valid Statuses**: accepted, arrived, started, completed
- **Status Transitions**:
  - offered → accepted
  - accepted → arrived
  - arrived → started
  - started → completed
- **Timestamps**: Automatically sets corresponding timestamp fields

---

## Ride Request Endpoints

### Request Ride
**POST** `/api/v1/rides`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "pickup_location": "Downtown Station",
  "pickup_lat": 12.9716,
  "pickup_lng": 77.5946,
  "dropoff_location": "Airport",
  "dropoff_lat": 13.1939,
  "dropoff_lng": 77.7068,
  "estimated_distance_km": 25.5,
  "estimated_duration_minutes": 45,
  "estimated_fare": 500.00
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
    "pickup_location": "Downtown Station",
    "pickup_lat": 12.9716,
    "pickup_lng": 77.5946,
    "dropoff_location": "Airport",
    "dropoff_lat": 13.1939,
    "dropoff_lng": 77.7068,
    "estimated_distance_km": 25.5,
    "estimated_duration_minutes": 45,
    "estimated_fare": 500.00,
    "status": "requested",
    "requested_at": "2024-01-01T10:00:00Z",
    "created_at": "2024-01-01T10:00:00Z"
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

**Error (422) - Validation Error:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "pickup_lat": ["The pickup lat must be between -90 and 90"],
    "estimated_fare": ["The estimated fare must be greater than 0"]
  }
}
```

### Request Ride Details
- **Purpose**: Passenger requests a ride
- **Authentication**: Required (Sanctum token)
- **Status**: Set to "requested"
- **Validation**:
  - Coordinates must be valid (lat: -90 to 90, lng: -180 to 180)
  - Estimated fare must be > 0
  - Locations must not be empty

---

### Search Rides
**GET** `/api/v1/rides`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Query Parameters:**
- `status` (optional): Filter by ride status
- `page` (optional): Pagination page number (default: 1)
- `per_page` (optional): Items per page (default: 15)

**Response (200):**
```json
{
  "success": true,
  "message": "Rides retrieved successfully",
  "rides": [
    {
      "id": 1,
      "rider_id": 1,
      "driver_id": null,
      "pickup_location": "Downtown Station",
      "status": "requested",
      "requested_at": "2024-01-01T10:00:00Z",
      "created_at": "2024-01-01T10:00:00Z"
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

**Error (401) - Unauthorized:**
```json
{
  "success": false,
  "error": "User not authenticated"
}
```

### Search Rides Details
- **Purpose**: Retrieve rides for the authenticated user
- **Authentication**: Required (Sanctum token)
- **Filtering**: Can filter by status (requested, accepted, completed, cancelled, etc.)
- **Pagination**: Supports page and per_page parameters

---

### Accept Ride
**POST** `/api/v1/rides/{id}/accept`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Response (200):**
```json
{
  "success": true,
  "message": "Ride accepted successfully",
  "ride": {
    "id": 1,
    "driver_id": 1,
    "status": "accepted",
    "accepted_at": "2024-01-01T10:05:00Z",
    "updated_at": "2024-01-01T10:05:00Z"
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

**Error (409) - Ride Not Available:**
```json
{
  "success": false,
  "error": "Ride not available",
  "message": "This ride has already been accepted"
}
```

### Accept Ride Details
- **Purpose**: Driver accepts a ride request
- **Authentication**: Required (Sanctum token)
- **Status Change**: requested → accepted
- **Timestamp**: Sets `accepted_at` to current time

---

### Arrive at Pickup
**POST** `/api/v1/rides/{id}/arrive`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Response (200):**
```json
{
  "success": true,
  "message": "Arrival confirmed",
  "ride": {
    "id": 1,
    "status": "arrived",
    "arrived_at": "2024-01-01T10:45:00Z",
    "updated_at": "2024-01-01T10:45:00Z"
  }
}
```

### Arrive at Pickup Details
- **Purpose**: Driver confirms arrival at pickup location
- **Status Change**: accepted → arrived
- **Timestamp**: Sets `arrived_at` to current time

---

### Start Ride
**POST** `/api/v1/rides/{id}/start`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Response (200):**
```json
{
  "success": true,
  "message": "Ride started",
  "ride": {
    "id": 1,
    "status": "started",
    "started_at": "2024-01-01T10:50:00Z",
    "updated_at": "2024-01-01T10:50:00Z"
  }
}
```

### Start Ride Details
- **Purpose**: Driver starts the ride
- **Status Change**: arrived → started
- **Timestamp**: Sets `started_at` to current time

---

### Complete Ride
**POST** `/api/v1/rides/{id}/complete`

**Headers:**
```
Authorization: Bearer {sanctum_api_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "actual_distance_km": 26.0,
  "actual_duration_minutes": 48,
  "actual_fare": 520.00,
  "toll_amount": 50.00
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
    "actual_distance_km": 26.0,
    "actual_duration_minutes": 48,
    "actual_fare": 520.00,
    "toll_amount": 50.00,
    "completed_at": "2024-01-01T11:38:00Z",
    "updated_at": "2024-01-01T11:38:00Z"
  }
}
```

### Complete Ride Details
- **Purpose**: Driver completes the ride
- **Status Change**: started → completed
- **Timestamp**: Sets `completed_at` to current time
- **Fare Calculation**: Records actual distance, duration, and fare Driver Profile Fields Details
- **Purpose**: Store and manage driver-specific information
- **Authentication**: Required (Sanctum token)
- **Availability**: Only for users with role "driver"
- **Partial Updates**: All fields are optional - only provided fields are updated
- **Validation**:
  - `languages_spoken`: Must be an array of strings
  - `emergency_contact`: Must be a valid phone number (max 20 characters)
  - `insurance_provider`: Must be a string (max 255 characters)
  - `insurance_policy_number`: Must be a string (max 255 characters)
- **Behavior**: Null values are ignored, existing values are preserved

### Use Cases
- Driver wants to add languages they speak
- Driver wants to update emergency contact information
- Driver wants to update insurance details
- Driver wants to manage their professional information

### Notes
- Driver profile fields are only available for users with role "driver"
- If a driver profile doesn't exist, it will be created automatically
- All fields are optional and can be updated independently
- Changes take effect immediately
- Driver profile data is included in the profile response for drivers


---

## API Endpoints Summary

### Complete Endpoint List (40+ endpoints)

#### Authentication (4 endpoints)
1. POST `/api/v1/auth/login` - Login with Firebase token
2. GET `/api/v1/auth/me` - Get current user profile
3. POST `/api/v1/auth/logout` - Logout
4. DELETE `/api/v1/auth/delete-account` - Delete account

#### User Profile (8 endpoints)
5. GET `/api/v1/user/profile` - Get user profile
6. POST `/api/v1/user/profile` - Update user profile
7. POST `/api/v1/user/profile/photo` - Upload profile photo
8. POST `/api/v1/user/complete-onboarding` - Complete onboarding
9. GET `/api/v1/user/preferences` - Get user preferences
10. POST `/api/v1/user/preferences` - Update user preferences
11. GET `/api/v1/user/privacy` - Get privacy settings
12. POST `/api/v1/user/privacy` - Update privacy settings

#### Driver Verification (6 endpoints)
13. POST `/api/v1/driver/verification` - Create driver verification
14. GET `/api/v1/driver/verification/status` - Get verification status
15. POST `/api/v1/driver/verification/documents` - Upload verification documents
16. GET `/api/v1/driver/verification/documents` - Get verification documents
17. POST `/api/v1/driver/verification/submit` - Submit verification
18. GET `/api/v1/driver/kyc-status` - Get KYC status

#### Vehicles (6 endpoints)
19. POST `/api/v1/vehicles` - Create vehicle
20. GET `/api/v1/vehicles` - List vehicles
21. GET `/api/v1/vehicles/{id}` - Get vehicle details
22. PUT `/api/v1/vehicles/{id}` - Update vehicle
23. DELETE `/api/v1/vehicles/{id}` - Delete vehicle
24. POST `/api/v1/vehicles/{id}/set-default` - Set default vehicle

#### Rides (12 endpoints)
25. POST `/api/v1/rides` - Request ride
26. GET `/api/v1/rides` - Search rides
27. GET `/api/v1/rides/{id}` - Get ride details
28. POST `/api/v1/rides/{id}/accept` - Accept ride
29. POST `/api/v1/rides/{id}/arrive` - Arrive at pickup
30. POST `/api/v1/rides/{id}/start` - Start ride
31. POST `/api/v1/rides/{id}/complete` - Complete ride
32. POST `/api/v1/rides/{id}/cancel` - Cancel ride
33. POST `/api/v1/rides/offer` - Offer ride (driver)
34. GET `/api/v1/rides/available` - Search available rides
35. POST `/api/v1/rides/{id}/update-status` - Update ride status
36. GET `/api/v1/rides/{id}/history` - Get ride history

#### Bookings (6 endpoints)
37. POST `/api/v1/bookings` - Create booking
38. GET `/api/v1/bookings` - List bookings
39. GET `/api/v1/bookings/{id}` - Get booking details
40. POST `/api/v1/bookings/{id}/cancel` - Cancel booking
41. GET `/api/v1/bookings/history` - Get booking history
42. GET `/api/v1/bookings/{id}/details` - Get booking details

#### Reviews (4 endpoints)
43. POST `/api/v1/reviews` - Create review
44. GET `/api/v1/reviews/{id}` - Get review
45. GET `/api/v1/reviews/user/{user_id}` - Get reviews for user
46. GET `/api/v1/reviews/ride/{ride_id}` - Get reviews for ride

#### Chat (6 endpoints)
47. POST `/api/v1/chats` - Create chat
48. GET `/api/v1/chats` - List chats
49. POST `/api/v1/chats/{id}/messages` - Send message
50. GET `/api/v1/chats/{id}/messages` - Get messages
51. POST `/api/v1/chats/{id}/mark-read` - Mark messages as read
52. DELETE `/api/v1/chats/{id}` - Delete chat

#### Saved Routes (5 endpoints)
53. POST `/api/v1/saved-routes` - Create saved route
54. GET `/api/v1/saved-routes` - Get saved routes
55. POST `/api/v1/saved-routes/{id}/pin` - Pin route
56. PUT `/api/v1/saved-routes/{id}` - Update saved route
57. DELETE `/api/v1/saved-routes/{id}` - Delete saved route

#### Notifications (4 endpoints)
58. POST `/api/v1/notifications/fcm-token` - Register FCM token
59. GET `/api/v1/notifications/preferences` - Get notification preferences
60. POST `/api/v1/notifications/preferences` - Update notification preferences
61. GET `/api/v1/notifications` - Get all notifications

#### Location (3 endpoints)
62. POST `/api/v1/locations/update` - Update location
63. GET `/api/v1/locations/history/{ride_id}` - Get location history
64. GET `/api/v1/locations/current/{ride_id}` - Get current location

#### Payment (5 endpoints)
65. POST `/api/v1/payment-methods` - Add payment method
66. GET `/api/v1/payment-methods` - Get payment methods
67. PUT `/api/v1/payment-methods/{id}` - Update payment method
68. DELETE `/api/v1/payment-methods/{id}` - Delete payment method
69. POST `/api/v1/payment-methods/{id}/set-default` - Set default payment method

---

## Common Response Patterns

### Success Response Format
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

### Error Response Format
```json
{
  "success": false,
  "error": "Error code",
  "message": "Error description",
  "errors": {
    // Validation errors (if applicable)
  },
  "timestamp": "2024-01-01T00:00:00Z"
}
```

### HTTP Status Codes
- **200 OK**: Successful GET, PUT, POST request
- **201 Created**: Successful resource creation
- **400 Bad Request**: Invalid request format or parameters
- **401 Unauthorized**: Missing or invalid authentication token
- **403 Forbidden**: Authenticated but not authorized for this resource
- **404 Not Found**: Resource does not exist
- **409 Conflict**: Race condition or duplicate resource
- **422 Unprocessable Entity**: Validation error
- **429 Too Many Requests**: Rate limit exceeded
- **500 Internal Server Error**: Server error
- **503 Service Unavailable**: Service temporarily unavailable

---

## Authentication

### Token Types
1. **Firebase ID Token**: Used for initial login (POST `/api/v1/auth/login`)
2. **Sanctum API Token**: Used for all subsequent requests

### Token Flow
1. Client sends Firebase ID token to `/api/v1/auth/login`
2. Backend verifies Firebase token using Firebase Admin SDK
3. Backend creates/updates user in database
4. Backend generates Sanctum API token
5. Client receives Sanctum token and uses it for all future requests
6. All protected endpoints verify Sanctum token

### Authorization Header
```
Authorization: Bearer {sanctum_api_token}
```

---

## Rate Limiting

### Rate Limit Headers
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640000000
```

### Rate Limit Rules
- **General endpoints**: 60 requests per minute
- **Sensitive endpoints** (auth, payment): 10 requests per minute
- **Location updates**: 100 requests per minute
- **Search endpoints**: 30 requests per minute

### Rate Limit Response (429)
```json
{
  "success": false,
  "error": "Too Many Requests",
  "message": "Rate limit exceeded. Please try again later.",
  "retry_after": 60
}
```

---

## File Upload Guidelines

### Supported File Types
- **Images**: JPG, PNG
- **Documents**: PDF
- **Max Size**: 10MB per file

### Upload Endpoints
- POST `/api/v1/user/profile/photo` - Profile photo
- POST `/api/v1/driver/verification/documents` - Verification documents
- POST `/api/v1/vehicles/{id}/photo` - Vehicle photo
- POST `/api/v1/chats/{id}/messages` - Message attachments

### File Upload Response
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "file_url": "https://example.com/storage/files/uuid.jpg"
}
```

### File Upload Errors
- **413 Payload Too Large**: File exceeds 10MB limit
- **415 Unsupported Media Type**: File type not supported
- **422 Validation Error**: File validation failed

---

## Pagination

### Pagination Parameters
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 15, max: 100)

### Pagination Response
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7,
    "from": 1,
    "to": 15
  }
}
```

---

## Validation Rules

### Common Validations

| Field | Rule | Example |
|-------|------|---------|
| phone_number | 10 digits (India format) | 9876543210 |
| email | Valid email format | user@example.com |
| date_of_birth | 18+ years old | 1990-01-01 |
| latitude | -90 to 90 | 28.6139 |
| longitude | -180 to 180 | 77.2090 |
| rating | 1-5 integer | 4 |
| price_per_seat | > 0, max 10000 | 250.00 |
| seats_booked | 1-8 | 2 |
| file_size | Max 10MB | 5242880 bytes |
| file_types | JPG, PNG, PDF | image/jpeg |

---

## Error Handling

### Common Error Codes

| Code | HTTP Status | Description |
|------|------------|-------------|
| UNAUTHORIZED | 401 | User not authenticated |
| FORBIDDEN | 403 | User not authorized |
| NOT_FOUND | 404 | Resource not found |
| VALIDATION_FAILED | 422 | Input validation failed |
| CONFLICT | 409 | Resource conflict (e.g., duplicate) |
| RATE_LIMIT_EXCEEDED | 429 | Too many requests |
| FILE_UPLOAD_FAILED | 422 | File upload validation failed |
| INVALID_STATE_TRANSITION | 409 | Invalid status transition |
| INTERNAL_SERVER_ERROR | 500 | Server error |

### Error Response Example
```json
{
  "success": false,
  "error": "VALIDATION_FAILED",
  "message": "Input validation failed",
  "errors": {
    "email": ["The email must be a valid email address"],
    "phone": ["The phone must be 10 digits"]
  },
  "timestamp": "2024-01-01T00:00:00Z"
}
```

---

## Security Considerations

### HTTPS Only
- All API endpoints require HTTPS
- HTTP requests are automatically redirected to HTTPS

### CORS Configuration
- Allowed origins: Configured for Flutter app domains
- Allowed methods: GET, POST, PUT, DELETE, OPTIONS
- Allowed headers: Content-Type, Authorization
- Credentials: Allowed

### Input Sanitization
- All user inputs are sanitized to prevent XSS attacks
- HTML tags are stripped from text inputs
- Special characters are escaped

### Payment Data Encryption
- Payment details are encrypted using Laravel's encryption
- Encryption key is stored securely in environment variables
- Encrypted data is stored in the database

### Request Logging
- All API requests are logged for audit purposes
- Sensitive data (passwords, tokens) are not logged
- Logs are retained for 90 days

---

## Testing with cURL

### Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Authorization: Bearer {firebase_token}" \
  -H "Content-Type: application/json"
```

### Get Profile
```bash
curl -X GET http://localhost:8000/api/v1/user/profile \
  -H "Authorization: Bearer {sanctum_token}"
```

### Create Booking
```bash
curl -X POST http://localhost:8000/api/v1/bookings \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "ride_id": 1,
    "seats_booked": 2,
    "passenger_name": "John Doe",
    "passenger_phone": "9876543210"
  }'
```

### Upload File
```bash
curl -X POST http://localhost:8000/api/v1/user/profile/photo \
  -H "Authorization: Bearer {sanctum_token}" \
  -F "profile_photo=@/path/to/photo.jpg"
```

---

## API Versioning

### Current Version
- **Version**: v1
- **Base URL**: `/api/v1`
- **Stability**: Stable

### Backward Compatibility
- All endpoints maintain backward compatibility
- New fields are added as optional
- Deprecated fields are marked in documentation
- Deprecation period: 6 months before removal

---

## Performance Optimization

### Caching
- User profiles: 30 minutes
- Ride searches: 5 minutes
- Vehicle list: 1 hour
- Saved routes: 1 hour
- Cache invalidation on updates

### Query Optimization
- Database indexes on frequently queried fields
- Eager loading of relationships
- Pagination for large result sets
- Query optimization for complex searches

### Response Time Targets
- API response time: < 200ms
- Database query time: < 50ms
- File upload processing: < 5s
- Location update processing: < 1s

---

## Support & Documentation

### API Documentation
- Full endpoint documentation: See above
- Request/response examples: Included with each endpoint
- Error handling guide: See Error Handling section
- Authentication guide: See Authentication section

### Postman Collection
- Import the Postman collection for easy testing
- Pre-configured requests for all endpoints
- Environment variables for base URL and tokens
- Example requests and responses

### Flutter Integration
- See FLUTTER_INTEGRATION_GUIDE.md for Flutter-specific implementation
- SDK examples and best practices
- Common issues and solutions
- Performance optimization tips

---

## Changelog

### Version 1.0 (Current)
- Initial API release
- 40+ endpoints implemented
- Complete user profile system
- Driver verification system
- Vehicle management
- Bookings system
- Reviews and ratings
- Chat and messaging
- Saved routes
- Notifications and FCM
- Location tracking
- Payment methods
- Rate limiting
- Input sanitization
- Payment encryption
- Request logging
- CORS configuration

---

## Contact & Support

For API support and questions:
- Email: api-support@wayloshare.com
- Documentation: https://docs.wayloshare.com
- Status Page: https://status.wayloshare.com
- Issue Tracker: https://github.com/wayloshare/api/issues
