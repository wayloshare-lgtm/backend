<?php

namespace Tests\Unit;

use App\Models\Ride;
use App\Models\User;
use Tests\TestCase;

class RideTest extends TestCase
{
    /**
     * Test that preferences field can be stored and retrieved as JSON
     */
    public function test_preferences_field_is_cast_to_json(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();

        $preferencesData = [
            'ac_available' => true,
            'wifi_available' => true,
            'music_preference' => 'pop',
            'smoking_allowed' => false,
        ];

        $ride = Ride::create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'pickup_location' => 'Start Point',
            'pickup_lat' => 28.7041,
            'pickup_lng' => 77.1025,
            'dropoff_location' => 'End Point',
            'dropoff_lat' => 28.5355,
            'dropoff_lng' => 77.3910,
            'estimated_distance_km' => 15.5,
            'estimated_duration_minutes' => 30,
            'estimated_fare' => 250.00,
            'preferences' => $preferencesData,
        ]);

        $this->assertIsArray($ride->preferences);
        $this->assertEquals($preferencesData, $ride->preferences);
        $this->assertTrue($ride->preferences['ac_available']);
        $this->assertTrue($ride->preferences['wifi_available']);
        $this->assertEquals('pop', $ride->preferences['music_preference']);
        $this->assertFalse($ride->preferences['smoking_allowed']);
    }

    /**
     * Test that preferences field can be null
     */
    public function test_preferences_field_can_be_null(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();

        $ride = Ride::create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'pickup_location' => 'Start Point',
            'pickup_lat' => 28.7041,
            'pickup_lng' => 77.1025,
            'dropoff_location' => 'End Point',
            'dropoff_lat' => 28.5355,
            'dropoff_lng' => 77.3910,
            'estimated_distance_km' => 15.5,
            'estimated_duration_minutes' => 30,
            'estimated_fare' => 250.00,
            'preferences' => null,
        ]);

        $this->assertNull($ride->preferences);
    }

    /**
     * Test that preferences field defaults to null for existing rides
     */
    public function test_preferences_field_defaults_to_null(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();

        $ride = Ride::create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'pickup_location' => 'Start Point',
            'pickup_lat' => 28.7041,
            'pickup_lng' => 77.1025,
            'dropoff_location' => 'End Point',
            'dropoff_lat' => 28.5355,
            'dropoff_lng' => 77.3910,
            'estimated_distance_km' => 15.5,
            'estimated_duration_minutes' => 30,
            'estimated_fare' => 250.00,
        ]);

        $this->assertNull($ride->preferences);
    }

    /**
     * Test that preferences field can be updated
     */
    public function test_preferences_field_can_be_updated(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();

        $ride = Ride::create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'pickup_location' => 'Start Point',
            'pickup_lat' => 28.7041,
            'pickup_lng' => 77.1025,
            'dropoff_location' => 'End Point',
            'dropoff_lat' => 28.5355,
            'dropoff_lng' => 77.3910,
            'estimated_distance_km' => 15.5,
            'estimated_duration_minutes' => 30,
            'estimated_fare' => 250.00,
            'preferences' => ['ac_available' => true],
        ]);

        $newPreferences = [
            'ac_available' => false,
            'wifi_available' => true,
            'music_preference' => 'jazz',
        ];

        $ride->update(['preferences' => $newPreferences]);

        $this->assertEquals($newPreferences, $ride->preferences);
        $this->assertFalse($ride->preferences['ac_available']);
        $this->assertTrue($ride->preferences['wifi_available']);
        $this->assertEquals('jazz', $ride->preferences['music_preference']);
    }

    /**
     * Test that ac_available field defaults to false
     */
    public function test_ac_available_field_defaults_to_false(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();

        $ride = Ride::create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'pickup_location' => 'Start Point',
            'pickup_lat' => 28.7041,
            'pickup_lng' => 77.1025,
            'dropoff_location' => 'End Point',
            'dropoff_lat' => 28.5355,
            'dropoff_lng' => 77.3910,
            'estimated_distance_km' => 15.5,
            'estimated_duration_minutes' => 30,
            'estimated_fare' => 250.00,
        ]);

        // Refresh from database to get the default value
        $ride->refresh();
        $this->assertFalse($ride->ac_available);
    }

    /**
     * Test that ac_available field can be set to true
     */
    public function test_ac_available_field_can_be_set_to_true(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();

        $ride = Ride::create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'pickup_location' => 'Start Point',
            'pickup_lat' => 28.7041,
            'pickup_lng' => 77.1025,
            'dropoff_location' => 'End Point',
            'dropoff_lat' => 28.5355,
            'dropoff_lng' => 77.3910,
            'estimated_distance_km' => 15.5,
            'estimated_duration_minutes' => 30,
            'estimated_fare' => 250.00,
            'ac_available' => true,
        ]);

        $this->assertTrue($ride->ac_available);
    }

    /**
     * Test that ac_available field can be updated
     */
    public function test_ac_available_field_can_be_updated(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();

        $ride = Ride::create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'pickup_location' => 'Start Point',
            'pickup_lat' => 28.7041,
            'pickup_lng' => 77.1025,
            'dropoff_location' => 'End Point',
            'dropoff_lat' => 28.5355,
            'dropoff_lng' => 77.3910,
            'estimated_distance_km' => 15.5,
            'estimated_duration_minutes' => 30,
            'estimated_fare' => 250.00,
            'ac_available' => false,
        ]);

        $ride->update(['ac_available' => true]);

        $this->assertTrue($ride->ac_available);
    }

    /**
     * Test that ac_available field is cast to boolean
     */
    public function test_ac_available_field_is_cast_to_boolean(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();

        $ride = Ride::create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'pickup_location' => 'Start Point',
            'pickup_lat' => 28.7041,
            'pickup_lng' => 77.1025,
            'dropoff_location' => 'End Point',
            'dropoff_lat' => 28.5355,
            'dropoff_lng' => 77.3910,
            'estimated_distance_km' => 15.5,
            'estimated_duration_minutes' => 30,
            'estimated_fare' => 250.00,
            'ac_available' => 1,
        ]);

        $this->assertIsBool($ride->ac_available);
        $this->assertTrue($ride->ac_available);
    }

    /**
     * Test that wifi_available field defaults to false
     */
    public function test_wifi_available_field_defaults_to_false(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();

        $ride = Ride::create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'pickup_location' => 'Start Point',
            'pickup_lat' => 28.7041,
            'pickup_lng' => 77.1025,
            'dropoff_location' => 'End Point',
            'dropoff_lat' => 28.5355,
            'dropoff_lng' => 77.3910,
            'estimated_distance_km' => 15.5,
            'estimated_duration_minutes' => 30,
            'estimated_fare' => 250.00,
        ]);

        // Refresh from database to get the default value
        $ride->refresh();
        $this->assertFalse($ride->wifi_available);
    }

    /**
     * Test that wifi_available field can be set to true
     */
    public function test_wifi_available_field_can_be_set_to_true(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();

        $ride = Ride::create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'pickup_location' => 'Start Point',
            'pickup_lat' => 28.7041,
            'pickup_lng' => 77.1025,
            'dropoff_location' => 'End Point',
            'dropoff_lat' => 28.5355,
            'dropoff_lng' => 77.3910,
            'estimated_distance_km' => 15.5,
            'estimated_duration_minutes' => 30,
            'estimated_fare' => 250.00,
            'wifi_available' => true,
        ]);

        $this->assertTrue($ride->wifi_available);
    }

    /**
     * Test that wifi_available field can be updated
     */
    public function test_wifi_available_field_can_be_updated(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();

        $ride = Ride::create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'pickup_location' => 'Start Point',
            'pickup_lat' => 28.7041,
            'pickup_lng' => 77.1025,
            'dropoff_location' => 'End Point',
            'dropoff_lat' => 28.5355,
            'dropoff_lng' => 77.3910,
            'estimated_distance_km' => 15.5,
            'estimated_duration_minutes' => 30,
            'estimated_fare' => 250.00,
            'wifi_available' => false,
        ]);

        $ride->update(['wifi_available' => true]);

        $this->assertTrue($ride->wifi_available);
    }

    /**
     * Test that wifi_available field is cast to boolean
     */
    public function test_wifi_available_field_is_cast_to_boolean(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();

        $ride = Ride::create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'pickup_location' => 'Start Point',
            'pickup_lat' => 28.7041,
            'pickup_lng' => 77.1025,
            'dropoff_location' => 'End Point',
            'dropoff_lat' => 28.5355,
            'dropoff_lng' => 77.3910,
            'estimated_distance_km' => 15.5,
            'estimated_duration_minutes' => 30,
            'estimated_fare' => 250.00,
            'wifi_available' => 1,
        ]);

        $this->assertIsBool($ride->wifi_available);
        $this->assertTrue($ride->wifi_available);
    }

    /**
     * Test that ride has many bookings
     */
    public function test_ride_has_many_bookings(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();
        $ride = Ride::factory()->create(['rider_id' => $rider->id, 'driver_id' => $driver->id]);

        \App\Models\Booking::factory()->create(['ride_id' => $ride->id]);
        \App\Models\Booking::factory()->create(['ride_id' => $ride->id]);

        $this->assertEquals(2, $ride->bookings()->count());
    }

    /**
     * Test that ride has many locations
     */
    public function test_ride_has_many_locations(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();
        $ride = Ride::factory()->create(['rider_id' => $rider->id, 'driver_id' => $driver->id]);

        \App\Models\RideLocation::factory()->create(['ride_id' => $ride->id]);
        \App\Models\RideLocation::factory()->create(['ride_id' => $ride->id]);

        $this->assertEquals(2, $ride->locations()->count());
    }

    /**
     * Test that ride belongs to rider
     */
    public function test_ride_belongs_to_rider(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();
        $ride = Ride::factory()->create(['rider_id' => $rider->id, 'driver_id' => $driver->id]);

        $this->assertTrue($ride->rider()->is($rider));
    }

    /**
     * Test that ride belongs to driver
     */
    public function test_ride_belongs_to_driver(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();
        $ride = Ride::factory()->create(['rider_id' => $rider->id, 'driver_id' => $driver->id]);

        $this->assertTrue($ride->driver()->is($driver));
    }

    /**
     * Test that ride belongs to vehicle
     */
    public function test_ride_belongs_to_vehicle(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();
        $vehicle = \App\Models\Vehicle::factory()->create(['user_id' => $driver->id]);
        $ride = Ride::factory()->create(['rider_id' => $rider->id, 'driver_id' => $driver->id, 'vehicle_id' => $vehicle->id]);

        $this->assertTrue($ride->vehicle()->is($vehicle));
    }

    /**
     * Test that available_seats field can be stored
     */
    public function test_available_seats_field_can_be_stored(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();

        $ride = Ride::create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'pickup_location' => 'Start Point',
            'pickup_lat' => 28.7041,
            'pickup_lng' => 77.1025,
            'dropoff_location' => 'End Point',
            'dropoff_lat' => 28.5355,
            'dropoff_lng' => 77.3910,
            'estimated_distance_km' => 15.5,
            'estimated_duration_minutes' => 30,
            'estimated_fare' => 250.00,
            'available_seats' => 4,
        ]);

        $this->assertEquals(4, $ride->available_seats);
    }

    /**
     * Test that price_per_seat field is cast to decimal
     */
    public function test_price_per_seat_field_is_cast_to_decimal(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();

        $ride = Ride::create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'pickup_location' => 'Start Point',
            'pickup_lat' => 28.7041,
            'pickup_lng' => 77.1025,
            'dropoff_location' => 'End Point',
            'dropoff_lat' => 28.5355,
            'dropoff_lng' => 77.3910,
            'estimated_distance_km' => 15.5,
            'estimated_duration_minutes' => 30,
            'estimated_fare' => 250.00,
            'price_per_seat' => 150.50,
        ]);

        $this->assertIsNumeric($ride->price_per_seat);
        $this->assertEquals(150.50, (float) $ride->price_per_seat);
    }

    /**
     * Test that description field can be stored
     */
    public function test_description_field_can_be_stored(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();

        $ride = Ride::create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'pickup_location' => 'Start Point',
            'pickup_lat' => 28.7041,
            'pickup_lng' => 77.1025,
            'dropoff_location' => 'End Point',
            'dropoff_lat' => 28.5355,
            'dropoff_lng' => 77.3910,
            'estimated_distance_km' => 15.5,
            'estimated_duration_minutes' => 30,
            'estimated_fare' => 250.00,
            'description' => 'Comfortable ride with AC',
        ]);

        $this->assertEquals('Comfortable ride with AC', $ride->description);
    }

    /**
     * Test that smoking_allowed field is cast to boolean
     */
    public function test_smoking_allowed_field_is_cast_to_boolean(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();

        $ride = Ride::create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'pickup_location' => 'Start Point',
            'pickup_lat' => 28.7041,
            'pickup_lng' => 77.1025,
            'dropoff_location' => 'End Point',
            'dropoff_lat' => 28.5355,
            'dropoff_lng' => 77.3910,
            'estimated_distance_km' => 15.5,
            'estimated_duration_minutes' => 30,
            'estimated_fare' => 250.00,
            'smoking_allowed' => 1,
        ]);

        $this->assertIsBool($ride->smoking_allowed);
        $this->assertTrue($ride->smoking_allowed);
    }

    /**
     * Test that music_preference field can be stored
     */
    public function test_music_preference_field_can_be_stored(): void
    {
        $rider = User::factory()->create();
        $driver = User::factory()->driver()->create();

        $ride = Ride::create([
            'rider_id' => $rider->id,
            'driver_id' => $driver->id,
            'pickup_location' => 'Start Point',
            'pickup_lat' => 28.7041,
            'pickup_lng' => 77.1025,
            'dropoff_location' => 'End Point',
            'dropoff_lat' => 28.5355,
            'dropoff_lng' => 77.3910,
            'estimated_distance_km' => 15.5,
            'estimated_duration_minutes' => 30,
            'estimated_fare' => 250.00,
            'music_preference' => 'classical',
        ]);

        $this->assertEquals('classical', $ride->music_preference);
    }
}
