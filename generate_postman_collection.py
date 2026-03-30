#!/usr/bin/env python3
import json

# Base collection structure
collection = {
    "info": {
        "name": "WayloShare Backend API - Flutter Alignment",
        "description": "Complete API testing collection for WayloShare ride-sharing backend with 40+ new endpoints for Flutter app alignment",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [],
    "variable": [
        {"key": "base_url", "value": "http://127.0.0.1:8000", "type": "string"},
        {"key": "firebase_token", "value": "", "type": "string"},
        {"key": "sanctum_token", "value": "", "type": "string"},
        {"key": "user_id", "value": "", "type": "string"},
        {"key": "ride_id", "value": "", "type": "string"},
        {"key": "vehicle_id", "value": "", "type": "string"},
        {"key": "booking_id", "value": "", "type": "string"},
        {"key": "review_id", "value": "", "type": "string"},
        {"key": "chat_id", "value": "", "type": "string"},
        {"key": "saved_route_id", "value": "", "type": "string"},
        {"key": "payment_method_id", "value": "", "type": "string"},
        {"key": "driver_id", "value": "", "type": "string"},
        {"key": "verification_id", "value": "", "type": "string"}
    ]
}

def create_request(method, path, description, body=None, auth=True):
    """Create a request object"""
    headers = [
        {"key": "Accept", "value": "application/json"}
    ]
    
    if auth:
        headers.insert(0, {
            "key": "Authorization",
            "value": "Bearer {{sanctum_token}}",
            "type": "text"
        })
    
    if body or method in ["POST", "PUT"]:
        headers.append({
            "key": "Content-Type",
            "value": "application/json"
        })
    
    request = {
        "method": method,
        "header": headers,
        "url": {
            "raw": f"{{{{base_url}}}}{path}",
            "protocol": "http",
            "host": ["{{base_url}}"],
            "path": path.lstrip("/").split("/")
        },
        "description": description
    }
    
    if body:
        request["body"] = {
            "mode": "raw",
            "raw": json.dumps(body, indent=2)
        }
    
    return request

# 1. Health Check
collection["item"].append({
    "name": "1. Health Check",
    "item": [
        {
            "name": "GET /health",
            "request": create_request("GET", "/api/v1/health", "Check backend health", auth=False),
            "response": []
        }
    ]
})

# 2. Authentication
collection["item"].append({
    "name": "2. Authentication",
    "item": [
        {
            "name": "POST /auth/login",
            "event": [{
                "listen": "test",
                "script": {
                    "exec": [
                        "if (pm.response.code === 200) {",
                        "    var jsonData = pm.response.json();",
                        "    pm.environment.set('sanctum_token', jsonData.token);",
                        "    pm.environment.set('user_id', jsonData.user.id);",
                        "} else {",
                        "    console.log('✗ Login failed');",
                        "}"
                    ]
                }
            }],
            "request": {
                "method": "POST",
                "header": [
                    {"key": "Authorization", "value": "Bearer {{firebase_token}}", "type": "text"},
                    {"key": "Content-Type", "value": "application/json"},
                    {"key": "Accept", "value": "application/json"}
                ],
                "body": {"mode": "raw", "raw": ""},
                "url": {
                    "raw": "{{base_url}}/api/v1/auth/login",
                    "protocol": "http",
                    "host": ["{{base_url}}"],
                    "path": ["api", "v1", "auth", "login"]
                },
                "description": "Login with Firebase token"
            },
            "response": []
        },
        {
            "name": "GET /auth/me",
            "request": create_request("GET", "/api/v1/auth/me", "Get current user profile"),
            "response": []
        },
        {
            "name": "POST /auth/logout",
            "request": create_request("POST", "/api/v1/auth/logout", "Logout and revoke tokens"),
            "response": []
        }
    ]
})

# 3. User Profile (8 endpoints)
collection["item"].append({
    "name": "3. User Profile",
    "item": [
        {
            "name": "GET /user/profile",
            "request": create_request("GET", "/api/v1/user/profile", "Get user profile"),
            "response": []
        },
        {
            "name": "POST /user/profile",
            "request": create_request("POST", "/api/v1/user/profile", "Update user profile", {
                "display_name": "John Doe",
                "date_of_birth": "1990-01-15",
                "gender": "male",
                "bio": "Friendly driver",
                "user_preference": "both"
            }),
            "response": []
        },
        {
            "name": "POST /user/profile/photo",
            "request": {
                "method": "POST",
                "header": [
                    {"key": "Authorization", "value": "Bearer {{sanctum_token}}", "type": "text"}
                ],
                "body": {
                    "mode": "formdata",
                    "formdata": [{"key": "profile_photo", "type": "file", "src": ""}]
                },
                "url": {
                    "raw": "{{base_url}}/api/v1/user/profile/photo",
                    "protocol": "http",
                    "host": ["{{base_url}}"],
                    "path": ["api", "v1", "user", "profile", "photo"]
                },
                "description": "Upload profile photo"
            },
            "response": []
        },
        {
            "name": "POST /user/complete-onboarding",
            "request": create_request("POST", "/api/v1/user/complete-onboarding", "Complete onboarding", {}),
            "response": []
        },
        {
            "name": "GET /user/preferences",
            "request": create_request("GET", "/api/v1/user/preferences", "Get user preferences"),
            "response": []
        },
        {
            "name": "POST /user/preferences",
            "request": create_request("POST", "/api/v1/user/preferences", "Update preferences", {
                "language": "english",
                "theme": "dark",
                "allow_messages": True
            }),
            "response": []
        },
        {
            "name": "GET /user/privacy",
            "request": create_request("GET", "/api/v1/user/privacy", "Get privacy settings"),
            "response": []
        },
        {
            "name": "POST /user/privacy",
            "request": create_request("POST", "/api/v1/user/privacy", "Update privacy settings", {
                "profile_visibility": "public",
                "show_phone": True,
                "show_email": False,
                "allow_messages": True
            }),
            "response": []
        }
    ]
})

# 4. Driver Verification (6 endpoints)
collection["item"].append({
    "name": "4. Driver Verification",
    "item": [
        {
            "name": "POST /driver/verification",
            "request": create_request("POST", "/api/v1/driver/verification", "Create verification", {
                "dl_number": "DL123456789",
                "dl_expiry_date": "2025-12-31"
            }),
            "response": []
        },
        {
            "name": "GET /driver/verification/status",
            "request": create_request("GET", "/api/v1/driver/verification/status", "Get verification status"),
            "response": []
        },
        {
            "name": "POST /driver/verification/documents",
            "request": {
                "method": "POST",
                "header": [
                    {"key": "Authorization", "value": "Bearer {{sanctum_token}}", "type": "text"}
                ],
                "body": {
                    "mode": "formdata",
                    "formdata": [
                        {"key": "dl_front_image", "type": "file", "src": ""},
                        {"key": "dl_back_image", "type": "file", "src": ""},
                        {"key": "rc_front_image", "type": "file", "src": ""},
                        {"key": "rc_back_image", "type": "file", "src": ""}
                    ]
                },
                "url": {
                    "raw": "{{base_url}}/api/v1/driver/verification/documents",
                    "protocol": "http",
                    "host": ["{{base_url}}"],
                    "path": ["api", "v1", "driver", "verification", "documents"]
                },
                "description": "Upload verification documents"
            },
            "response": []
        },
        {
            "name": "GET /driver/verification/documents",
            "request": create_request("GET", "/api/v1/driver/verification/documents", "Get verification documents"),
            "response": []
        },
        {
            "name": "POST /driver/verification/submit",
            "request": create_request("POST", "/api/v1/driver/verification/submit", "Submit verification", {}),
            "response": []
        },
        {
            "name": "GET /driver/kyc-status",
            "request": create_request("GET", "/api/v1/driver/kyc-status", "Get KYC status"),
            "response": []
        }
    ]
})

# 5. Vehicles (6 endpoints)
collection["item"].append({
    "name": "5. Vehicles",
    "item": [
        {
            "name": "POST /vehicles",
            "request": create_request("POST", "/api/v1/vehicles", "Create vehicle", {
                "vehicle_name": "My Sedan",
                "vehicle_type": "sedan",
                "license_plate": "KA01AB1234",
                "vehicle_color": "Black",
                "vehicle_year": 2023,
                "seating_capacity": 5
            }),
            "response": []
        },
        {
            "name": "GET /vehicles",
            "request": create_request("GET", "/api/v1/vehicles", "List vehicles"),
            "response": []
        },
        {
            "name": "GET /vehicles/{id}",
            "request": create_request("GET", "/api/v1/vehicles/{{vehicle_id}}", "Get vehicle details"),
            "response": []
        },
        {
            "name": "PUT /vehicles/{id}",
            "request": create_request("PUT", "/api/v1/vehicles/{{vehicle_id}}", "Update vehicle", {
                "vehicle_name": "Updated Sedan",
                "vehicle_color": "Red"
            }),
            "response": []
        },
        {
            "name": "DELETE /vehicles/{id}",
            "request": create_request("DELETE", "/api/v1/vehicles/{{vehicle_id}}", "Delete vehicle"),
            "response": []
        },
        {
            "name": "POST /vehicles/{id}/set-default",
            "request": create_request("POST", "/api/v1/vehicles/{{vehicle_id}}/set-default", "Set default vehicle", {}),
            "response": []
        }
    ]
})

# 6. Rides (12 endpoints)
collection["item"].append({
    "name": "6. Rides",
    "item": [
        {
            "name": "POST /rides (Request Ride)",
            "event": [{
                "listen": "test",
                "script": {
                    "exec": [
                        "if (pm.response.code === 200 || pm.response.code === 201) {",
                        "    var jsonData = pm.response.json();",
                        "    pm.environment.set('ride_id', jsonData.ride.id);",
                        "}"
                    ]
                }
            }],
            "request": create_request("POST", "/api/v1/rides", "Request a new ride", {
                "pickup_location": "123 Main St",
                "pickup_lat": 32.7266,
                "pickup_lng": 74.8570,
                "dropoff_location": "456 Oak Ave",
                "dropoff_lat": 32.7100,
                "dropoff_lng": 74.8500,
                "estimated_distance_km": 12,
                "estimated_duration_minutes": 20
            }),
            "response": []
        },
        {
            "name": "GET /rides (Search Available)",
            "request": create_request("GET", "/api/v1/rides/available?seats_needed=2&price_max=500", "Search available rides"),
            "response": []
        },
        {
            "name": "GET /rides/{id}",
            "request": create_request("GET", "/api/v1/rides/{{ride_id}}", "Get ride details"),
            "response": []
        },
        {
            "name": "POST /rides/{id}/accept",
            "request": create_request("POST", "/api/v1/rides/{{ride_id}}/accept", "Accept ride", {}),
            "response": []
        },
        {
            "name": "POST /rides/{id}/arrive",
            "request": create_request("POST", "/api/v1/rides/{{ride_id}}/arrive", "Driver arrives", {}),
            "response": []
        },
        {
            "name": "POST /rides/{id}/start",
            "request": create_request("POST", "/api/v1/rides/{{ride_id}}/start", "Start ride", {}),
            "response": []
        },
        {
            "name": "POST /rides/{id}/complete",
            "request": create_request("POST", "/api/v1/rides/{{ride_id}}/complete", "Complete ride", {
                "actual_distance_km": 12.5,
                "actual_duration_minutes": 22
            }),
            "response": []
        },
        {
            "name": "POST /rides/{id}/cancel",
            "request": create_request("POST", "/api/v1/rides/{{ride_id}}/cancel", "Cancel ride", {
                "reason": "Driver taking too long"
            }),
            "response": []
        },
        {
            "name": "POST /rides/offer",
            "request": create_request("POST", "/api/v1/rides/offer", "Offer a ride (Driver)", {
                "pickup_location": "Downtown",
                "pickup_lat": 32.7266,
                "pickup_lng": 74.8570,
                "dropoff_location": "Airport",
                "dropoff_lat": 32.7100,
                "dropoff_lng": 74.8500,
                "available_seats": 3,
                "price_per_seat": 250,
                "ac_available": True,
                "smoking_allowed": False
            }),
            "response": []
        },
        {
            "name": "POST /rides/{id}/update-status",
            "request": create_request("POST", "/api/v1/rides/{{ride_id}}/update-status", "Update ride status", {
                "status": "started"
            }),
            "response": []
        },
        {
            "name": "GET /rides/{id}/history",
            "request": create_request("GET", "/api/v1/rides/{{ride_id}}/history", "Get ride history"),
            "response": []
        }
    ]
})

# 7. Bookings (6 endpoints)
collection["item"].append({
    "name": "7. Bookings",
    "item": [
        {
            "name": "POST /bookings",
            "event": [{
                "listen": "test",
                "script": {
                    "exec": [
                        "if (pm.response.code === 201) {",
                        "    var jsonData = pm.response.json();",
                        "    pm.environment.set('booking_id', jsonData.booking.id);",
                        "}"
                    ]
                }
            }],
            "request": create_request("POST", "/api/v1/bookings", "Create booking", {
                "ride_id": "{{ride_id}}",
                "seats_booked": 2,
                "passenger_name": "John Doe",
                "passenger_phone": "9876543210",
                "special_instructions": "Wait at main entrance",
                "luggage_info": "1 small bag"
            }),
            "response": []
        },
        {
            "name": "GET /bookings",
            "request": create_request("GET", "/api/v1/bookings", "List bookings"),
            "response": []
        },
        {
            "name": "GET /bookings/{id}",
            "request": create_request("GET", "/api/v1/bookings/{{booking_id}}", "Get booking details"),
            "response": []
        },
        {
            "name": "POST /bookings/{id}/cancel",
            "request": create_request("POST", "/api/v1/bookings/{{booking_id}}/cancel", "Cancel booking", {
                "cancellation_reason": "Change of plans"
            }),
            "response": []
        },
        {
            "name": "GET /bookings/history",
            "request": create_request("GET", "/api/v1/bookings/history", "Get booking history"),
            "response": []
        },
        {
            "name": "GET /bookings/{id}/details",
            "request": create_request("GET", "/api/v1/bookings/{{booking_id}}/details", "Get booking details"),
            "response": []
        }
    ]
})

# 8. Reviews (4 endpoints)
collection["item"].append({
    "name": "8. Reviews",
    "item": [
        {
            "name": "POST /reviews",
            "event": [{
                "listen": "test",
                "script": {
                    "exec": [
                        "if (pm.response.code === 201) {",
                        "    var jsonData = pm.response.json();",
                        "    pm.environment.set('review_id', jsonData.review.id);",
                        "}"
                    ]
                }
            }],
            "request": create_request("POST", "/api/v1/reviews", "Create review", {
                "ride_id": "{{ride_id}}",
                "reviewee_id": "{{driver_id}}",
                "rating": 5,
                "comment": "Great ride!",
                "categories": {
                    "cleanliness": 5,
                    "driving": 4,
                    "communication": 5
                }
            }),
            "response": []
        },
        {
            "name": "GET /reviews/{id}",
            "request": create_request("GET", "/api/v1/reviews/{{review_id}}", "Get review"),
            "response": []
        },
        {
            "name": "GET /reviews/user/{user_id}",
            "request": create_request("GET", "/api/v1/reviews/user/{{driver_id}}", "Get user reviews"),
            "response": []
        },
        {
            "name": "GET /reviews/ride/{ride_id}",
            "request": create_request("GET", "/api/v1/reviews/ride/{{ride_id}}", "Get ride reviews"),
            "response": []
        }
    ]
})

# 9. Chat (6 endpoints)
collection["item"].append({
    "name": "9. Chat & Messaging",
    "item": [
        {
            "name": "POST /chats",
            "event": [{
                "listen": "test",
                "script": {
                    "exec": [
                        "if (pm.response.code === 201) {",
                        "    var jsonData = pm.response.json();",
                        "    pm.environment.set('chat_id', jsonData.data.id);",
                        "}"
                    ]
                }
            }],
            "request": create_request("POST", "/api/v1/chats", "Create chat", {
                "ride_id": "{{ride_id}}"
            }),
            "response": []
        },
        {
            "name": "GET /chats",
            "request": create_request("GET", "/api/v1/chats", "List chats"),
            "response": []
        },
        {
            "name": "POST /chats/{id}/messages",
            "request": {
                "method": "POST",
                "header": [
                    {"key": "Authorization", "value": "Bearer {{sanctum_token}}", "type": "text"}
                ],
                "body": {
                    "mode": "formdata",
                    "formdata": [
                        {"key": "message", "value": "Hello!", "type": "text"},
                        {"key": "message_type", "value": "text", "type": "text"},
                        {"key": "attachment", "type": "file", "src": ""}
                    ]
                },
                "url": {
                    "raw": "{{base_url}}/api/v1/chats/{{chat_id}}/messages",
                    "protocol": "http",
                    "host": ["{{base_url}}"],
                    "path": ["api", "v1", "chats", "{{chat_id}}", "messages"]
                },
                "description": "Send message"
            },
            "response": []
        },
        {
            "name": "GET /chats/{id}/messages",
            "request": create_request("GET", "/api/v1/chats/{{chat_id}}/messages", "Get messages"),
            "response": []
        },
        {
            "name": "POST /chats/{id}/mark-read",
            "request": create_request("POST", "/api/v1/chats/{{chat_id}}/mark-read", "Mark as read", {}),
            "response": []
        },
        {
            "name": "DELETE /chats/{id}",
            "request": create_request("DELETE", "/api/v1/chats/{{chat_id}}", "Delete chat"),
            "response": []
        }
    ]
})

# 10. Saved Routes (5 endpoints)
collection["item"].append({
    "name": "10. Saved Routes",
    "item": [
        {
            "name": "POST /saved-routes",
            "event": [{
                "listen": "test",
                "script": {
                    "exec": [
                        "if (pm.response.code === 201) {",
                        "    var jsonData = pm.response.json();",
                        "    pm.environment.set('saved_route_id', jsonData.data.id);",
                        "}"
                    ]
                }
            }],
            "request": create_request("POST", "/api/v1/saved-routes", "Save route", {
                "from_location": "Home",
                "to_location": "Office"
            }),
            "response": []
        },
        {
            "name": "GET /saved-routes",
            "request": create_request("GET", "/api/v1/saved-routes", "List saved routes"),
            "response": []
        },
        {
            "name": "POST /saved-routes/{id}/pin",
            "request": create_request("POST", "/api/v1/saved-routes/{{saved_route_id}}/pin", "Pin route", {}),
            "response": []
        },
        {
            "name": "PUT /saved-routes/{id}",
            "request": create_request("PUT", "/api/v1/saved-routes/{{saved_route_id}}", "Update route", {
                "from_location": "New Home",
                "to_location": "New Office"
            }),
            "response": []
        },
        {
            "name": "DELETE /saved-routes/{id}",
            "request": create_request("DELETE", "/api/v1/saved-routes/{{saved_route_id}}", "Delete route"),
            "response": []
        }
    ]
})

# 11. Notifications (4 endpoints)
collection["item"].append({
    "name": "11. Notifications",
    "item": [
        {
            "name": "POST /notifications/fcm-token",
            "request": create_request("POST", "/api/v1/notifications/fcm-token", "Register FCM token", {
                "fcm_token": "eJxlj91uwjAMhV9l5XoXEgMSV0hIXCBNV5MmTrOWJiQ2QFXVd18CUi...",
                "device_type": "android",
                "device_id": "device_123456",
                "device_name": "Samsung Galaxy S21"
            }),
            "response": []
        },
        {
            "name": "GET /notifications/preferences",
            "request": create_request("GET", "/api/v1/notifications/preferences", "Get notification preferences"),
            "response": []
        },
        {
            "name": "POST /notifications/preferences",
            "request": create_request("POST", "/api/v1/notifications/preferences", "Update preferences", {
                "preferences": [
                    {"notification_type": "ride_updates", "is_enabled": True},
                    {"notification_type": "messages", "is_enabled": True},
                    {"notification_type": "reviews", "is_enabled": True}
                ]
            }),
            "response": []
        },
        {
            "name": "GET /notifications",
            "request": create_request("GET", "/api/v1/notifications", "Get all notifications"),
            "response": []
        }
    ]
})

# 12. Location (3 endpoints)
collection["item"].append({
    "name": "12. Location Tracking",
    "item": [
        {
            "name": "POST /locations/update",
            "request": create_request("POST", "/api/v1/locations/update", "Update location", {
                "ride_id": "{{ride_id}}",
                "latitude": 28.6139,
                "longitude": 77.2090,
                "accuracy": 5.0,
                "speed": 25.5,
                "heading": 180.0,
                "altitude": 100.0
            }),
            "response": []
        },
        {
            "name": "GET /locations/history/{ride_id}",
            "request": create_request("GET", "/api/v1/locations/history/{{ride_id}}", "Get location history"),
            "response": []
        },
        {
            "name": "GET /locations/current/{ride_id}",
            "request": create_request("GET", "/api/v1/locations/current/{{ride_id}}", "Get current location"),
            "response": []
        }
    ]
})

# 13. Payment Methods (5 endpoints)
collection["item"].append({
    "name": "13. Payment Methods",
    "item": [
        {
            "name": "POST /payment-methods",
            "event": [{
                "listen": "test",
                "script": {
                    "exec": [
                        "if (pm.response.code === 201) {",
                        "    var jsonData = pm.response.json();",
                        "    pm.environment.set('payment_method_id', jsonData.payment_method.id);",
                        "}"
                    ]
                }
            }],
            "request": create_request("POST", "/api/v1/payment-methods", "Add payment method", {
                "payment_type": "card",
                "payment_details": {
                    "card_number": "****1234",
                    "expiry": "12/25",
                    "holder_name": "John Doe"
                },
                "is_default": False
            }),
            "response": []
        },
        {
            "name": "GET /payment-methods",
            "request": create_request("GET", "/api/v1/payment-methods", "List payment methods"),
            "response": []
        },
        {
            "name": "PUT /payment-methods/{id}",
            "request": create_request("PUT", "/api/v1/payment-methods/{{payment_method_id}}", "Update payment method", {
                "payment_type": "wallet",
                "payment_details": {"wallet_id": "wallet123"}
            }),
            "response": []
        },
        {
            "name": "DELETE /payment-methods/{id}",
            "request": create_request("DELETE", "/api/v1/payment-methods/{{payment_method_id}}", "Delete payment method"),
            "response": []
        },
        {
            "name": "POST /payment-methods/{id}/set-default",
            "request": create_request("POST", "/api/v1/payment-methods/{{payment_method_id}}/set-default", "Set default", {}),
            "response": []
        }
    ]
})

# Write to file
with open("POSTMAN_COLLECTION_COMPLETE.json", "w") as f:
    json.dump(collection, f, indent=2)

print("✓ Postman collection generated successfully!")
print(f"✓ Total endpoints: {sum(len(folder['item']) for folder in collection['item'])}")
print(f"✓ Total folders: {len(collection['item'])}")
