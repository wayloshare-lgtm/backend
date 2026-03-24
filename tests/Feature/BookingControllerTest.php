<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $driver;
    protected User $passenger;
    protected Ride $ride;

    protected function setUp(): void
    {
        parent::setUp();

        // Create driver and passenger users
        $this->driver = User::factory()->create();
        $this->passenger = User::factory()->create();

        // Create a ride
        $this->ride = Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'rider_id' => $this->passenger->id,
            'status' => 'requested',
        ]);
    }

    public function test_create_booking_with_valid_seats_booked(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/bookings', [
            'ride_id' => $this->ride->id,
            'seats_booked' => 2,
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('booking.seats_booked', 2);

        $this->assertDatabaseHas('bookings', [
            'ride_id' => $this->ride->id,
            'passenger_id' => $this->passenger->id,
            'seats_booked' => 2,
        ]);
    }

    public function test_create_booking_with_minimum_seats(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/bookings', [
            'ride_id' => $this->ride->id,
            'seats_booked' => 1,
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('booking.seats_booked', 1);
    }

    public function test_create_booking_with_maximum_seats(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/bookings', [
            'ride_id' => $this->ride->id,
            'seats_booked' => 8,
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('booking.seats_booked', 8);
    }

    public function test_create_booking_with_zero_seats_fails(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/bookings', [
            'ride_id' => $this->ride->id,
            'seats_booked' => 0,
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_create_booking_with_more_than_eight_seats_fails(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/bookings', [
            'ride_id' => $this->ride->id,
            'seats_booked' => 9,
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_create_booking_without_seats_booked_fails(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/bookings', [
            'ride_id' => $this->ride->id,
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_booking_response_includes_seats_booked(): void
    {
        $booking = Booking::factory()->create([
            'ride_id' => $this->ride->id,
            'passenger_id' => $this->passenger->id,
            'seats_booked' => 3,
        ]);

        $response = $this->actingAs($this->passenger)->getJson("/api/v1/bookings/{$booking->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('booking.seats_booked', 3);
    }

    public function test_create_booking_with_special_instructions(): void
    {
        $specialInstructions = 'Please wait at the main entrance. I will be wearing a blue jacket.';
        
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/bookings', [
            'ride_id' => $this->ride->id,
            'seats_booked' => 2,
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
            'special_instructions' => $specialInstructions,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('booking.special_instructions', $specialInstructions);

        $this->assertDatabaseHas('bookings', [
            'ride_id' => $this->ride->id,
            'passenger_id' => $this->passenger->id,
            'special_instructions' => $specialInstructions,
        ]);
    }

    public function test_create_booking_with_null_special_instructions(): void
    {
        $response = $this->actingAs($this->passenger)->postJson('/api/v1/bookings', [
            'ride_id' => $this->ride->id,
            'seats_booked' => 2,
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
            'special_instructions' => null,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('booking.special_instructions', null);
    }

    public function test_booking_details_includes_special_instructions(): void
    {
        $specialInstructions = 'Call me when you arrive';
        
        $booking = Booking::factory()->create([
            'ride_id' => $this->ride->id,
            'passenger_id' => $this->passenger->id,
            'seats_booked' => 2,
            'special_instructions' => $specialInstructions,
        ]);

        $response = $this->actingAs($this->passenger)->getJson("/api/v1/bookings/{$booking->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('booking.special_instructions', $specialInstructions);
    }

    public function test_cancel_booking_successfully(): void
    {
        $booking = Booking::factory()->create([
            'ride_id' => $this->ride->id,
            'passenger_id' => $this->passenger->id,
            'booking_status' => 'pending',
        ]);

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/bookings/{$booking->id}/cancel",
            ['cancellation_reason' => 'Changed my mind']
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('booking.booking_status', 'cancelled');
        $response->assertJsonPath('booking.cancellation_reason', 'Changed my mind');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'booking_status' => 'cancelled',
            'cancellation_reason' => 'Changed my mind',
        ]);
    }

    public function test_cancel_booking_without_reason(): void
    {
        $booking = Booking::factory()->create([
            'ride_id' => $this->ride->id,
            'passenger_id' => $this->passenger->id,
            'booking_status' => 'pending',
        ]);

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/bookings/{$booking->id}/cancel"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('booking.booking_status', 'cancelled');
    }

    public function test_cancel_booking_unauthorized(): void
    {
        $otherUser = User::factory()->create();
        $booking = Booking::factory()->create([
            'ride_id' => $this->ride->id,
            'passenger_id' => $this->passenger->id,
            'booking_status' => 'pending',
        ]);

        $response = $this->actingAs($otherUser)->postJson(
            "/api/v1/bookings/{$booking->id}/cancel"
        );

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }

    public function test_cancel_already_cancelled_booking(): void
    {
        $booking = Booking::factory()->create([
            'ride_id' => $this->ride->id,
            'passenger_id' => $this->passenger->id,
            'booking_status' => 'cancelled',
        ]);

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/bookings/{$booking->id}/cancel"
        );

        $response->assertStatus(409);
        $response->assertJsonPath('success', false);
    }

    public function test_cancel_completed_booking(): void
    {
        $booking = Booking::factory()->create([
            'ride_id' => $this->ride->id,
            'passenger_id' => $this->passenger->id,
            'booking_status' => 'completed',
        ]);

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/bookings/{$booking->id}/cancel"
        );

        $response->assertStatus(409);
        $response->assertJsonPath('success', false);
    }

    public function test_cancel_booking_with_long_reason(): void
    {
        $booking = Booking::factory()->create([
            'ride_id' => $this->ride->id,
            'passenger_id' => $this->passenger->id,
            'booking_status' => 'pending',
        ]);

        $longReason = str_repeat('a', 500);

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/bookings/{$booking->id}/cancel",
            ['cancellation_reason' => $longReason]
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function test_cancel_booking_with_reason_exceeding_max_length(): void
    {
        $booking = Booking::factory()->create([
            'ride_id' => $this->ride->id,
            'passenger_id' => $this->passenger->id,
            'booking_status' => 'pending',
        ]);

        $tooLongReason = str_repeat('a', 501);

        $response = $this->actingAs($this->passenger)->postJson(
            "/api/v1/bookings/{$booking->id}/cancel",
            ['cancellation_reason' => $tooLongReason]
        );

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_get_booking_history_successfully(): void
    {
        // Create multiple bookings for the passenger
        $booking1 = Booking::factory()->create([
            'ride_id' => $this->ride->id,
            'passenger_id' => $this->passenger->id,
            'booking_status' => 'completed',
        ]);

        $ride2 = Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'rider_id' => $this->passenger->id,
            'status' => 'requested',
        ]);

        $booking2 = Booking::factory()->create([
            'ride_id' => $ride2->id,
            'passenger_id' => $this->passenger->id,
            'booking_status' => 'pending',
        ]);

        $response = $this->actingAs($this->passenger)->getJson('/api/v1/bookings/history');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('pagination.total', 2);
        $this->assertCount(2, $response->json('bookings'));
    }

    public function test_get_booking_history_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/bookings/history');

        $response->assertStatus(401);
    }

    public function test_get_booking_history_with_pagination(): void
    {
        // Create 20 bookings for the passenger
        for ($i = 0; $i < 20; $i++) {
            $ride = Ride::factory()->create([
                'driver_id' => $this->driver->id,
                'rider_id' => $this->passenger->id,
                'status' => 'requested',
            ]);

            Booking::factory()->create([
                'ride_id' => $ride->id,
                'passenger_id' => $this->passenger->id,
                'booking_status' => 'completed',
            ]);
        }

        // Test default pagination (15 per page)
        $response = $this->actingAs($this->passenger)->getJson('/api/v1/bookings/history');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('pagination.total', 20);
        $response->assertJsonPath('pagination.per_page', 15);
        $response->assertJsonPath('pagination.current_page', 1);
        $response->assertJsonPath('pagination.last_page', 2);
        $this->assertCount(15, $response->json('bookings'));
    }

    public function test_get_booking_history_with_custom_per_page(): void
    {
        // Create 10 bookings for the passenger
        for ($i = 0; $i < 10; $i++) {
            $ride = Ride::factory()->create([
                'driver_id' => $this->driver->id,
                'rider_id' => $this->passenger->id,
                'status' => 'requested',
            ]);

            Booking::factory()->create([
                'ride_id' => $ride->id,
                'passenger_id' => $this->passenger->id,
                'booking_status' => 'completed',
            ]);
        }

        // Test custom per_page parameter
        $response = $this->actingAs($this->passenger)->getJson('/api/v1/bookings/history?per_page=5');

        $response->assertStatus(200);
        $response->assertJsonPath('pagination.per_page', 5);
        $response->assertJsonPath('pagination.last_page', 2);
        $this->assertCount(5, $response->json('bookings'));
    }

    public function test_get_booking_history_with_status_filter(): void
    {
        // Create bookings with different statuses
        $booking1 = Booking::factory()->create([
            'ride_id' => $this->ride->id,
            'passenger_id' => $this->passenger->id,
            'booking_status' => 'completed',
        ]);

        $ride2 = Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'rider_id' => $this->passenger->id,
            'status' => 'requested',
        ]);

        $booking2 = Booking::factory()->create([
            'ride_id' => $ride2->id,
            'passenger_id' => $this->passenger->id,
            'booking_status' => 'pending',
        ]);

        $ride3 = Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'rider_id' => $this->passenger->id,
            'status' => 'requested',
        ]);

        $booking3 = Booking::factory()->create([
            'ride_id' => $ride3->id,
            'passenger_id' => $this->passenger->id,
            'booking_status' => 'cancelled',
        ]);

        // Filter by completed status
        $response = $this->actingAs($this->passenger)->getJson('/api/v1/bookings/history?status=completed');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('pagination.total', 1);
        $this->assertCount(1, $response->json('bookings'));
        $this->assertEquals('completed', $response->json('bookings.0.booking_status'));
    }

    public function test_get_booking_history_ordered_by_created_at_descending(): void
    {
        // Create bookings with different timestamps
        $booking1 = Booking::factory()->create([
            'ride_id' => $this->ride->id,
            'passenger_id' => $this->passenger->id,
            'booking_status' => 'completed',
            'created_at' => now()->subDays(2),
        ]);

        $ride2 = Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'rider_id' => $this->passenger->id,
            'status' => 'requested',
        ]);

        $booking2 = Booking::factory()->create([
            'ride_id' => $ride2->id,
            'passenger_id' => $this->passenger->id,
            'booking_status' => 'completed',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->passenger)->getJson('/api/v1/bookings/history');

        $response->assertStatus(200);
        $bookings = $response->json('bookings');
        // Most recent booking should be first
        $this->assertEquals($booking2->id, $bookings[0]['id']);
        $this->assertEquals($booking1->id, $bookings[1]['id']);
    }

    public function test_get_booking_history_includes_ride_and_passenger_data(): void
    {
        $booking = Booking::factory()->create([
            'ride_id' => $this->ride->id,
            'passenger_id' => $this->passenger->id,
            'booking_status' => 'completed',
        ]);

        $response = $this->actingAs($this->passenger)->getJson('/api/v1/bookings/history');

        $response->assertStatus(200);
        $bookings = $response->json('bookings');
        $this->assertNotNull($bookings[0]['id']);
        $this->assertNotNull($bookings[0]['ride_id']);
        $this->assertNotNull($bookings[0]['passenger_id']);
        $this->assertNotNull($bookings[0]['booking_status']);
    }

    public function test_get_booking_history_empty_for_new_user(): void
    {
        $newUser = User::factory()->create();

        $response = $this->actingAs($newUser)->getJson('/api/v1/bookings/history');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('pagination.total', 0);
        $this->assertCount(0, $response->json('bookings'));
    }

    public function test_get_booking_history_only_shows_user_bookings(): void
    {
        // Create a booking for the passenger
        $booking1 = Booking::factory()->create([
            'ride_id' => $this->ride->id,
            'passenger_id' => $this->passenger->id,
            'booking_status' => 'completed',
        ]);

        // Create a booking for another user
        $otherUser = User::factory()->create();
        $ride2 = Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'rider_id' => $otherUser->id,
            'status' => 'requested',
        ]);

        $booking2 = Booking::factory()->create([
            'ride_id' => $ride2->id,
            'passenger_id' => $otherUser->id,
            'booking_status' => 'completed',
        ]);

        // Get history for the passenger
        $response = $this->actingAs($this->passenger)->getJson('/api/v1/bookings/history');

        $response->assertStatus(200);
        $response->assertJsonPath('pagination.total', 1);
        $this->assertCount(1, $response->json('bookings'));
        $this->assertEquals($booking1->id, $response->json('bookings.0.id'));
    }
}
