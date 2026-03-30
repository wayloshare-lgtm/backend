<?php

namespace Tests\Integration;

use App\Models\User;
use App\Models\Ride;
use App\Models\Booking;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RideBookingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test complete passenger booking workflow:
     * Search rides → book → chat → review
     */
    public function test_complete_passenger_booking_workflow()
    {
        // Step 1: Create driver and passenger
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        // Step 2: Driver creates a ride offering
        $rideData = [
            'pickup_location' => 'Bangalore Central',
            'pickup_lat' => 12.9716,
            'pickup_lng' => 77.5946,
            'dropoff_location' => 'Bangalore Airport',
            'dropoff_lat' => 13.1939,
            'dropoff_lng' => 77.7068,
            'departure_date' => now()->addDay()->format('Y-m-d'),
            'departure_time' => '10:00:00',
            'available_seats' => 3,
            'price_per_seat' => 250,
            'ac_available' => true,
            'wifi_available' => false,
            'smoking_allowed' => false,
        ];

        $response = $this->actingAs($driver, 'sanctum')
            ->postJson('/api/v1/rides/offer', $rideData);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $ride = Ride::where('driver_id', $driver->id)->first();
        $this->assertNotNull($ride);
        $this->assertEquals(3, $ride->available_seats);
        $this->assertEquals(250, $ride->price_per_seat);

        // Step 3: Passenger searches for available rides
        $response = $this->actingAs($passenger, 'sanctum')
            ->getJson('/api/v1/rides/available?from_location=Bangalore Central&to_location=Bangalore Airport');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertGreaterThan(0, count($response->json('data')));

        // Step 4: Passenger books the ride
        $bookingData = [
            'ride_id' => $ride->id,
            'seats_booked' => 2,
            'passenger_name' => 'John Passenger',
            'passenger_phone' => '9876543210',
            'special_instructions' => 'Please pick me up from the main entrance',
        ];

        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson('/api/v1/bookings', $bookingData);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $booking = Booking::where('passenger_id', $passenger->id)->first();
        $this->assertNotNull($booking);
        $this->assertEquals(2, $booking->seats_booked);
        $this->assertEquals('pending', $booking->booking_status);

        // Step 5: Verify booking is in database
        $this->assertDatabaseHas('bookings', [
            'ride_id' => $ride->id,
            'passenger_id' => $passenger->id,
            'seats_booked' => 2,
            'booking_status' => 'pending',
        ]);

        // Step 6: Passenger initiates chat with driver
        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson('/api/v1/chats', [
                'ride_id' => $ride->id,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $chat = Chat::where('ride_id', $ride->id)->first();
        $this->assertNotNull($chat);

        // Step 7: Passenger sends message to driver
        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/chats/{$chat->id}/messages", [
                'message' => 'Hi, I have booked 2 seats. Can you confirm?',
                'message_type' => 'text',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $message = Message::where('chat_id', $chat->id)->first();
        $this->assertNotNull($message);
        $this->assertEquals('Hi, I have booked 2 seats. Can you confirm?', $message->message);
        $this->assertFalse($message->is_read);

        // Step 8: Driver reads the message
        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/chats/{$chat->id}/mark-read", []);

        $response->assertStatus(200);

        $message->refresh();
        $this->assertTrue($message->is_read);

        // Step 9: Confirm booking
        $response = $this->actingAs($driver, 'sanctum')
            ->patchJson("/api/v1/bookings/{$booking->id}", [
                'booking_status' => 'confirmed',
            ]);

        $response->assertStatus(200);

        $booking->refresh();
        $this->assertEquals('confirmed', $booking->booking_status);

        // Step 10: Complete the ride
        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/complete", [
                'actual_distance_km' => 25.5,
                'actual_duration_minutes' => 45,
                'actual_fare' => 500,
            ]);

        $response->assertStatus(200);

        $ride->refresh();
        $this->assertEquals('completed', $ride->status);

        // Step 11: Update booking status to completed
        $response = $this->actingAs($driver, 'sanctum')
            ->patchJson("/api/v1/bookings/{$booking->id}", [
                'booking_status' => 'completed',
            ]);

        $response->assertStatus(200);

        $booking->refresh();
        $this->assertEquals('completed', $booking->booking_status);

        // Step 12: Passenger reviews the driver
        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride->id,
                'reviewee_id' => $driver->id,
                'rating' => 5,
                'comment' => 'Great driver, very professional!',
                'categories' => [
                    'cleanliness' => 5,
                    'driving' => 5,
                    'communication' => 4,
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('reviews', [
            'ride_id' => $ride->id,
            'reviewer_id' => $passenger->id,
            'reviewee_id' => $driver->id,
            'rating' => 5,
        ]);

        // Step 13: Driver reviews the passenger
        $response = $this->actingAs($driver, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'ride_id' => $ride->id,
                'reviewee_id' => $passenger->id,
                'rating' => 4,
                'comment' => 'Good passenger, polite and on time',
                'categories' => [
                    'behavior' => 4,
                    'punctuality' => 5,
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        // Verify both reviews exist
        $this->assertDatabaseHas('reviews', [
            'ride_id' => $ride->id,
            'reviewer_id' => $driver->id,
            'reviewee_id' => $passenger->id,
            'rating' => 4,
        ]);
    }

    /**
     * Test passenger can cancel booking before ride starts
     */
    public function test_passenger_can_cancel_booking()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
            'status' => 'requested',
        ]);

        $booking = Booking::factory()->create([
            'ride_id' => $ride->id,
            'passenger_id' => $passenger->id,
            'booking_status' => 'confirmed',
        ]);

        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/bookings/{$booking->id}/cancel", [
                'cancellation_reason' => 'Change of plans',
            ]);

        $response->assertStatus(200);

        $booking->refresh();
        $this->assertEquals('cancelled', $booking->booking_status);
        $this->assertEquals('Change of plans', $booking->cancellation_reason);
    }

    /**
     * Test booking with special instructions and luggage info
     */
    public function test_booking_with_special_instructions()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'ride_id' => $ride->id,
                'seats_booked' => 1,
                'passenger_name' => 'John Doe',
                'passenger_phone' => '9876543210',
                'special_instructions' => 'Please wait 5 minutes at the entrance',
                'luggage_info' => '2 large suitcases',
                'accessibility_requirements' => 'Wheelchair accessible vehicle needed',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('bookings', [
            'ride_id' => $ride->id,
            'passenger_id' => $passenger->id,
            'special_instructions' => 'Please wait 5 minutes at the entrance',
            'luggage_info' => '2 large suitcases',
            'accessibility_requirements' => 'Wheelchair accessible vehicle needed',
        ]);
    }

    /**
     * Test multiple passengers can book same ride
     */
    public function test_multiple_passengers_can_book_same_ride()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger1 = User::factory()->create(['user_preference' => 'passenger']);
        $passenger2 = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->create([
            'driver_id' => $driver->id,
            'available_seats' => 4,
        ]);

        // First passenger books 2 seats
        $response1 = $this->actingAs($passenger1, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'ride_id' => $ride->id,
                'seats_booked' => 2,
                'passenger_name' => 'Passenger 1',
                'passenger_phone' => '9876543210',
            ]);

        $response1->assertStatus(201);

        // Second passenger books 2 seats
        $response2 = $this->actingAs($passenger2, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'ride_id' => $ride->id,
                'seats_booked' => 2,
                'passenger_name' => 'Passenger 2',
                'passenger_phone' => '9876543211',
            ]);

        $response2->assertStatus(201);

        // Verify both bookings exist
        $this->assertDatabaseHas('bookings', [
            'ride_id' => $ride->id,
            'passenger_id' => $passenger1->id,
            'seats_booked' => 2,
        ]);

        $this->assertDatabaseHas('bookings', [
            'ride_id' => $ride->id,
            'passenger_id' => $passenger2->id,
            'seats_booked' => 2,
        ]);
    }

    /**
     * Test chat messages are properly stored and retrieved
     */
    public function test_chat_messages_workflow()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        // Create chat
        $chat = Chat::factory()->create(['ride_id' => $ride->id]);

        // Passenger sends message
        $this->actingAs($passenger, 'sanctum')
            ->postJson("/api/v1/chats/{$chat->id}/messages", [
                'message' => 'Hello driver!',
                'message_type' => 'text',
            ]);

        // Driver sends message
        $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/chats/{$chat->id}/messages", [
                'message' => 'Hello passenger!',
                'message_type' => 'text',
            ]);

        // Retrieve messages
        $response = $this->actingAs($passenger, 'sanctum')
            ->getJson("/api/v1/chats/{$chat->id}/messages");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
