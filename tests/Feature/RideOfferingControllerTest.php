<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ride;
use App\Models\FareSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RideOfferingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = User::factory()->create(['role' => 'driver']);
        
        // Create a default fare setting for tests
        FareSetting::create([
            'base_fare' => 50,
            'per_km_rate' => 15,
            'per_minute_rate' => 2,
            'fuel_surcharge_per_km' => 1,
            'platform_fee_percentage' => 10,
            'toll_enabled' => true,
            'night_multiplier' => 1.5,
            'surge_multiplier' => 1.0,
            'city' => null,
            'is_active' => true,
        ]);
    }

    public function test_offer_ride_successfully()
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/rides/offer', [
                'pickup_location' => 'Downtown Station',
                'pickup_lat' => 12.9716,
                'pickup_lng' => 77.5946,
                'dropoff_location' => 'Airport',
                'dropoff_lat' => 13.1939,
                'dropoff_lng' => 77.7068,
                'estimated_distance_km' => 25.5,
                'estimated_duration_minutes' => 45,
                'available_seats' => 3,
                'price_per_seat' => 250.00,
                'description' => 'Comfortable sedan with AC',
                'ac_available' => true,
                'wifi_available' => false,
                'music_preference' => 'Bollywood',
                'smoking_allowed' => false,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Ride offered successfully')
            ->assertJsonPath('ride.driver_id', $this->driver->id)
            ->assertJsonPath('ride.available_seats', 3)
            ->assertJsonPath('ride.status', 'offered')
            ->assertJsonPath('ride.ac_available', true)
            ->assertJsonPath('ride.wifi_available', false)
            ->assertJsonPath('ride.smoking_allowed', false);

        // Check price_per_seat separately since it's a float
        $this->assertEquals(250.0, $response->json('ride.price_per_seat'));

        $this->assertDatabaseHas('rides', [
            'driver_id' => $this->driver->id,
            'available_seats' => 3,
            'price_per_seat' => 250.00,
            'status' => 'offered',
            'ac_available' => true,
            'wifi_available' => false,
            'smoking_allowed' => false,
        ]);
    }

    public function test_offer_ride_with_minimal_fields()
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/rides/offer', [
                'pickup_location' => 'Location A',
                'pickup_lat' => 12.9716,
                'pickup_lng' => 77.5946,
                'dropoff_location' => 'Location B',
                'dropoff_lat' => 13.1939,
                'dropoff_lng' => 77.7068,
                'estimated_distance_km' => 10.0,
                'estimated_duration_minutes' => 20,
                'available_seats' => 1,
                'price_per_seat' => 100.00,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('ride.available_seats', 1);

        // Check price_per_seat separately since it's a float
        $this->assertEquals(100.0, $response->json('ride.price_per_seat'));
    }

    public function test_offer_ride_with_invalid_available_seats()
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/rides/offer', [
                'pickup_location' => 'Location A',
                'pickup_lat' => 12.9716,
                'pickup_lng' => 77.5946,
                'dropoff_location' => 'Location B',
                'dropoff_lat' => 13.1939,
                'dropoff_lng' => 77.7068,
                'estimated_distance_km' => 10.0,
                'estimated_duration_minutes' => 20,
                'available_seats' => 10, // Invalid: max 8
                'price_per_seat' => 100.00,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Validation failed');
    }

    public function test_offer_ride_with_invalid_price_per_seat()
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/rides/offer', [
                'pickup_location' => 'Location A',
                'pickup_lat' => 12.9716,
                'pickup_lng' => 77.5946,
                'dropoff_location' => 'Location B',
                'dropoff_lat' => 13.1939,
                'dropoff_lng' => 77.7068,
                'estimated_distance_km' => 10.0,
                'estimated_duration_minutes' => 20,
                'available_seats' => 3,
                'price_per_seat' => 15000.00, // Invalid: max 10000
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Validation failed');
    }

    public function test_offer_ride_with_invalid_coordinates()
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/rides/offer', [
                'pickup_location' => 'Location A',
                'pickup_lat' => 95.0, // Invalid: > 90
                'pickup_lng' => 77.5946,
                'dropoff_location' => 'Location B',
                'dropoff_lat' => 13.1939,
                'dropoff_lng' => 77.7068,
                'estimated_distance_km' => 10.0,
                'estimated_duration_minutes' => 20,
                'available_seats' => 3,
                'price_per_seat' => 100.00,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_offer_ride_requires_driver_role()
    {
        $passenger = User::factory()->create(['role' => 'rider']);

        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson('/api/v1/rides/offer', [
                'pickup_location' => 'Location A',
                'pickup_lat' => 12.9716,
                'pickup_lng' => 77.5946,
                'dropoff_location' => 'Location B',
                'dropoff_lat' => 13.1939,
                'dropoff_lng' => 77.7068,
                'estimated_distance_km' => 10.0,
                'estimated_duration_minutes' => 20,
                'available_seats' => 3,
                'price_per_seat' => 100.00,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Unauthorized');
    }

    public function test_offer_ride_requires_authentication()
    {
        $response = $this->postJson('/api/v1/rides/offer', [
            'pickup_location' => 'Location A',
            'pickup_lat' => 12.9716,
            'pickup_lng' => 77.5946,
            'dropoff_location' => 'Location B',
            'dropoff_lat' => 13.1939,
            'dropoff_lng' => 77.7068,
            'estimated_distance_km' => 10.0,
            'estimated_duration_minutes' => 20,
            'available_seats' => 3,
            'price_per_seat' => 100.00,
        ]);

        $response->assertStatus(401);
    }

    public function test_offer_ride_with_json_preferences()
    {
        $preferences = json_encode(['music_genre' => 'classical', 'temperature' => 22]);

        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/rides/offer', [
                'pickup_location' => 'Location A',
                'pickup_lat' => 12.9716,
                'pickup_lng' => 77.5946,
                'dropoff_location' => 'Location B',
                'dropoff_lat' => 13.1939,
                'dropoff_lng' => 77.7068,
                'estimated_distance_km' => 10.0,
                'estimated_duration_minutes' => 20,
                'available_seats' => 3,
                'price_per_seat' => 100.00,
                'preferences' => $preferences,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('ride.preferences.music_genre', 'classical')
            ->assertJsonPath('ride.preferences.temperature', 22);
    }

    public function test_offer_ride_with_zero_available_seats_fails()
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/rides/offer', [
                'pickup_location' => 'Location A',
                'pickup_lat' => 12.9716,
                'pickup_lng' => 77.5946,
                'dropoff_location' => 'Location B',
                'dropoff_lat' => 13.1939,
                'dropoff_lng' => 77.7068,
                'estimated_distance_km' => 10.0,
                'estimated_duration_minutes' => 20,
                'available_seats' => 0, // Invalid: min 1
                'price_per_seat' => 100.00,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_offer_ride_with_zero_price_fails()
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/rides/offer', [
                'pickup_location' => 'Location A',
                'pickup_lat' => 12.9716,
                'pickup_lng' => 77.5946,
                'dropoff_location' => 'Location B',
                'dropoff_lat' => 13.1939,
                'dropoff_lng' => 77.7068,
                'estimated_distance_km' => 10.0,
                'estimated_duration_minutes' => 20,
                'available_seats' => 3,
                'price_per_seat' => 0, // Invalid: min 0.01
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_update_ride_status_from_requested_to_accepted()
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'status' => 'requested',
        ]);

        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/update-status", [
                'status' => 'accepted',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Ride status updated successfully')
            ->assertJsonPath('ride.status', 'accepted')
            ->assertJsonPath('ride.driver_id', $driver->id);

        $this->assertDatabaseHas('rides', [
            'id' => $ride->id,
            'status' => 'accepted',
            'driver_id' => $driver->id,
        ]);
    }

    public function test_update_ride_status_from_accepted_to_arrived()
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/update-status", [
                'status' => 'arrived',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('ride.status', 'arrived');

        $this->assertDatabaseHas('rides', [
            'id' => $ride->id,
            'status' => 'arrived',
        ]);
    }

    public function test_update_ride_status_from_arrived_to_started()
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'status' => 'arrived',
        ]);

        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/update-status", [
                'status' => 'started',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('ride.status', 'started');

        $this->assertDatabaseHas('rides', [
            'id' => $ride->id,
            'status' => 'started',
        ]);
    }

    public function test_update_ride_status_from_started_to_completed()
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'status' => 'started',
        ]);

        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/update-status", [
                'status' => 'completed',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('ride.status', 'completed');

        $this->assertDatabaseHas('rides', [
            'id' => $ride->id,
            'status' => 'completed',
        ]);
    }

    public function test_update_ride_status_from_requested_to_cancelled()
    {
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'status' => 'requested',
        ]);

        $response = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/update-status", [
                'status' => 'cancelled',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('ride.status', 'cancelled');

        $this->assertDatabaseHas('rides', [
            'id' => $ride->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_update_ride_status_invalid_transition()
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/update-status", [
                'status' => 'cancelled',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Invalid ride status transition');
    }

    public function test_update_ride_status_invalid_status_value()
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/update-status", [
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Validation failed');
    }

    public function test_update_ride_status_missing_status_field()
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/update-status", []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Validation failed');
    }

    public function test_update_ride_status_requires_authentication()
    {
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'status' => 'requested',
        ]);

        $response = $this->postJson("/api/v1/rides/{$ride->id}/update-status", [
            'status' => 'cancelled',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_ride_status_sets_correct_timestamp()
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'status' => 'requested',
            'accepted_at' => null,
        ]);

        $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/update-status", [
                'status' => 'accepted',
            ]);

        $updatedRide = Ride::find($ride->id);
        $this->assertNotNull($updatedRide->accepted_at);
    }

    public function test_update_ride_status_from_accepted_to_cancelled()
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/update-status", [
                'status' => 'cancelled',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('ride.status', 'cancelled');

        $this->assertDatabaseHas('rides', [
            'id' => $ride->id,
            'status' => 'cancelled',
        ]);
    }

    // Ride Cancellation Endpoint Tests

    public function test_cancel_ride_from_requested_status()
    {
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'status' => 'requested',
            'cancellation_reason' => null,
            'cancelled_at' => null,
        ]);

        $response = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/cancel", [
                'reason' => 'Driver taking too long',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Ride cancelled successfully')
            ->assertJsonPath('ride.status', 'cancelled')
            ->assertJsonPath('ride.cancellation_reason', 'Driver taking too long');

        $this->assertDatabaseHas('rides', [
            'id' => $ride->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Driver taking too long',
        ]);

        $ride->refresh();
        $this->assertNotNull($ride->cancelled_at);
    }

    public function test_cancel_ride_from_accepted_status()
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'status' => 'accepted',
            'cancellation_reason' => null,
            'cancelled_at' => null,
        ]);

        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/cancel", [
                'reason' => 'Emergency situation',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('ride.status', 'cancelled')
            ->assertJsonPath('ride.cancellation_reason', 'Emergency situation');

        $this->assertDatabaseHas('rides', [
            'id' => $ride->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Emergency situation',
        ]);
    }

    public function test_cancel_ride_requires_reason()
    {
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'status' => 'requested',
        ]);

        $response = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/cancel", []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Validation failed');
    }

    public function test_cancel_ride_reason_max_length()
    {
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'status' => 'requested',
        ]);

        $longReason = str_repeat('a', 501); // Exceeds max 500

        $response = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/cancel", [
                'reason' => $longReason,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_cancel_ride_cannot_cancel_completed_ride()
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/cancel", [
                'reason' => 'Changed mind',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Cannot cancel ride');

        $this->assertDatabaseHas('rides', [
            'id' => $ride->id,
            'status' => 'completed',
        ]);
    }

    public function test_cancel_ride_cannot_cancel_already_cancelled_ride()
    {
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Already cancelled',
        ]);

        $response = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/cancel", [
                'reason' => 'Another reason',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Cannot cancel ride');
    }

    public function test_cancel_ride_cannot_cancel_started_ride()
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'status' => 'started',
        ]);

        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/cancel", [
                'reason' => 'Changed mind',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Cannot cancel ride');
    }

    public function test_cancel_ride_requires_authentication()
    {
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'status' => 'requested',
        ]);

        $response = $this->postJson("/api/v1/rides/{$ride->id}/cancel", [
            'reason' => 'Changed mind',
        ]);

        $response->assertStatus(401);
    }

    public function test_cancel_ride_with_max_length_reason()
    {
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'status' => 'requested',
        ]);

        $maxReason = str_repeat('a', 500); // Exactly max 500

        $response = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/cancel", [
                'reason' => $maxReason,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('ride.status', 'cancelled');
    }

    public function test_cancel_ride_sets_cancelled_at_timestamp()
    {
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'status' => 'requested',
            'cancelled_at' => null,
        ]);

        $this->actingAs($rider, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/cancel", [
                'reason' => 'Changed mind',
            ]);

        $updatedRide = Ride::find($ride->id);
        $this->assertNotNull($updatedRide->cancelled_at);
    }

    public function test_cancel_ride_returns_full_ride_details()
    {
        $driver = User::factory()->create(['role' => 'driver']);
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'status' => 'accepted',
            'pickup_location' => 'Downtown',
            'dropoff_location' => 'Airport',
            'estimated_distance_km' => 25.5,
            'estimated_fare' => 500.00,
        ]);

        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/cancel", [
                'reason' => 'Emergency',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('ride.id', $ride->id)
            ->assertJsonPath('ride.pickup_location', 'Downtown')
            ->assertJsonPath('ride.dropoff_location', 'Airport')
            ->assertJsonPath('ride.status', 'cancelled');
    }

    public function test_cancel_ride_with_special_characters_in_reason()
    {
        $rider = User::factory()->create(['role' => 'rider']);
        
        $ride = Ride::factory()->create([
            'rider_id' => $rider->id,
            'status' => 'requested',
        ]);

        $specialReason = "Driver didn't respond! @#$%^&*()";

        $response = $this->actingAs($rider, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/cancel", [
                'reason' => $specialReason,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('ride.cancellation_reason', $specialReason);
    }
}
