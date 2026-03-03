# Flutter App Data Requirements - Agent Prompt

## Instructions for Flutter App Agent

Please analyze your Flutter app codebase and provide a comprehensive list of all data that will be sent to the WayloShare backend API.

---

## Questions to Answer

### 1. User Authentication & Profile
- What fields does the Flutter app collect during user registration/login?
- Does it collect: profile picture, date of birth, gender, address, emergency contact, preferred language?
- Any additional user metadata?

### 2. Ride Request Endpoint
When a user requests a ride, what data will the Flutter app send?

**Current API expects:**
```json
{
  "pickup_location": "string",
  "pickup_lat": "number",
  "pickup_lng": "number",
  "dropoff_location": "string",
  "dropoff_lat": "number",
  "dropoff_lng": "number",
  "estimated_distance_km": "number",
  "estimated_duration_minutes": "integer",
  "toll_amount": "number",
  "city": "string"
}
```

**Additional fields the Flutter app might send:**
- Passenger name?
- Passenger phone number?
- Special instructions/notes?
- Preferred vehicle type?
- Promo code?
- Payment method preference?
- Accessibility requirements?
- Number of passengers?
- Luggage/cargo info?
- Preferred driver rating minimum?
- Any other fields?

### 3. Driver Profile Endpoint
When a driver creates/updates their profile, what data will the Flutter app send?

**Current API expects:**
```json
{
  "license_number": "string",
  "vehicle_type": "string",
  "vehicle_number": "string",
  "current_lat": "number",
  "current_lng": "number"
}
```

**Additional fields the Flutter app might send:**
- Profile picture/avatar?
- License expiry date?
- Vehicle registration number?
- Vehicle color?
- Vehicle model/year?
- Insurance details?
- Bank account info?
- Emergency contact?
- Languages spoken?
- Vehicle capacity?
- Any other fields?

### 4. Ride Completion Endpoint
When a ride is completed, what data will the Flutter app send?

**Current API expects:**
```json
{
  "actual_distance_km": "number",
  "actual_duration_minutes": "integer",
  "toll_amount": "number",
  "city": "string"
}
```

**Additional fields the Flutter app might send:**
- Passenger rating (1-5 stars)?
- Driver rating (1-5 stars)?
- Feedback/comments?
- Payment method used?
- Tip amount?
- Issues/complaints?
- Photos/evidence?
- Any other fields?

### 5. Driver Location Updates
When driver updates location, what data will the Flutter app send?

**Current API expects:**
```json
{
  "current_lat": "number",
  "current_lng": "number"
}
```

**Additional fields the Flutter app might send:**
- GPS accuracy?
- Speed?
- Heading/bearing?
- Altitude?
- Timestamp?
- Any other fields?

### 6. User Profile Updates
Does the Flutter app have an endpoint to update user profile? What fields?

**Possible fields:**
- Name?
- Phone number?
- Email?
- Profile picture?
- Address?
- Emergency contact?
- Preferred payment method?
- Language preference?
- Notification preferences?
- Any other fields?

### 7. Payment & Transactions
Does the Flutter app handle payments? What payment-related data needs to be stored?

**Possible fields:**
- Payment method (card, wallet, cash)?
- Card details (last 4 digits)?
- Wallet balance?
- Transaction history?
- Refund requests?
- Any other fields?

### 8. Ratings & Reviews
Does the Flutter app have a ratings/reviews system? What data is needed?

**Possible fields:**
- Rating (1-5 stars)?
- Review text?
- Photos?
- Category (cleanliness, safety, driving, etc.)?
- Any other fields?

### 9. Chat/Messaging
Does the Flutter app have in-app messaging? What data structure?

**Possible fields:**
- Message text?
- Attachments (images, files)?
- Timestamps?
- Read status?
- Any other fields?

### 10. Notifications
What notification data does the Flutter app need to send/receive?

**Possible fields:**
- FCM token?
- Notification preferences?
- Device info?
- Any other fields?

---

## Output Format

Please provide the response in this format:

```markdown
# Flutter App Data Structure

## 1. User Authentication & Profile
- Field 1: type, required/optional, description
- Field 2: type, required/optional, description

## 2. Ride Request
**Additional fields beyond current API:**
- Field 1: type, required/optional, description
- Field 2: type, required/optional, description

## 3. Driver Profile
**Additional fields beyond current API:**
- Field 1: type, required/optional, description

## 4. Ride Completion
**Additional fields beyond current API:**
- Field 1: type, required/optional, description

## 5. Driver Location Updates
**Additional fields beyond current API:**
- Field 1: type, required/optional, description

## 6. Other Endpoints
[List any other endpoints/data structures]

## Summary
- Total new fields to add: X
- Database migrations needed: Y
- New endpoints needed: Z
```

---

## How to Use This

1. Analyze your Flutter app's data models and API calls
2. Compare with the current WayloShare API endpoints
3. List all additional fields your app sends
4. Provide the response in the format above
5. Share this with the backend team to update the API

---

**Note:** The backend API is flexible and can be updated to accept any additional data your Flutter app sends. Just provide the exact field names, types, and whether they're required or optional.
