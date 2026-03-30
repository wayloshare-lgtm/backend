# Flutter Integration Examples

## Complete Code Examples for Common Scenarios

### 1. Authentication Setup

#### Firebase Authentication
```dart
import 'package:firebase_auth/firebase_auth.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class AuthService {
  final FirebaseAuth _firebaseAuth = FirebaseAuth.instance;
  final String baseUrl = 'https://api.wayloshare.com/api/v1';
  
  String? _sanctumToken;
  
  // Get Firebase ID Token
  Future<String?> getFirebaseToken() async {
    try {
      final user = _firebaseAuth.currentUser;
      if (user != null) {
        return await user.getIdToken();
      }
    } catch (e) {
      print('Error getting Firebase token: $e');
    }
    return null;
  }
  
  // Login with Firebase Token
  Future<bool> loginWithFirebase(String phoneNumber) async {
    try {
      // Verify phone number with Firebase
      await _firebaseAuth.verifyPhoneNumber(
        phoneNumber: phoneNumber,
        verificationCompleted: (PhoneAuthCredential credential) async {
          await _firebaseAuth.signInWithCredential(credential);
        },
        verificationFailed: (FirebaseAuthException e) {
          print('Verification failed: ${e.message}');
        },
        codeSent: (String verificationId, int? resendToken) {
          // Handle code sent
        },
        codeAutoRetrievalTimeout: (String verificationId) {},
      );
      
      // Get Firebase token
      final firebaseToken = await getFirebaseToken();
      if (firebaseToken == null) return false;
      
      // Exchange for Sanctum token
      final response = await http.post(
        Uri.parse('$baseUrl/auth/login'),
        headers: {
          'Authorization': 'Bearer $firebaseToken',
          'Content-Type': 'application/json',
        },
      );
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        _sanctumToken = data['token'];
        return true;
      }
      return false;
    } catch (e) {
      print('Login error: $e');
      return false;
    }
  }
  
  // Get Sanctum Token
  String? get sanctumToken => _sanctumToken;
  
  // Logout
  Future<void> logout() async {
    try {
      await http.post(
        Uri.parse('$baseUrl/auth/logout'),
        headers: {
          'Authorization': 'Bearer $_sanctumToken',
          'Content-Type': 'application/json',
        },
      );
      _sanctumToken = null;
      await _firebaseAuth.signOut();
    } catch (e) {
      print('Logout error: $e');
    }
  }
}
```

### 2. User Profile Management

```dart
class UserProfileService {
  final String baseUrl = 'https://api.wayloshare.com/api/v1';
  final String token;
  
  UserProfileService({required this.token});
  
  // Get User Profile
  Future<Map<String, dynamic>?> getUserProfile() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/user/profile'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
      );
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error fetching profile: $e');
      return null;
    }
  }
  
  // Update User Profile
  Future<bool> updateProfile({
    required String displayName,
    required String dateOfBirth,
    required String gender,
    String? bio,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/user/profile'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'display_name': displayName,
          'date_of_birth': dateOfBirth,
          'gender': gender,
          'bio': bio,
        }),
      );
      
      return response.statusCode == 200;
    } catch (e) {
      print('Error updating profile: $e');
      return false;
    }
  }
  
  // Upload Profile Photo
  Future<String?> uploadProfilePhoto(File imageFile) async {
    try {
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/user/profile/photo'),
      );
      
      request.headers['Authorization'] = 'Bearer $token';
      request.files.add(
        await http.MultipartFile.fromPath(
          'profile_photo',
          imageFile.path,
        ),
      );
      
      var response = await request.send();
      
      if (response.statusCode == 200) {
        final responseData = await response.stream.bytesToString();
        final data = jsonDecode(responseData);
        return data['profile_photo_url'];
      }
      return null;
    } catch (e) {
      print('Error uploading photo: $e');
      return null;
    }
  }
  
  // Complete Onboarding
  Future<bool> completeOnboarding() async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/user/complete-onboarding'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
      );
      
      return response.statusCode == 200;
    } catch (e) {
      print('Error completing onboarding: $e');
      return false;
    }
  }
}
```

### 3. Vehicle Management

