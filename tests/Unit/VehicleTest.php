<?php

namespace Tests\Unit;

use App\Models\Vehicle;
use App\Models\User;
use App\Models\Ride;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that vehicle can be created with all attributes
     */
    public function test_vehicle_can_be_created(): void
    {
        $user = User::factory()->driver()->create();

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'vehicle_name' => 'My Car',
            'vehicle_type' => 'sedan',
            'license_plate' => 'DL01AB1234',
            'vehicle_color' => 'Black',
            'vehicle_year' => 2023,
            'seating_capacity' => 5,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->assertNotNull($vehicle->id);
        $this->assertEquals($user->id, $vehicle->user_id);
        $this->assertEquals('My Car', $vehicle->vehicle_name);
        $this->assertEquals('sedan', $vehicle->vehicle_type);
        $this->assertEquals('DL01AB1234', $vehicle->license_plate);
    }

    /**
     * Test that vehicle belongs to a user
     */
    public function test_vehicle_belongs_to_user(): void
    {
        $user = User::factory()->driver()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($vehicle->user()->is($user));
    }

    /**
     * Test that vehicle has many rides
     */
    public function test_vehicle_has_many_rides(): void
    {
        $user = User::factory()->driver()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $user->id]);

        Ride::factory()->create(['vehicle_id' => $vehicle->id]);
        Ride::factory()->create(['vehicle_id' => $vehicle->id]);

        $this->assertEquals(2, $vehicle->rides()->count());
    }

    /**
     * Test that seating capacity is auto-determined for sedan
     */
    public function test_seating_capacity_auto_determined_for_sedan(): void
    {
        $user = User::factory()->driver()->create();

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'vehicle_name' => 'Sedan',
            'vehicle_type' => 'sedan',
            'license_plate' => 'DL01AB1234',
            'seating_capacity' => null,
        ]);

        $this->assertEquals(5, $vehicle->seating_capacity);
    }

    /**
     * Test that seating capacity is auto-determined for suv
     */
    public function test_seating_capacity_auto_determined_for_suv(): void
    {
        $user = User::factory()->driver()->create();

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'vehicle_name' => 'SUV',
            'vehicle_type' => 'suv',
            'license_plate' => 'DL01AB1234',
            'seating_capacity' => null,
        ]);

        $this->assertEquals(7, $vehicle->seating_capacity);
    }

    /**
     * Test that seating capacity is auto-determined for muv
     */
    public function test_seating_capacity_auto_determined_for_muv(): void
    {
        $user = User::factory()->driver()->create();

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'vehicle_name' => 'MUV',
            'vehicle_type' => 'muv',
            'license_plate' => 'DL01AB1234',
            'seating_capacity' => null,
        ]);

        $this->assertEquals(8, $vehicle->seating_capacity);
    }

    /**
     * Test that seating capacity can be explicitly set
     */
    public function test_seating_capacity_can_be_explicitly_set(): void
    {
        $user = User::factory()->driver()->create();

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'vehicle_name' => 'Custom',
            'vehicle_type' => 'sedan',
            'license_plate' => 'DL01AB1234',
            'seating_capacity' => 6,
        ]);

        $this->assertEquals(6, $vehicle->seating_capacity);
    }

    /**
     * Test that seating capacity is bounded between 1 and 8
     */
    public function test_seating_capacity_is_bounded(): void
    {
        $user = User::factory()->driver()->create();

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'vehicle_name' => 'Test',
            'vehicle_type' => 'sedan',
            'license_plate' => 'DL01AB1234',
            'seating_capacity' => 15,
        ]);

        $this->assertEquals(8, $vehicle->seating_capacity);
    }

    /**
     * Test that is_default field is cast to boolean
     */
    public function test_is_default_field_is_cast_to_boolean(): void
    {
        $user = User::factory()->driver()->create();

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'vehicle_name' => 'Test',
            'vehicle_type' => 'sedan',
            'license_plate' => 'DL01AB1234',
            'is_default' => 1,
        ]);

        $this->assertIsBool($vehicle->is_default);
        $this->assertTrue($vehicle->is_default);
    }

    /**
     * Test that is_active field is cast to boolean
     */
    public function test_is_active_field_is_cast_to_boolean(): void
    {
        $user = User::factory()->driver()->create();

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'vehicle_name' => 'Test',
            'vehicle_type' => 'sedan',
            'license_plate' => 'DL01AB1234',
            'is_active' => 0,
        ]);

        $this->assertIsBool($vehicle->is_active);
        $this->assertFalse($vehicle->is_active);
    }

    /**
     * Test that vehicle_year is cast to integer
     */
    public function test_vehicle_year_is_cast_to_integer(): void
    {
        $user = User::factory()->driver()->create();

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'vehicle_name' => 'Test',
            'vehicle_type' => 'sedan',
            'license_plate' => 'DL01AB1234',
            'vehicle_year' => '2023',
        ]);

        $this->assertIsInt($vehicle->vehicle_year);
        $this->assertEquals(2023, $vehicle->vehicle_year);
    }

    /**
     * Test that vehicle photo can be null
     */
    public function test_vehicle_photo_can_be_null(): void
    {
        $user = User::factory()->driver()->create();

        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'vehicle_name' => 'Test',
            'vehicle_type' => 'sedan',
            'license_plate' => 'DL01AB1234',
            'vehicle_photo' => null,
        ]);

        $this->assertNull($vehicle->vehicle_photo);
    }

    /**
     * Test that vehicle can be updated
     */
    public function test_vehicle_can_be_updated(): void
    {
        $user = User::factory()->driver()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $user->id]);

        $vehicle->update([
            'vehicle_name' => 'Updated Name',
            'vehicle_color' => 'Red',
        ]);

        $this->assertEquals('Updated Name', $vehicle->vehicle_name);
        $this->assertEquals('Red', $vehicle->vehicle_color);
    }

    /**
     * Test that vehicle is deleted when user is deleted
     */
    public function test_vehicle_deleted_when_user_deleted(): void
    {
        $user = User::factory()->driver()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $user->id]);

        $vehicleId = $vehicle->id;
        $user->delete();

        $this->assertNull(Vehicle::find($vehicleId));
    }

    /**
     * Test that multiple vehicles can be created for a user
     */
    public function test_user_can_have_multiple_vehicles(): void
    {
        $user = User::factory()->driver()->create();

        Vehicle::factory()->create(['user_id' => $user->id]);
        Vehicle::factory()->create(['user_id' => $user->id]);
        Vehicle::factory()->create(['user_id' => $user->id]);

        $this->assertEquals(3, $user->vehicles()->count());
    }
}
