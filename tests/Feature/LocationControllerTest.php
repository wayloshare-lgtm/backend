<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ride;
use App\Models\RideLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $driver;
    private User $rider;
    private Ride $ride;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = User::factory()->create();
        $this->rider = User::factory()->create();
        $this->ride = Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'rider_id' => $this->rider->id,
            'status' => 'started',
        ]);
    }

    /**
     * Test updating location successfully
     */
    public function test_update_location_creates_location_record(): void
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/locations/update', [
                'ride_id' => $this->ride->id,
                'latitude' => 28.6139,
                'longitude' => 77.2090,
                'accuracy' => 5.0,
                'speed' => 25.5,
                'heading' => 180.0,
                'altitude' => 100.0,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Location updated successfully',
            ]);

        $this->assertDatabaseHas('ride_locations', [
            'ride_id' => $this->ride->id,
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'accuracy' => 5.0,
            'speed' => 25.5,
            'heading' => 180.0,
            'altitude' => 100.0,
        ]);
    }

    /**
     * Test updating location with minimal fields
     */
    public function test_update_location_with_minimal_fields(): void
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/locations/update', [
                'ride_id' => $this->ride->id,
                'latitude' => 28.6139,
                'longitude' => 77.2090,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Location updated successfully',
            ]);

        $this->assertDatabaseHas('ride_locations', [
            'ride_id' => $this->ride->id,
            'latitude' => 28.6139,
            'longitude' => 77.2090,
        ]);
    }

    /**
     * Test update location requires authentication
     */
    public function test_update_location_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/locations/update', [
            'ride_id' => $this->ride->id,
            'latitude' => 28.6139,
            'longitude' => 77.2090,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test update location validates latitude
     */
    public function test_update_location_validates_latitude(): void
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/locations/update', [
                'ride_id' => $this->ride->id,
                'latitude' => 91.0,
                'longitude' => 77.2090,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test update location validates longitude
     */
    public function test_update_location_validates_longitude(): void
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/locations/update', [
                'ride_id' => $this->ride->id,
                'latitude' => 28.6139,
                'longitude' => 181.0,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test update location requires ride_id
     */
    public function test_update_location_requires_ride_id(): void
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/locations/update', [
                'latitude' => 28.6139,
                'longitude' => 77.2090,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test update location requires valid ride_id
     */
    public function test_update_location_requires_valid_ride_id(): void
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/locations/update', [
                'ride_id' => 9999,
                'latitude' => 28.6139,
                'longitude' => 77.2090,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test update location authorization - only driver can update
     */
    public function test_update_location_authorization_only_driver_can_update(): void
    {
        $response = $this->actingAs($this->rider, 'sanctum')
            ->postJson('/api/v1/locations/update', [
                'ride_id' => $this->ride->id,
                'latitude' => 28.6139,
                'longitude' => 77.2090,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'Unauthorized',
            ]);
    }

    /**
     * Test get location history
     */
    public function test_get_location_history(): void
    {
        RideLocation::factory()->count(5)->create([
            'ride_id' => $this->ride->id,
        ]);

        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson("/api/v1/locations/history/{$this->ride->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'ride_id',
                        'latitude',
                        'longitude',
                        'accuracy',
                        'speed',
                        'heading',
                        'altitude',
                        'timestamp',
                        'created_at',
                    ],
                ],
                'pagination' => [
                    'total',
                    'limit',
                    'offset',
                ],
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /**
     * Test get location history requires authentication
     */
    public function test_get_location_history_requires_authentication(): void
    {
        $response = $this->getJson("/api/v1/locations/history/{$this->ride->id}");

        $response->assertStatus(401);
    }

    /**
     * Test get location history for non-existent ride
     */
    public function test_get_location_history_for_non_existent_ride(): void
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson('/api/v1/locations/history/9999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => 'Ride not found',
            ]);
    }

    /**
     * Test get location history authorization - only rider or driver can view
     */
    public function test_get_location_history_authorization(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/locations/history/{$this->ride->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'Unauthorized',
            ]);
    }

    /**
     * Test get location history with pagination
     */
    public function test_get_location_history_with_pagination(): void
    {
        RideLocation::factory()->count(15)->create([
            'ride_id' => $this->ride->id,
        ]);

        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson("/api/v1/locations/history/{$this->ride->id}?limit=5&offset=0");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'pagination' => [
                    'total' => 15,
                    'limit' => 5,
                    'offset' => 0,
                ],
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /**
     * Test get location history rider can view
     */
    public function test_get_location_history_rider_can_view(): void
    {
        RideLocation::factory()->count(3)->create([
            'ride_id' => $this->ride->id,
        ]);

        $response = $this->actingAs($this->rider, 'sanctum')
            ->getJson("/api/v1/locations/history/{$this->ride->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test get current location
     */
    public function test_get_current_location(): void
    {
        RideLocation::factory()->count(3)->create([
            'ride_id' => $this->ride->id,
        ]);

        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson("/api/v1/locations/current/{$this->ride->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'ride_id',
                    'latitude',
                    'longitude',
                    'accuracy',
                    'speed',
                    'heading',
                    'altitude',
                    'timestamp',
                    'created_at',
                ],
            ]);
    }

    /**
     * Test get current location requires authentication
     */
    public function test_get_current_location_requires_authentication(): void
    {
        $response = $this->getJson("/api/v1/locations/current/{$this->ride->id}");

        $response->assertStatus(401);
    }

    /**
     * Test get current location for non-existent ride
     */
    public function test_get_current_location_for_non_existent_ride(): void
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson('/api/v1/locations/current/9999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => 'Ride not found',
            ]);
    }

    /**
     * Test get current location when no location data exists
     */
    public function test_get_current_location_when_no_data_exists(): void
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson("/api/v1/locations/current/{$this->ride->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => 'No location data available',
            ]);
    }

    /**
     * Test get current location authorization - only rider or driver can view
     */
    public function test_get_current_location_authorization(): void
    {
        RideLocation::factory()->create([
            'ride_id' => $this->ride->id,
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/locations/current/{$this->ride->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'Unauthorized',
            ]);
    }

    /**
     * Test get current location rider can view
     */
    public function test_get_current_location_rider_can_view(): void
    {
        RideLocation::factory()->create([
            'ride_id' => $this->ride->id,
        ]);

        $response = $this->actingAs($this->rider, 'sanctum')
            ->getJson("/api/v1/locations/current/{$this->ride->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test get current location returns latest location
     */
    public function test_get_current_location_returns_latest(): void
    {
        $location1 = RideLocation::factory()->create([
            'ride_id' => $this->ride->id,
            'latitude' => 28.6139,
            'timestamp' => now()->subMinutes(5),
        ]);

        $location2 = RideLocation::factory()->create([
            'ride_id' => $this->ride->id,
            'latitude' => 28.6200,
            'timestamp' => now(),
        ]);

        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson("/api/v1/locations/current/{$this->ride->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $location2->id,
                    'latitude' => 28.6200,
                ],
            ]);
    }
}
