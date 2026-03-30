<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ride;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RideDetailsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $driver;
    protected User $rider;
    protected Ride $ride;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = User::factory()->create(['role' => 'driver']);
        $this->rider = User::factory()->create(['role' => 'rider']);
        
        $this->ride = Ride::factory()->create([
            'rider_id' => $this->rider->id,
            'driver_id' => $this->driver->id,
            'pickup_location' => 'Downtown Station',
            'pickup_lat' => 12.9716,
            'pickup_lng' => 77.5946,
            'dropoff_location' => 'Airport',
            'dropoff_lat' => 13.1939,
            'dropoff_lng' => 77.7068,
            'estimated_distance_km' => 25.5,
            'estimated_duration_minutes' => 45,
            'estimated_fare' => 500.00,
            'actual_distance_km' => 26.0,
            'actual_duration_minutes' => 48,
            'actual_fare' => 520.00,
            'toll_amount' => 50.00,
            'status' => 'completed',
            'available_seats' => 3,
            'price_per_seat' => 250.00,
            'description' => 'Comfortable sedan with AC',
            'ac_available' => true,
            'wifi_available' => false,
            'music_preference' => 'Bollywood',
            'smoking_allowed' => false,
        ]);
    }

    public function test_get_ride_details_as_rider()
    {
        $response = $this->actingAs($this->rider, 'sanctum')
            ->getJson("/api/v1/rides/{$this->ride->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('ride.id', $this->ride->id)
            ->assertJsonPath('ride.rider_id', $this->rider->id)
            ->assertJsonPath('ride.driver_id', $this->driver->id)
            ->assertJsonPath('ride.pickup_location', 'Downtown Station')
            ->assertJsonPath('ride.pickup_lat', 12.9716)
            ->assertJsonPath('ride.pickup_lng', 77.5946)
            ->assertJsonPath('ride.dropoff_location', 'Airport')
            ->assertJsonPath('ride.dropoff_lat', 13.1939)
            ->assertJsonPath('ride.dropoff_lng', 77.7068)
            ->assertJsonPath('ride.estimated_distance_km', 25.5)
            ->assertJsonPath('ride.estimated_duration_minutes', 45)
            ->assertJsonPath('ride.estimated_fare', 500)
            ->assertJsonPath('ride.actual_distance_km', 26)
            ->assertJsonPath('ride.actual_duration_minutes', 48)
            ->assertJsonPath('ride.actual_fare', 520)
            ->assertJsonPath('ride.toll_amount', 50)
            ->assertJsonPath('ride.status', 'completed')
            ->assertJsonPath('ride.available_seats', 3)
            ->assertJsonPath('ride.price_per_seat', 250)
            ->assertJsonPath('ride.description', 'Comfortable sedan with AC')
            ->assertJsonPath('ride.ac_available', true)
            ->assertJsonPath('ride.wifi_available', false)
            ->assertJsonPath('ride.music_preference', 'Bollywood')
            ->assertJsonPath('ride.smoking_allowed', false);
    }

    public function test_get_ride_details_as_driver()
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson("/api/v1/rides/{$this->ride->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('ride.id', $this->ride->id)
            ->assertJsonPath('ride.rider_id', $this->rider->id)
            ->assertJsonPath('ride.driver_id', $this->driver->id)
            ->assertJsonPath('ride.status', 'completed');
    }

    public function test_get_ride_details_unauthorized_user()
    {
        $otherUser = User::factory()->create(['role' => 'rider']);

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/rides/{$this->ride->id}");

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Unauthorized');
    }

    public function test_get_ride_details_unauthenticated()
    {
        $response = $this->getJson("/api/v1/rides/{$this->ride->id}");

        $response->assertStatus(401);
    }

    public function test_get_ride_details_not_found()
    {
        $response = $this->actingAs($this->rider, 'sanctum')
            ->getJson('/api/v1/rides/99999');

        $response->assertStatus(404);
    }

    public function test_get_ride_details_with_null_actual_values()
    {
        $ride = Ride::factory()->create([
            'rider_id' => $this->rider->id,
            'driver_id' => $this->driver->id,
            'status' => 'requested',
            'actual_distance_km' => null,
            'actual_fare' => null,
        ]);

        $response = $this->actingAs($this->rider, 'sanctum')
            ->getJson("/api/v1/rides/{$ride->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('ride.actual_distance_km', null)
            ->assertJsonPath('ride.actual_fare', null);
    }

    public function test_get_ride_details_with_cancellation_reason()
    {
        $ride = Ride::factory()->create([
            'rider_id' => $this->rider->id,
            'driver_id' => $this->driver->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Driver cancelled due to emergency',
        ]);

        $response = $this->actingAs($this->rider, 'sanctum')
            ->getJson("/api/v1/rides/{$ride->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('ride.status', 'cancelled')
            ->assertJsonPath('ride.cancellation_reason', 'Driver cancelled due to emergency');
    }

    public function test_get_ride_details_with_json_preferences()
    {
        $preferences = ['music_genre' => 'classical', 'temperature' => 22];
        $ride = Ride::factory()->create([
            'rider_id' => $this->rider->id,
            'driver_id' => $this->driver->id,
            'preferences' => $preferences,
        ]);

        $response = $this->actingAs($this->rider, 'sanctum')
            ->getJson("/api/v1/rides/{$ride->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('ride.preferences.music_genre', 'classical')
            ->assertJsonPath('ride.preferences.temperature', 22);
    }

    public function test_get_ride_details_includes_all_timestamps()
    {
        $ride = Ride::factory()->create([
            'rider_id' => $this->rider->id,
            'driver_id' => $this->driver->id,
            'status' => 'completed',
            'requested_at' => now()->subHours(2),
            'accepted_at' => now()->subHours(1.5),
            'arrived_at' => now()->subHours(1),
            'started_at' => now()->subMinutes(50),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->rider, 'sanctum')
            ->getJson("/api/v1/rides/{$ride->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
        
        // Verify all timestamp fields are present and not null
        $data = $response->json('ride');
        $this->assertNotNull($data['requested_at']);
        $this->assertNotNull($data['accepted_at']);
        $this->assertNotNull($data['arrived_at']);
        $this->assertNotNull($data['started_at']);
        $this->assertNotNull($data['completed_at']);
    }

    public function test_get_ride_details_response_structure()
    {
        $response = $this->actingAs($this->rider, 'sanctum')
            ->getJson("/api/v1/rides/{$this->ride->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'ride' => [
                    'id',
                    'rider_id',
                    'driver_id',
                    'pickup_location',
                    'pickup_lat',
                    'pickup_lng',
                    'dropoff_location',
                    'dropoff_lat',
                    'dropoff_lng',
                    'estimated_distance_km',
                    'estimated_duration_minutes',
                    'estimated_fare',
                    'actual_distance_km',
                    'actual_duration_minutes',
                    'actual_fare',
                    'toll_amount',
                    'status',
                    'cancellation_reason',
                    'requested_at',
                    'accepted_at',
                    'arrived_at',
                    'started_at',
                    'completed_at',
                    'cancelled_at',
                    'available_seats',
                    'price_per_seat',
                    'description',
                    'preferences',
                    'ac_available',
                    'wifi_available',
                    'music_preference',
                    'smoking_allowed',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_get_ride_details_with_offered_status()
    {
        $ride = Ride::factory()->create([
            'rider_id' => null,
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'available_seats' => 4,
            'price_per_seat' => 300.00,
        ]);

        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson("/api/v1/rides/{$ride->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('ride.status', 'offered')
            ->assertJsonPath('ride.available_seats', 4)
            ->assertJsonPath('ride.price_per_seat', 300);
    }

    public function test_get_ride_details_coordinates_are_floats()
    {
        $response = $this->actingAs($this->rider, 'sanctum')
            ->getJson("/api/v1/rides/{$this->ride->id}");

        $response->assertStatus(200);
        
        $data = $response->json('ride');
        $this->assertIsFloat($data['pickup_lat']);
        $this->assertIsFloat($data['pickup_lng']);
        $this->assertIsFloat($data['dropoff_lat']);
        $this->assertIsFloat($data['dropoff_lng']);
    }

    public function test_get_ride_details_fare_values_are_floats()
    {
        $response = $this->actingAs($this->rider, 'sanctum')
            ->getJson("/api/v1/rides/{$this->ride->id}");

        $response->assertStatus(200);
        
        $data = $response->json('ride');
        // JSON numbers are returned as integers or floats depending on the value
        $this->assertTrue(is_numeric($data['estimated_fare']));
        $this->assertTrue(is_numeric($data['actual_fare']));
        $this->assertTrue(is_numeric($data['toll_amount']));
        $this->assertTrue(is_numeric($data['price_per_seat']));
    }
}
