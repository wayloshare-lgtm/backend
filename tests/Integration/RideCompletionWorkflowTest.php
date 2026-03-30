<?php

namespace Tests\Integration;

use App\Models\User;
use App\Models\Ride;
use App\Models\RideLocation;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RideCompletionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test complete ride workflow:
     * Request → Accept → Arrive → Start → Track Location → Complete
     */
    public function test_complete_ride_workflow()
    {
        // Step 1: Create driver and passenger
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        // Step 2: Passenger requests a ride
        $rideData = [
            'pickup_location' => 'Bangalore Central',
            'pickup_lat' => 12.9716,
            'pickup_lng' => 77.5946,
            'dropoff_location' => 'Bangalore Airport',
            'dropoff_lat' => 13.1939,
            'dropoff_lng' => 77.7068,
            'departure_date' => now()->format('Y-m-d'),
            'departure_time' => now()->addHour()->format('H:i:s'),
        ];

        $response = $this->actingAs($passenger, 'sanctum')
            ->postJson('/api/v1/rides', $rideData);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $ride = Ride::where('rider_id', $passenger->id)->first();
        $this->assertEquals('requested', $ride->status);

        // Step 3: Driver accepts the ride
        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/accept", []);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $ride->refresh();
        $this->assertEquals('accepted', $ride->status);
        $this->assertNotNull($ride->accepted_at);

        // Step 4: Driver arrives at pickup location
        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/arrive", []);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $ride->refresh();
        $this->assertEquals('arrived', $ride->status);
        $this->assertNotNull($ride->arrived_at);

        // Step 5: Driver starts the ride
        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/start", []);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $ride->refresh();
        $this->assertEquals('in_progress', $ride->status);
        $this->assertNotNull($ride->started_at);

        // Step 6: Driver updates location during ride
        $locations = [
            ['latitude' => 12.9716, 'longitude' => 77.5946, 'accuracy' => 5.0, 'speed' => 0],
            ['latitude' => 12.9750, 'longitude' => 77.6000, 'accuracy' => 5.0, 'speed' => 40],
            ['latitude' => 12.9800, 'longitude' => 77.6050, 'accuracy' => 5.0, 'speed' => 50],
            ['latitude' => 13.1939, 'longitude' => 77.7068, 'accuracy' => 5.0, 'speed' => 0],
        ];

        foreach ($locations as $location) {
            $response = $this->actingAs($driver, 'sanctum')
                ->postJson('/api/v1/locations/update', [
                    'ride_id' => $ride->id,
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                    'accuracy' => $location['accuracy'],
                    'speed' => $location['speed'],
                    'heading' => 45,
                    'altitude' => 920,
                ]);

            $response->assertStatus(200)
                ->assertJson(['success' => true]);
        }

        // Verify location history
        $this->assertDatabaseHas('ride_locations', [
            'ride_id' => $ride->id,
            'latitude' => 12.9716,
        ]);

        $this->assertDatabaseHas('ride_locations', [
            'ride_id' => $ride->id,
            'latitude' => 13.1939,
        ]);

        // Step 7: Retrieve location history
        $response = $this->actingAs($driver, 'sanctum')
            ->getJson("/api/v1/locations/history/{$ride->id}");

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data');

        // Step 8: Complete the ride
        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/complete", [
                'actual_distance_km' => 25.5,
                'actual_duration_minutes' => 45,
                'actual_fare' => 500,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $ride->refresh();
        $this->assertEquals('completed', $ride->status);
        $this->assertNotNull($ride->completed_at);
        $this->assertEquals(25.5, $ride->actual_distance_km);
        $this->assertEquals(45, $ride->actual_duration_minutes);
        $this->assertEquals(500, $ride->actual_fare);
    }

    /**
     * Test ride cancellation workflow
     */
    public function test_ride_cancellation_workflow()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
            'status' => 'requested',
        ]);

        // Driver cancels the ride
        $response = $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/cancel", [
                'cancellation_reason' => 'Vehicle breakdown',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $ride->refresh();
        $this->assertEquals('cancelled', $ride->status);
        $this->assertEquals('Vehicle breakdown', $ride->cancellation_reason);
        $this->assertNotNull($ride->cancelled_at);
    }

    /**
     * Test ride status transitions are valid
     */
    public function test_ride_status_transitions()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
            'status' => 'requested',
        ]);

        // Valid transition: requested → accepted
        $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/accept", []);

        $ride->refresh();
        $this->assertEquals('accepted', $ride->status);

        // Valid transition: accepted → arrived
        $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/arrive", []);

        $ride->refresh();
        $this->assertEquals('arrived', $ride->status);

        // Valid transition: arrived → in_progress
        $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/start", []);

        $ride->refresh();
        $this->assertEquals('in_progress', $ride->status);

        // Valid transition: in_progress → completed
        $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/rides/{$ride->id}/complete", [
                'actual_distance_km' => 25.5,
                'actual_duration_minutes' => 45,
                'actual_fare' => 500,
            ]);

        $ride->refresh();
        $this->assertEquals('completed', $ride->status);
    }

    /**
     * Test location tracking with multiple updates
     */
    public function test_location_tracking_workflow()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
            'status' => 'in_progress',
        ]);

        // Send multiple location updates
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($driver, 'sanctum')
                ->postJson('/api/v1/locations/update', [
                    'ride_id' => $ride->id,
                    'latitude' => 12.9716 + ($i * 0.001),
                    'longitude' => 77.5946 + ($i * 0.001),
                    'accuracy' => 5.0,
                    'speed' => 40 + ($i * 5),
                    'heading' => 45,
                    'altitude' => 920,
                ]);
        }

        // Verify all locations are stored
        $locations = RideLocation::where('ride_id', $ride->id)->get();
        $this->assertCount(5, $locations);

        // Verify location progression
        $firstLocation = $locations->first();
        $lastLocation = $locations->last();

        $this->assertLessThan($lastLocation->latitude, $firstLocation->latitude + 0.01);
        $this->assertLessThan($lastLocation->longitude, $firstLocation->longitude + 0.01);
    }

    /**
     * Test ride details are properly retrieved
     */
    public function test_ride_details_retrieval()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $ride = Ride::factory()->completed()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
            'pickup_location' => 'Bangalore Central',
            'dropoff_location' => 'Bangalore Airport',
            'actual_distance_km' => 25.5,
            'actual_fare' => 500,
        ]);

        $response = $this->actingAs($passenger, 'sanctum')
            ->getJson("/api/v1/rides/{$ride->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.pickup_location', 'Bangalore Central')
            ->assertJsonPath('data.dropoff_location', 'Bangalore Airport')
            ->assertJsonPath('data.actual_distance_km', 25.5)
            ->assertJsonPath('data.actual_fare', 500)
            ->assertJsonPath('data.status', 'completed');
    }

    /**
     * Test ride history retrieval
     */
    public function test_ride_history_retrieval()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        // Create multiple completed rides
        Ride::factory()->completed()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        Ride::factory()->completed()->create([
            'driver_id' => $driver->id,
            'rider_id' => $passenger->id,
        ]);

        // Retrieve ride history
        $response = $this->actingAs($passenger, 'sanctum')
            ->getJson('/api/v1/rides/history');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test ride with all optional fields
     */
    public function test_ride_with_all_optional_fields()
    {
        $driver = User::factory()->create(['user_preference' => 'driver']);
        $passenger = User::factory()->create(['user_preference' => 'passenger']);

        $rideData = [
            'pickup_location' => 'Bangalore Central',
            'pickup_lat' => 12.9716,
            'pickup_lng' => 77.5946,
            'dropoff_location' => 'Bangalore Airport',
            'dropoff_lat' => 13.1939,
            'dropoff_lng' => 77.7068,
            'departure_date' => now()->format('Y-m-d'),
            'departure_time' => now()->addHour()->format('H:i:s'),
            'available_seats' => 3,
            'price_per_seat' => 250,
            'description' => 'Comfortable ride with AC',
            'ac_available' => true,
            'wifi_available' => true,
            'music_preference' => 'pop',
            'smoking_allowed' => false,
        ];

        $response = $this->actingAs($driver, 'sanctum')
            ->postJson('/api/v1/rides/offer', $rideData);

        $response->assertStatus(201);

        $ride = Ride::where('driver_id', $driver->id)->first();
        $this->assertTrue($ride->ac_available);
        $this->assertTrue($ride->wifi_available);
        $this->assertEquals('pop', $ride->music_preference);
        $this->assertFalse($ride->smoking_allowed);
    }
}