```dart
class VehicleService {
  final String baseUrl = 'https://api.wayloshare.com/api/v1';
  final String token;
  
  VehicleService({required this.token});
  
  // Create Vehicle
  Future<Map<String, dynamic>?> createVehicle({
    required String vehicleName,
    required String vehicleType,
    required String licensePlate,
    required String vehicleColor,
    required int vehicleYear,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/vehicles'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'vehicle_name': vehicleName,
          'vehicle_type': vehicleType,
          'license_plate': licensePlate,
          'vehicle_color': vehicleColor,
          'vehicle_year': vehicleYear,
        }),
      );
      
      if (response.statusCode == 201) {
        final data = jsonDecode(response.body);
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error creating vehicle: $e');
      return null;
    }
  }
  
  // List Vehicles
  Future<List<Map<String, dynamic>>?> listVehicles() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/vehicles'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
      );
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return List<Map<String, dynamic>>.from(data['data']);
      }
      return null;
    } catch (e) {
      print('Error listing vehicles: $e');
      return null;
    }
  }
  
  // Upload Vehicle Photo
  Future<String?> uploadVehiclePhoto(int vehicleId, File imageFile) async {
    try {
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/vehicles/$vehicleId/photo'),
      );
      
      request.headers['Authorization'] = 'Bearer $token';
      request.files.add(
        await http.MultipartFile.fromPath(
          'vehicle_photo',
          imageFile.path,
        ),
      );
      
      var response = await request.send();
      
      if (response.statusCode == 200) {
        final responseData = await response.stream.bytesToString();
        final data = jsonDecode(responseData);
        return data['vehicle_photo_url'];
      }
      return null;
    } catch (e) {
      print('Error uploading vehicle photo: $e');
      return null;
    }
  }
  
  // Set Default Vehicle
  Future<bool> setDefaultVehicle(int vehicleId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/vehicles/$vehicleId/set-default'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
      );
      
      return response.statusCode == 200;
    } catch (e) {
      print('Error setting default vehicle: $e');
      return false;
    }
  }
}
```

### 4. Booking Management

```dart
class BookingService {
  final String baseUrl = 'https://api.wayloshare.com/api/v1';
  final String token;
  
  BookingService({required this.token});
  
  // Create Booking
  Future<Map<String, dynamic>?> createBooking({
    required int rideId,
    required int seatsBooked,
    required String passengerName,
    required String passengerPhone,
    String? specialInstructions,
    String? luggageInfo,
    String? accessibilityRequirements,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/bookings'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'ride_id': rideId,
          'seats_booked': seatsBooked,
          'passenger_name': passengerName,
          'passenger_phone': passengerPhone,
          'special_instructions': specialInstructions,
          'luggage_info': luggageInfo,
          'accessibility_requirements': accessibilityRequirements,
        }),
      );
      
      if (response.statusCode == 201) {
        final data = jsonDecode(response.body);
        return data['data'];
      } else if (response.statusCode == 422) {
        final data = jsonDecode(response.body);
        print('Validation errors: ${data['errors']}');
      }
      return null;
    } catch (e) {
      print('Error creating booking: $e');
      return null;
    }
  }
  
  // List Bookings with Pagination
  Future<Map<String, dynamic>?> listBookings({
    int page = 1,
    int perPage = 15,
    String? status,
  }) async {
    try {
      String url = '$baseUrl/bookings?page=$page&per_page=$perPage';
      if (status != null) {
        url += '&status=$status';
      }
      
      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
      );
      
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
      return null;
    } catch (e) {
      print('Error listing bookings: $e');
      return null;
    }
  }
  
  // Cancel Booking
  Future<bool> cancelBooking(int bookingId, String reason) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/bookings/$bookingId/cancel'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'cancellation_reason': reason,
        }),
      );
      
      return response.statusCode == 200;
    } catch (e) {
      print('Error cancelling booking: $e');
      return false;
    }
  }
}
```

### 5. Chat & Messaging

```dart
class ChatService {
  final String baseUrl = 'https://api.wayloshare.com/api/v1';
  final String token;
  
  ChatService({required this.token});
  
  // Create Chat
  Future<Map<String, dynamic>?> createChat(int rideId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/chats'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({'ride_id': rideId}),
      );
      
      if (response.statusCode == 201) {
        final data = jsonDecode(response.body);
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error creating chat: $e');
      return null;
    }
  }
  
  // Send Message with Optional Attachment
  Future<Map<String, dynamic>?> sendMessage({
    required int chatId,
    required String message,
    String messageType = 'text',
    File? attachment,
    Map<String, dynamic>? metadata,
  }) async {
    try {
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/chats/$chatId/messages'),
      );
      
      request.headers['Authorization'] = 'Bearer $token';
      request.fields['message'] = message;
      request.fields['message_type'] = messageType;
      
      if (metadata != null) {
        request.fields['metadata'] = jsonEncode(metadata);
      }
      
      if (attachment != null) {
        request.files.add(
          await http.MultipartFile.fromPath(
            'attachment',
            attachment.path,
          ),
        );
      }
      
      var response = await request.send();
      
      if (response.statusCode == 201) {
        final responseData = await response.stream.bytesToString();
        final data = jsonDecode(responseData);
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error sending message: $e');
      return null;
    }
  }
  
  // Get Messages
  Future<Map<String, dynamic>?> getMessages(int chatId, {int perPage = 20}) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/chats/$chatId/messages?per_page=$perPage'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
      );
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error fetching messages: $e');
      return null;
    }
  }
  
  // Mark Messages as Read
  Future<bool> markMessagesAsRead(int chatId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/chats/$chatId/mark-read'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
      );
      
      return response.statusCode == 200;
    } catch (e) {
      print('Error marking messages as read: $e');
      return false;
    }
  }
}
```

