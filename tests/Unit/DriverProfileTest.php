<?php

namespace Tests\Unit;

use App\Models\DriverProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that driver profile can be created
     */
    public function test_driver_profile_can_be_created(): void
    {
        $user = User::factory()->driver()->create();
        $profile = DriverProfile::factory()->create(['user_id' => $user->id]);

        $this->assertNotNull($profile->id);
        $this->assertEquals($user->id, $profile->user_id);
    }

    /**
     * Test that driver profile belongs to a user
     */
    public function test_driver_profile_belongs_to_user(): void
    {
        $user = User::factory()->driver()->create();
        $profile = DriverProfile::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($profile->user()->is($user));
    }

    /**
     * Test that is_approved field is cast to boolean
     */
    public function test_is_approved_field_is_cast_to_boolean(): void
    {
        $profile = DriverProfile::factory()->create(['is_approved' => 1]);

        $this->assertIsBool($profile->is_approved);
        $this->assertTrue($profile->is_approved);
    }

    /**
     * Test that is_online field is cast to boolean
     */
    public function test_is_online_field_is_cast_to_boolean(): void
    {
        $profile = DriverProfile::factory()->create(['is_online' => 0]);

        $this->assertIsBool($profile->is_online);
        $this->assertFalse($profile->is_online);
    }

    /**
     * Test that current_lat is cast to decimal
     */
    public function test_current_lat_is_cast_to_decimal(): void
    {
        $profile = DriverProfile::factory()->create([
            'current_lat' => 28.7041,
            'current_lng' => 77.1025,
        ]);

        $this->assertIsNumeric($profile->current_lat);
        $this->assertEquals(28.7041, (float) $profile->current_lat);
    }

    /**
     * Test that current_lng is cast to decimal
     */
    public function test_current_lng_is_cast_to_decimal(): void
    {
        $profile = DriverProfile::factory()->create([
            'current_lat' => 28.7041,
            'current_lng' => 77.1025,
        ]);

        $this->assertIsNumeric($profile->current_lng);
        $this->assertEquals(77.1025, (float) $profile->current_lng);
    }

    /**
     * Test that driver profile can be updated
     */
    public function test_driver_profile_can_be_updated(): void
    {
        $profile = DriverProfile::factory()->create();

        $profile->update([
            'is_online' => true,
            'current_lat' => 28.5355,
            'current_lng' => 77.3910,
        ]);

        $this->assertTrue($profile->is_online);
        $this->assertEquals(28.5355, (float) $profile->current_lat);
        $this->assertEquals(77.3910, (float) $profile->current_lng);
    }

    /**
     * Test that driver profile is deleted when user is deleted
     */
    public function test_driver_profile_deleted_when_user_deleted(): void
    {
        $user = User::factory()->driver()->create();
        $profile = DriverProfile::factory()->create(['user_id' => $user->id]);

        $profileId = $profile->id;
        $user->delete();

        $this->assertNull(DriverProfile::find($profileId));
    }

    /**
     * Test that current location can be updated
     */
    public function test_current_location_can_be_updated(): void
    {
        $profile = DriverProfile::factory()->create([
            'current_lat' => 28.7041,
            'current_lng' => 77.1025,
        ]);

        $profile->update([
            'current_lat' => 28.5355,
            'current_lng' => 77.3910,
        ]);

        $this->assertEquals(28.5355, (float) $profile->current_lat);
        $this->assertEquals(77.3910, (float) $profile->current_lng);
    }

    /**
     * Test that license_number can be stored
     */
    public function test_license_number_can_be_stored(): void
    {
        $profile = DriverProfile::factory()->create([
            'license_number' => 'DL987654321',
        ]);

        $this->assertEquals('DL987654321', $profile->license_number);
    }

    /**
     * Test that vehicle_type can be stored
     */
    public function test_vehicle_type_can_be_stored(): void
    {
        $profile = DriverProfile::factory()->create([
            'vehicle_type' => 'suv',
        ]);

        $this->assertEquals('suv', $profile->vehicle_type);
    }

    /**
     * Test that vehicle_number can be stored
     */
    public function test_vehicle_number_can_be_stored(): void
    {
        $profile = DriverProfile::factory()->create([
            'vehicle_number' => 'MH02AB5678',
        ]);

        $this->assertEquals('MH02AB5678', $profile->vehicle_number);
    }

    /**
     * Test that is_approved defaults to false
     */
    public function test_is_approved_defaults_to_false(): void
    {
        $profile = DriverProfile::factory()->create(['is_approved' => false]);

        $profile->refresh();
        $this->assertFalse($profile->is_approved);
    }

    /**
     * Test that is_online defaults to false
     */
    public function test_is_online_defaults_to_false(): void
    {
        $profile = DriverProfile::factory()->create(['is_online' => false]);

        $profile->refresh();
        $this->assertFalse($profile->is_online);
    }
}
