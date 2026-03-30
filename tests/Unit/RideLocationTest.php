<?php

namespace Tests\Unit;

use App\Models\RideLocation;
use App\Models\Ride;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RideLocationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that ride location can be created with all attributes
     */
    public function test_ride_location_can_be_created(): void
    {
        $ride = Ride::factory()->create();

        $location = RideLocation::create([
            'ride_id' => $ride->id,
            'latitude' => 28.7041,
            'longitude' => 77.1025,
            'accuracy' => 10.5,
            'speed' => 45.2,
            'heading' => 180.0,
            'altitude' => 200.5,
            'timestamp' => now(),
        ]);

        $this->assertNotNull($location->id);
        $this->assertEquals($ride->id, $location->ride_id);
        $this->assertEquals(28.7041, $location->latitude);
        $this->assertEquals(77.1025, $location->longitude);
    }

    /**
     * Test that ride location belongs to a ride
     */
    public function test_ride_location_belongs_to_ride(): void
    {
        $ride = Ride::factory()->create();
        $location = RideLocation::factory()->create(['ride_id' => $ride->id]);

        $this->assertTrue($location->ride()->is($ride));
    }

    /**
     * Test that latitude is cast to decimal
     */
    public function test_latitude_is_cast_to_decimal(): void
    {
        $ride = Ride::factory()->create();

        $location = RideLocation::create([
            'ride_id' => $ride->id,
            'latitude' => 28.7041,
            'longitude' => 77.1025,
            'timestamp' => now(),
        ]);

        $this->assertIsNumeric($location->latitude);
        $this->assertEquals(28.7041, (float) $location->latitude);
    }

    /**
     * Test that longitude is cast to decimal
     */
    public function test_longitude_is_cast_to_decimal(): void
    {
        $ride = Ride::factory()->create();

        $location = RideLocation::create([
            'ride_id' => $ride->id,
            'latitude' => 28.7041,
            'longitude' => 77.1025,
            'timestamp' => now(),
        ]);

        $this->assertIsNumeric($location->longitude);
        $this->assertEquals(77.1025, (float) $location->longitude);
    }

    /**
     * Test that accuracy is cast to decimal
     */
    public function test_accuracy_is_cast_to_decimal(): void
    {
        $ride = Ride::factory()->create();

        $location = RideLocation::create([
            'ride_id' => $ride->id,
            'latitude' => 28.7041,
            'longitude' => 77.1025,
            'accuracy' => 10.5,
            'timestamp' => now(),
        ]);

        $this->assertIsNumeric($location->accuracy);
        $this->assertEquals(10.5, (float) $location->accuracy);
    }

    /**
     * Test that speed is cast to decimal
     */
    public function test_speed_is_cast_to_decimal(): void
    {
        $ride = Ride::factory()->create();

        $location = RideLocation::create([
            'ride_id' => $ride->id,
            'latitude' => 28.7041,
            'longitude' => 77.1025,
            'speed' => 45.2,
            'timestamp' => now(),
        ]);

        $this->assertIsNumeric($location->speed);
        $this->assertEquals(45.2, (float) $location->speed);
    }

    /**
     * Test that heading is cast to decimal
     */
    public function test_heading_is_cast_to_decimal(): void
    {
        $ride = Ride::factory()->create();

        $location = RideLocation::create([
            'ride_id' => $ride->id,
            'latitude' => 28.7041,
            'longitude' => 77.1025,
            'heading' => 180.0,
            'timestamp' => now(),
        ]);

        $this->assertIsNumeric($location->heading);
        $this->assertEquals(180.0, (float) $location->heading);
    }

    /**
     * Test that altitude is cast to decimal
     */
    public function test_altitude_is_cast_to_decimal(): void
    {
        $ride = Ride::factory()->create();

        $location = RideLocation::create([
            'ride_id' => $ride->id,
            'latitude' => 28.7041,
            'longitude' => 77.1025,
            'altitude' => 200.5,
            'timestamp' => now(),
        ]);

        $this->assertIsNumeric($location->altitude);
        $this->assertEquals(200.5, (float) $location->altitude);
    }

    /**
     * Test that optional fields can be null
     */
    public function test_optional_fields_can_be_null(): void
    {
        $ride = Ride::factory()->create();

        $location = RideLocation::create([
            'ride_id' => $ride->id,
            'latitude' => 28.7041,
            'longitude' => 77.1025,
            'accuracy' => null,
            'speed' => null,
            'heading' => null,
            'altitude' => null,
            'timestamp' => now(),
        ]);

        $this->assertNull($location->accuracy);
        $this->assertNull($location->speed);
        $this->assertNull($location->heading);
        $this->assertNull($location->altitude);
    }

    /**
     * Test that timestamp is cast to datetime
     */
    public function test_timestamp_is_cast_to_datetime(): void
    {
        $ride = Ride::factory()->create();
        $timestamp = now();

        $location = RideLocation::create([
            'ride_id' => $ride->id,
            'latitude' => 28.7041,
            'longitude' => 77.1025,
            'timestamp' => $timestamp,
        ]);

        $this->assertIsObject($location->timestamp);
    }

    /**
     * Test that ride location can be updated
     */
    public function test_ride_location_can_be_updated(): void
    {
        $ride = Ride::factory()->create();
        $location = RideLocation::factory()->create(['ride_id' => $ride->id]);

        $location->update([
            'latitude' => 28.5355,
            'longitude' => 77.3910,
            'speed' => 50.0,
        ]);

        $this->assertEquals(28.5355, (float) $location->latitude);
        $this->assertEquals(77.3910, (float) $location->longitude);
        $this->assertEquals(50.0, (float) $location->speed);
    }

    /**
     * Test that ride location is deleted when ride is deleted
     */
    public function test_ride_location_deleted_when_ride_deleted(): void
    {
        $ride = Ride::factory()->create();
        $location = RideLocation::factory()->create(['ride_id' => $ride->id]);

        $locationId = $location->id;
        $ride->delete();

        $this->assertNull(RideLocation::find($locationId));
    }

    /**
     * Test that ride can have multiple locations
     */
    public function test_ride_can_have_multiple_locations(): void
    {
        $ride = Ride::factory()->create();

        RideLocation::factory()->create(['ride_id' => $ride->id]);
        RideLocation::factory()->create(['ride_id' => $ride->id]);
        RideLocation::factory()->create(['ride_id' => $ride->id]);

        $this->assertEquals(3, $ride->locations()->count());
    }

    /**
     * Test that latitude is within valid range
     */
    public function test_latitude_within_valid_range(): void
    {
        $ride = Ride::factory()->create();

        $location = RideLocation::create([
            'ride_id' => $ride->id,
            'latitude' => -45.5,
            'longitude' => 77.1025,
            'timestamp' => now(),
        ]);

        $this->assertGreaterThanOrEqual(-90, $location->latitude);
        $this->assertLessThanOrEqual(90, $location->latitude);
    }

    /**
     * Test that longitude is within valid range
     */
    public function test_longitude_within_valid_range(): void
    {
        $ride = Ride::factory()->create();

        $location = RideLocation::create([
            'ride_id' => $ride->id,
            'latitude' => 28.7041,
            'longitude' => -120.5,
            'timestamp' => now(),
        ]);

        $this->assertGreaterThanOrEqual(-180, $location->longitude);
        $this->assertLessThanOrEqual(180, $location->longitude);
    }
}
