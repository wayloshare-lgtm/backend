<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ride;
use App\Models\FareSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchAvailableRidesTest extends TestCase
{
    use RefreshDatabase;

    protected User $driver;
    protected User $passenger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = User::factory()->create(['role' => 'driver']);
        $this->passenger = User::factory()->create(['role' => 'rider']);
        
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

    public function test_search_available_rides_returns_offered_rides()
    {
        // Create offered rides
        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 3,
            'price_per_seat' => 250.00,
            'ac_available' => true,
            'wifi_available' => false,
        ]);

        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Available rides retrieved successfully')
            ->assertJsonPath('pagination.total', 1);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Downtown Station', $response->json('data.0.pickup_location'));
        $this->assertEquals('Airport', $response->json('data.0.dropoff_location'));
        $this->assertEquals(3, $response->json('data.0.available_seats'));
        $this->assertEquals(250.0, $response->json('data.0.price_per_seat'));
    }

    public function test_search_available_rides_filters_by_from_location()
    {
        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 3,
            'price_per_seat' => 250.00,
        ]);

        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Central Park',
            'dropoff_location' => 'Airport',
            'available_seats' => 2,
            'price_per_seat' => 200.00,
        ]);

        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available?from_location=Downtown');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 1);

        $this->assertEquals('Downtown Station', $response->json('data.0.pickup_location'));
    }

    public function test_search_available_rides_filters_by_to_location()
    {
        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 3,
            'price_per_seat' => 250.00,
        ]);

        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Railway Station',
            'available_seats' => 2,
            'price_per_seat' => 150.00,
        ]);

        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available?to_location=Airport');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 1);

        $this->assertEquals('Airport', $response->json('data.0.dropoff_location'));
    }

    public function test_search_available_rides_filters_by_seats_needed()
    {
        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 3,
            'price_per_seat' => 250.00,
        ]);

        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 1,
            'price_per_seat' => 250.00,
        ]);

        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available?seats_needed=2');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 1);

        $this->assertEquals(3, $response->json('data.0.available_seats'));
    }

    public function test_search_available_rides_filters_by_price_range()
    {
        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 3,
            'price_per_seat' => 250.00,
        ]);

        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 2,
            'price_per_seat' => 150.00,
        ]);

        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available?price_min=200&price_max=300');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 1);

        $this->assertEquals(250.0, $response->json('data.0.price_per_seat'));
    }

    public function test_search_available_rides_filters_by_ac_available()
    {
        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 3,
            'price_per_seat' => 250.00,
            'ac_available' => true,
        ]);

        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 2,
            'price_per_seat' => 200.00,
            'ac_available' => false,
        ]);

        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available?ac_available=1');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 1);

        $this->assertTrue($response->json('data.0.ac_available'));
    }

    public function test_search_available_rides_filters_by_wifi_available()
    {
        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 3,
            'price_per_seat' => 250.00,
            'wifi_available' => true,
        ]);

        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 2,
            'price_per_seat' => 200.00,
            'wifi_available' => false,
        ]);

        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available?wifi_available=1');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 1);

        $this->assertTrue($response->json('data.0.wifi_available'));
    }

    public function test_search_available_rides_filters_by_smoking_allowed()
    {
        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 3,
            'price_per_seat' => 250.00,
            'smoking_allowed' => true,
        ]);

        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 2,
            'price_per_seat' => 200.00,
            'smoking_allowed' => false,
        ]);

        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available?smoking_allowed=0');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 1);

        $this->assertFalse($response->json('data.0.smoking_allowed'));
    }

    public function test_search_available_rides_sorts_by_price_ascending()
    {
        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 3,
            'price_per_seat' => 300.00,
        ]);

        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 2,
            'price_per_seat' => 150.00,
        ]);

        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available?sort_by=price&sort_order=asc');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 2);

        $this->assertEquals(150.0, $response->json('data.0.price_per_seat'));
        $this->assertEquals(300.0, $response->json('data.1.price_per_seat'));
    }

    public function test_search_available_rides_sorts_by_price_descending()
    {
        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 3,
            'price_per_seat' => 150.00,
        ]);

        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 2,
            'price_per_seat' => 300.00,
        ]);

        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available?sort_by=price&sort_order=desc');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 2);

        $this->assertEquals(300.0, $response->json('data.0.price_per_seat'));
        $this->assertEquals(150.0, $response->json('data.1.price_per_seat'));
    }

    public function test_search_available_rides_pagination()
    {
        // Create 20 rides
        for ($i = 0; $i < 20; $i++) {
            Ride::factory()->create([
                'driver_id' => $this->driver->id,
                'status' => 'offered',
                'pickup_location' => 'Downtown Station',
                'dropoff_location' => 'Airport',
                'available_seats' => 3,
                'price_per_seat' => 250.00,
            ]);
        }

        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available?per_page=10&page=1');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 20)
            ->assertJsonPath('pagination.per_page', 10)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.last_page', 2);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_search_available_rides_includes_driver_information()
    {
        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 3,
            'price_per_seat' => 250.00,
        ]);

        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available');

        $response->assertStatus(200);

        $driver = $response->json('data.0.driver');
        $this->assertNotNull($driver);
        $this->assertEquals($this->driver->id, $driver['id']);
        $this->assertEquals($this->driver->name, $driver['name']);
        $this->assertEquals($this->driver->phone, $driver['phone']);
        $this->assertArrayHasKey('rating', $driver);
        $this->assertArrayHasKey('total_rides', $driver);
    }

    public function test_search_available_rides_excludes_requested_rides()
    {
        Ride::factory()->create([
            'rider_id' => $this->passenger->id,
            'status' => 'requested',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
        ]);

        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 0);

        $this->assertCount(0, $response->json('data'));
    }

    public function test_search_available_rides_excludes_rides_with_no_available_seats()
    {
        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 0,
            'price_per_seat' => 250.00,
        ]);

        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 0);

        $this->assertCount(0, $response->json('data'));
    }

    public function test_search_available_rides_requires_authentication()
    {
        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 3,
            'price_per_seat' => 250.00,
        ]);

        $response = $this->getJson('/api/v1/rides/available');

        $response->assertStatus(401);
    }

    public function test_search_available_rides_with_invalid_seats_needed()
    {
        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available?seats_needed=10');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Validation failed');
    }

    public function test_search_available_rides_with_invalid_sort_by()
    {
        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available?sort_by=invalid');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Validation failed');
    }

    public function test_search_available_rides_with_invalid_sort_order()
    {
        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available?sort_order=invalid');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Validation failed');
    }

    public function test_search_available_rides_with_invalid_date_format()
    {
        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available?date=invalid-date');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'Validation failed');
    }

    public function test_search_available_rides_with_multiple_filters()
    {
        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 3,
            'price_per_seat' => 250.00,
            'ac_available' => true,
            'wifi_available' => true,
            'smoking_allowed' => false,
        ]);

        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'dropoff_location' => 'Airport',
            'available_seats' => 2,
            'price_per_seat' => 150.00,
            'ac_available' => false,
            'wifi_available' => false,
            'smoking_allowed' => true,
        ]);

        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available?from_location=Downtown&to_location=Airport&seats_needed=2&price_min=200&ac_available=1&wifi_available=1&smoking_allowed=0');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.total', 1);

        $this->assertEquals(250.0, $response->json('data.0.price_per_seat'));
        $this->assertTrue($response->json('data.0.ac_available'));
        $this->assertTrue($response->json('data.0.wifi_available'));
        $this->assertFalse($response->json('data.0.smoking_allowed'));
    }

    public function test_search_available_rides_returns_ride_details()
    {
        Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'offered',
            'pickup_location' => 'Downtown Station',
            'pickup_lat' => 12.9716,
            'pickup_lng' => 77.5946,
            'dropoff_location' => 'Airport',
            'dropoff_lat' => 13.1939,
            'dropoff_lng' => 77.7068,
            'estimated_distance_km' => 25.5,
            'estimated_duration_minutes' => 45,
            'estimated_fare' => 500.00,
            'available_seats' => 3,
            'price_per_seat' => 250.00,
            'description' => 'Comfortable sedan with AC',
            'ac_available' => true,
            'wifi_available' => false,
            'music_preference' => 'Bollywood',
            'smoking_allowed' => false,
        ]);

        $response = $this->actingAs($this->passenger, 'sanctum')
            ->getJson('/api/v1/rides/available');

        $response->assertStatus(200);

        $ride = $response->json('data.0');
        $this->assertEquals('Downtown Station', $ride['pickup_location']);
        $this->assertEquals(12.9716, $ride['pickup_lat']);
        $this->assertEquals(77.5946, $ride['pickup_lng']);
        $this->assertEquals('Airport', $ride['dropoff_location']);
        $this->assertEquals(13.1939, $ride['dropoff_lat']);
        $this->assertEquals(77.7068, $ride['dropoff_lng']);
        $this->assertEquals(25.5, $ride['estimated_distance_km']);
        $this->assertEquals(45, $ride['estimated_duration_minutes']);
        $this->assertEquals(500.0, $ride['estimated_fare']);
        $this->assertEquals(3, $ride['available_seats']);
        $this->assertEquals(250.0, $ride['price_per_seat']);
        $this->assertEquals('Comfortable sedan with AC', $ride['description']);
        $this->assertTrue($ride['ac_available']);
        $this->assertFalse($ride['wifi_available']);
        $this->assertEquals('Bollywood', $ride['music_preference']);
        $this->assertFalse($ride['smoking_allowed']);
    }
}
