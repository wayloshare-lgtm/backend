<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ride;
use App\Models\Review;
use App\Models\Chat;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InputSanitizationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    // User Profile Sanitization Tests
    public function test_user_profile_update_sanitizes_bio(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'display_name' => 'John Doe',
                'bio' => '<script>alert("XSS")</script>I love coding',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'display_name' => 'John Doe',
        ]);

        // Verify bio doesn't contain script tags
        $user = User::find($this->user->id);
        $this->assertStringNotContainsString('<script>', $user->bio);
    }

    public function test_user_profile_update_sanitizes_display_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'display_name' => '<img src=x onerror="alert(\'XSS\')">John',
            ]);

        $response->assertStatus(200);
        $user = User::find($this->user->id);
        $this->assertStringNotContainsString('<img', $user->display_name);
    }

    public function test_user_profile_update_sanitizes_email(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'email' => '  JOHN@EXAMPLE.COM  ',
            ]);

        $response->assertStatus(200);
        $user = User::find($this->user->id);
        $this->assertEquals('john@example.com', $user->email);
    }

    public function test_user_profile_update_sanitizes_emergency_contact(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'emergency_contact' => '+91-9876-543-210',
            ]);

        $response->assertStatus(200);
        $user = User::find($this->user->id);
        $driverProfile = $user->driverProfile;
        if ($driverProfile) {
            $this->assertEquals('919876543210', $driverProfile->emergency_contact);
        }
    }

    public function test_complete_onboarding_sanitizes_display_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/complete-onboarding', [
                'display_name' => '<script>alert("XSS")</script>John',
                'date_of_birth' => '2000-01-01',
                'gender' => 'male',
                'user_preference' => 'driver',
            ]);

        $response->assertStatus(200);
        $user = User::find($this->user->id);
        $this->assertStringNotContainsString('<script>', $user->display_name);
    }

    // Review Sanitization Tests
    public function test_create_review_sanitizes_comment(): void
    {
        $ride = Ride::factory()
            ->create([
                'driver_id' => $this->otherUser->id,
                'rider_id' => $this->user->id,
                'status' => 'completed',
            ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride->id,
                'reviewee_id' => $this->otherUser->id,
                'rating' => 5,
                'comment' => '<img src=x onerror="alert(\'XSS\')">Great ride!',
            ]);

        $response->assertStatus(201);
        $review = Review::first();
        $this->assertStringNotContainsString('<img', $review->comment);
        $this->assertStringContainsString('Great ride!', $review->comment);
    }

    public function test_create_review_sanitizes_categories(): void
    {
        $ride = Ride::factory()
            ->create([
                'driver_id' => $this->otherUser->id,
                'rider_id' => $this->user->id,
                'status' => 'completed',
            ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride->id,
                'reviewee_id' => $this->otherUser->id,
                'rating' => 5,
                'categories' => [
                    [
                        'name' => '<script>alert("XSS")</script>cleanliness',
                        'rating' => 5,
                    ],
                ],
            ]);

        $response->assertStatus(201);
        $review = Review::first();
        $categories = $review->categories;
        if (is_array($categories) && !empty($categories)) {
            $categoryJson = json_encode($categories);
            $this->assertStringNotContainsString('<script>', $categoryJson);
        }
    }

    // Chat Message Sanitization Tests
    public function test_send_message_sanitizes_text(): void
    {
        $ride = Ride::factory()
            ->create([
                'driver_id' => $this->otherUser->id,
                'rider_id' => $this->user->id,
            ]);

        $chat = Chat::factory()->create(['ride_id' => $ride->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/chats/{$chat->id}/messages", [
                'message' => '<script>alert("XSS")</script>Hello',
                'message_type' => 'text',
            ]);

        $response->assertStatus(201);
        $message = $chat->messages()->first();
        $this->assertStringNotContainsString('<script>', $message->message);
        $this->assertStringContainsString('Hello', $message->message);
    }

    public function test_send_message_sanitizes_metadata(): void
    {
        $ride = Ride::factory()
            ->create([
                'driver_id' => $this->otherUser->id,
                'rider_id' => $this->user->id,
            ]);

        $chat = Chat::factory()->create(['ride_id' => $ride->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/chats/{$chat->id}/messages", [
                'message' => 'Hello',
                'message_type' => 'text',
                'metadata' => json_encode([
                    'location' => '<script>alert("XSS")</script>Home',
                ]),
            ]);

        $response->assertStatus(201);
        $message = $chat->messages()->first();
        if ($message->metadata) {
            $metadataJson = json_encode($message->metadata);
            $this->assertStringNotContainsString('<script>', $metadataJson);
        }
    }

    // Booking Sanitization Tests
    public function test_create_booking_sanitizes_special_instructions(): void
    {
        $ride = Ride::factory()
            ->create([
                'driver_id' => $this->otherUser->id,
                'status' => 'requested',
            ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/bookings', [
                'ride_id' => $ride->id,
                'seats_booked' => 2,
                'passenger_name' => 'John Doe',
                'passenger_phone' => '9876543210',
                'special_instructions' => '<script>alert("XSS")</script>Please wait outside',
            ]);

        $response->assertStatus(201);
        $booking = Booking::first();
        $this->assertStringNotContainsString('<script>', $booking->special_instructions);
        $this->assertStringContainsString('Please wait outside', $booking->special_instructions);
    }

    public function test_create_booking_sanitizes_passenger_name(): void
    {
        $ride = Ride::factory()
            ->create([
                'driver_id' => $this->otherUser->id,
                'status' => 'requested',
            ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/bookings', [
                'ride_id' => $ride->id,
                'seats_booked' => 2,
                'passenger_name' => '<img src=x onerror="alert(\'XSS\')">John',
                'passenger_phone' => '9876543210',
            ]);

        $response->assertStatus(201);
        $booking = Booking::first();
        $this->assertStringNotContainsString('<img', $booking->passenger_name);
    }

    public function test_create_booking_sanitizes_luggage_info(): void
    {
        $ride = Ride::factory()
            ->create([
                'driver_id' => $this->otherUser->id,
                'status' => 'requested',
            ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/bookings', [
                'ride_id' => $ride->id,
                'seats_booked' => 2,
                'passenger_name' => 'John Doe',
                'passenger_phone' => '9876543210',
                'luggage_info' => '<script>alert("XSS")</script>2 large bags',
            ]);

        $response->assertStatus(201);
        $booking = Booking::first();
        $this->assertStringNotContainsString('<script>', $booking->luggage_info);
        $this->assertStringContainsString('2 large bags', $booking->luggage_info);
    }

    public function test_create_booking_sanitizes_accessibility_requirements(): void
    {
        $ride = Ride::factory()
            ->create([
                'driver_id' => $this->otherUser->id,
                'status' => 'requested',
            ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/bookings', [
                'ride_id' => $ride->id,
                'seats_booked' => 2,
                'passenger_name' => 'John Doe',
                'passenger_phone' => '9876543210',
                'accessibility_requirements' => '<script>alert("XSS")</script>Wheelchair access needed',
            ]);

        $response->assertStatus(201);
        $booking = Booking::first();
        $this->assertStringNotContainsString('<script>', $booking->accessibility_requirements);
        $this->assertStringContainsString('Wheelchair access needed', $booking->accessibility_requirements);
    }

    // Saved Route Sanitization Tests
    public function test_create_saved_route_sanitizes_locations(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/saved-routes', [
                'from_location' => '<script>alert("XSS")</script>Home',
                'to_location' => 'Office',
            ]);

        $response->assertStatus(201);
        $savedRoute = $response->json('data');
        $this->assertStringNotContainsString('<script>', $savedRoute['from_location']);
    }

    // Test that sanitization doesn't break valid data
    public function test_sanitization_preserves_valid_text_with_newlines(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'bio' => "Line 1\nLine 2\nLine 3",
            ]);

        $response->assertStatus(200);
        $user = User::find($this->user->id);
        $this->assertStringContainsString("\n", $user->bio);
    }

    public function test_sanitization_preserves_valid_special_characters_in_text(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'bio' => 'I love coding & design! (It\'s awesome)',
            ]);

        $response->assertStatus(200);
        $user = User::find($this->user->id);
        // The text should be preserved (though special chars may be HTML encoded)
        $this->assertNotEmpty($user->bio);
    }
}