### 6. Location Tracking

```dart
class LocationService {
  final String baseUrl = 'https://api.wayloshare.com/api/v1';
  final String token;
  
  LocationService({required this.token});
  
  // Update Location
  Future<bool> updateLocation({
    required int rideId,
    required double latitude,
    required double longitude,
    double? accuracy,
    double? speed,
    double? heading,
    double? altitude,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/locations/update'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'ride_id': rideId,
          'latitude': latitude,
          'longitude': longitude,
          'accuracy': accuracy,
          'speed': speed,
          'heading': heading,
          'altitude': altitude,
          'timestamp': DateTime.now().toIso8601String(),
        }),
      );
      
      return response.statusCode == 201;
    } catch (e) {
      print('Error updating location: $e');
      return false;
    }
  }
  
  // Get Location History
  Future<List<Map<String, dynamic>>?> getLocationHistory(
    int rideId, {
    int limit = 100,
    int offset = 0,
  }) async {
    try {
      final response = await http.get(
        Uri.parse(
          '$baseUrl/locations/history/$rideId?limit=$limit&offset=$offset',
        ),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
      );
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return List<Map<String, dynamic>>.from(data['data']);
      }
      return null;
    } catch (e) {
      print('Error fetching location history: $e');
      return null;
    }
  }
}
```

### 7. Notifications & FCM

```dart
class NotificationService {
  final String baseUrl = 'https://api.wayloshare.com/api/v1';
  final String token;
  
  NotificationService({required this.token});
  
  // Register FCM Token
  Future<bool> registerFcmToken({
    required String fcmToken,
    required String deviceType,
    String? deviceId,
    String? deviceName,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/notifications/fcm-token'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'fcm_token': fcmToken,
          'device_type': deviceType,
          'device_id': deviceId,
          'device_name': deviceName,
        }),
      );
      
      return response.statusCode == 201;
    } catch (e) {
      print('Error registering FCM token: $e');
      return false;
    }
  }
  
  // Get Notification Preferences
  Future<List<Map<String, dynamic>>?> getPreferences() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/notifications/preferences'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
      );
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return List<Map<String, dynamic>>.from(data['data']);
      }
      return null;
    } catch (e) {
      print('Error fetching preferences: $e');
      return null;
    }
  }
  
  // Update Notification Preferences
  Future<bool> updatePreferences(
    List<Map<String, dynamic>> preferences,
  ) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/notifications/preferences'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({'preferences': preferences}),
      );
      
      return response.statusCode == 200;
    } catch (e) {
      print('Error updating preferences: $e');
      return false;
    }
  }
}
```

### 8. Reviews

```dart
class ReviewService {
  final String baseUrl = 'https://api.wayloshare.com/api/v1';
  final String token;
  
  ReviewService({required this.token});
  
  // Create Review
  Future<Map<String, dynamic>?> createReview({
    required int rideId,
    required int revieweeId,
    required int rating,
    String? comment,
    Map<String, int>? categories,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/reviews'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'ride_id': rideId,
          'reviewee_id': revieweeId,
          'rating': rating,
          'comment': comment,
          'categories': categories,
        }),
      );
      
      if (response.statusCode == 201) {
        final data = jsonDecode(response.body);
        return data['data'];
      }
      return null;
    } catch (e) {
      print('Error creating review: $e');
      return null;
    }
  }
  
  // Get Reviews for User
  Future<Map<String, dynamic>?> getUserReviews(
    int userId, {
    int page = 1,
    int perPage = 15,
  }) async {
    try {
      final response = await http.get(
        Uri.parse(
          '$baseUrl/reviews/user/$userId?page=$page&per_page=$perPage',
        ),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
      );
      
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
      return null;
    } catch (e) {
      print('Error fetching user reviews: $e');
      return null;
    }
  }
}
```

### 9. Error Handling & Retry Logic

```dart
class ApiClient {
  final String baseUrl;
  final String token;
  
  ApiClient({required this.baseUrl, required this.token});
  
  // Generic GET with retry logic
  Future<http.Response> getWithRetry(
    String endpoint, {
    int maxRetries = 3,
    Duration delay = const Duration(seconds: 1),
  }) async {
    int retries = 0;
    
    while (retries < maxRetries) {
      try {
        final response = await http.get(
          Uri.parse('$baseUrl$endpoint'),
          headers: {
            'Authorization': 'Bearer $token',
            'Content-Type': 'application/json',
          },
        ).timeout(const Duration(seconds: 30));
        
        if (response.statusCode == 429) {
          // Rate limited, wait and retry
          final retryAfter = int.tryParse(
            response.headers['retry-after'] ?? '60',
          ) ?? 60;
          await Future.delayed(Duration(seconds: retryAfter));
          retries++;
          continue;
        }
        
        if (response.statusCode >= 500) {
          // Server error, retry with exponential backoff
          await Future.delayed(delay * (retries + 1));
          retries++;
          continue;
        }
        
        return response;
      } on SocketException {
        // Network error, retry
        await Future.delayed(delay * (retries + 1));
        retries++;
        continue;
      } on TimeoutException {
        // Timeout, retry
        await Future.delayed(delay * (retries + 1));
        retries++;
        continue;
      }
    }
    
    throw Exception('Max retries exceeded');
  }
  
  // Handle API errors
  void handleApiError(http.Response response) {
    try {
      final data = jsonDecode(response.body);
      
      switch (response.statusCode) {
        case 400:
          throw BadRequestException(data['message']);
        case 401:
          throw UnauthorizedException(data['message']);
        case 403:
          throw ForbiddenException(data['message']);
        case 404:
          throw NotFoundException(data['message']);
        case 422:
          throw ValidationException(data['errors']);
        case 429:
          throw RateLimitException(data['message']);
        case 500:
          throw ServerException(data['message']);
        default:
          throw Exception(data['message']);
      }
    } catch (e) {
      throw Exception('Error parsing response: $e');
    }
  }
}

// Custom Exceptions
class BadRequestException implements Exception {
  final String message;
  BadRequestException(this.message);
  
  @override
  String toString() => 'BadRequestException: $message';
}

class UnauthorizedException implements Exception {
  final String message;
  UnauthorizedException(this.message);
  
  @override
  String toString() => 'UnauthorizedException: $message';
}

class ForbiddenException implements Exception {
  final String message;
  ForbiddenException(this.message);
  
  @override
  String toString() => 'ForbiddenException: $message';
}

class NotFoundException implements Exception {
  final String message;
  NotFoundException(this.message);
  
  @override
  String toString() => 'NotFoundException: $message';
}

class ValidationException implements Exception {
  final Map<String, dynamic> errors;
  ValidationException(this.errors);
  
  @override
  String toString() => 'ValidationException: $errors';
}

class RateLimitException implements Exception {
  final String message;
  RateLimitException(this.message);
  
  @override
  String toString() => 'RateLimitException: $message';
}

class ServerException implements Exception {
  final String message;
  ServerException(this.message);
  
  @override
  String toString() => 'ServerException: $message';
}
```

### 10. Complete Integration Example

```dart
// main.dart
import 'package:flutter/material.dart';
import 'package:firebase_core/firebase_core.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({Key? key}) : super(key: key);
  
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'WayloShare',
      theme: ThemeData(primarySwatch: Colors.blue),
      home: const LoginScreen(),
    );
  }
}

class LoginScreen extends StatefulWidget {
  const LoginScreen({Key? key}) : super(key: key);
  
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  late AuthService _authService;
  final _phoneController = TextEditingController();
  bool _isLoading = false;
  
  @override
  void initState() {
    super.initState();
    _authService = AuthService();
  }
  
  Future<void> _login() async {
    setState(() => _isLoading = true);
    
    try {
      final success = await _authService.loginWithFirebase(
        '+91${_phoneController.text}',
      );
      
      if (success && mounted) {
        Navigator.of(context).pushReplacementNamed('/home');
      } else if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Login failed')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('WayloShare Login')),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            TextField(
              controller: _phoneController,
              decoration: const InputDecoration(
                labelText: 'Phone Number',
                hintText: 'Enter 10-digit phone number',
              ),
              keyboardType: TextInputType.phone,
            ),
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: _isLoading ? null : _login,
              child: _isLoading
                  ? const CircularProgressIndicator()
                  : const Text('Login'),
            ),
          ],
        ),
      ),
    );
  }
  
  @override
  void dispose() {
    _phoneController.dispose();
    super.dispose();
  }
}
```

## Best Practices

1. **Token Management**: Store tokens securely using platform-specific secure storage
2. **Error Handling**: Always handle errors gracefully with user-friendly messages
3. **Retry Logic**: Implement exponential backoff for transient failures
4. **Pagination**: Use pagination for large datasets to improve performance
5. **Caching**: Cache frequently accessed data to reduce API calls
6. **Validation**: Validate all user inputs before sending to API
7. **Logging**: Log API calls and errors for debugging
8. **Testing**: Write unit tests for all service classes
9. **Rate Limiting**: Respect rate limits and implement backoff strategies
10. **Security**: Never expose sensitive data in logs or error messages
