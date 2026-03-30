<?php

namespace Tests\Integration;

use App\Models\User;
use App\Models\DriverProfile;
use App\Models\DriverVerification;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverSetupWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test complete driver setup workflow:
     * User profile → driver profile → verification → vehicle setup
     */
    public function test_complete_driver_setup_workflow()
    {
        // Step 1: Create user with driver preference
        $user = User::factory()->create([
            'display_name' => 'Jane Driver',
            'date_of_birth' => '1985-05-20',
            'gender' => 'female',
            'user_preference' => 'driver',
            'profile_completed' => true,
        ]);

        // Step 2: Create driver profile
        $driverProfileData = [
            'bio' => 'Professional driver with 10 years experience',
            'languages_spoken' => ['english', 'hindi'],
            'emergency_contact' => '9876543210',
            'insurance_provider' => 'HDFC Insurance',
            'insurance_policy_number' => 'POL123456789',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/profile', $driverProfileData);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('driver_profiles', [
            'user_id' => $user->id,
            'bio' => 'Professional driver with 10 years experience',
        ]);

        // Step 3: Submit driver verification documents
        $verificationData = [
            'dl_number' => 'DL123456789',
            'dl_expiry_date' => '2026-12-31',
            'rc_number' => 'RC987654321',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification', $verificationData);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('driver_verifications', [
            'user_id' => $user->id,
            'dl_number' => 'DL123456789',
            'verification_status' => 'pending',
        ]);

        // Step 4: Check verification status
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/driver/verification/status');

        $response->assertStatus(200)
            ->assertJsonPath('data.verification_status', 'pending');

        // Step 5: Add vehicle
        $vehicleData = [
            'vehicle_name' => 'Toyota Innova',
            'vehicle_type' => 'muv',
            'license_plate' => 'KA01AB1234',
            'vehicle_color' => 'white',
            'vehicle_year' => 2022,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/vehicles', $vehicleData);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('vehicles', [
            'user_id' => $user->id,
            'license_plate' => 'KA01AB1234',
            'vehicle_type' => 'muv',
        ]);

        // Step 6: Set vehicle as default
        $vehicle = Vehicle::where('user_id', $user->id)->first();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/vehicles/{$vehicle->id}/set-default", []);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $vehicle->refresh();
        $this->assertTrue($vehicle->is_default);

        // Step 7: Verify complete driver setup
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/driver/kyc-status');

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.driver_profile'));
        $this->assertNotNull($response->json('data.verification'));
        $this->assertNotNull($response->json('data.vehicle'));
    }

    /**
     * Test driver can add multiple vehicles
     */
    public function test_driver_can_add_multiple_vehicles()
    {
        $user = User::factory()->create(['user_preference' => 'driver']);

        // Add first vehicle
        $response1 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/vehicles', [
                'vehicle_name' => 'Toyota Innova',
                'vehicle_type' => 'muv',
                'license_plate' => 'KA01AB1234',
                'vehicle_color' => 'white',
                'vehicle_year' => 2022,
            ]);

        $response1->assertStatus(201);

        // Add second vehicle
        $response2 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/vehicles', [
                'vehicle_name' => 'Maruti Swift',
                'vehicle_type' => 'hatchback',
                'license_plate' => 'KA02CD5678',
                'vehicle_color' => 'red',
                'vehicle_year' => 2023,
            ]);

        $response2->assertStatus(201);

        // Verify both vehicles exist
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/vehicles');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test vehicle seating capacity is auto-determined by type
     */
    public function test_vehicle_seating_capacity_auto_determined()
    {
        $user = User::factory()->create(['user_preference' => 'driver']);

        // Create MUV (should have 8 seats)
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/vehicles', [
                'vehicle_name' => 'Toyota Innova',
                'vehicle_type' => 'muv',
                'license_plate' => 'KA01AB1234',
                'vehicle_color' => 'white',
                'vehicle_year' => 2022,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('vehicles', [
            'vehicle_type' => 'muv',
            'seating_capacity' => 8,
        ]);

        // Create sedan (should have 5 seats)
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/vehicles', [
                'vehicle_name' => 'Honda Accord',
                'vehicle_type' => 'sedan',
                'license_plate' => 'KA02CD5678',
                'vehicle_color' => 'black',
                'vehicle_year' => 2023,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('vehicles', [
            'vehicle_type' => 'sedan',
            'seating_capacity' => 5,
        ]);
    }

    /**
     * Test driver verification status transitions
     */
    public function test_driver_verification_status_transitions()
    {
        $user = User::factory()->create(['user_preference' => 'driver']);

        // Create verification (status: pending)
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification', [
                'dl_number' => 'DL123456789',
                'dl_expiry_date' => '2026-12-31',
                'rc_number' => 'RC987654321',
            ]);

        $verification = DriverVerification::where('user_id', $user->id)->first();
        $this->assertEquals('pending', $verification->verification_status);

        // Simulate admin approval (in real scenario, this would be done by admin)
        $verification->update(['verification_status' => 'approved']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/driver/verification/status');

        $response->assertStatus(200)
            ->assertJsonPath('data.verification_status', 'approved');
    }

    /**
     * Test driver profile fields are properly stored
     */
    public function test_driver_profile_fields_stored_correctly()
    {
        $user = User::factory()->create(['user_preference' => 'driver']);

        $profileData = [
            'bio' => 'Experienced driver',
            'languages_spoken' => ['english', 'hindi', 'kannada'],
            'emergency_contact' => '9876543210',
            'insurance_provider' => 'HDFC Insurance',
            'insurance_policy_number' => 'POL123456789',
        ];

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/profile', $profileData);

        $driverProfile = DriverProfile::where('user_id', $user->id)->first();
        $this->assertEquals('Experienced driver', $driverProfile->bio);
        $this->assertContains('english', $driverProfile->languages_spoken);
        $this->assertContains('hindi', $driverProfile->languages_spoken);
        $this->assertContains('kannada', $driverProfile->languages_spoken);
    }

    /**
     * Test cannot set non-existent vehicle as default
     */
    public function test_cannot_set_nonexistent_vehicle_as_default()
    {
        $user = User::factory()->create(['user_preference' => 'driver']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/vehicles/99999/set-default', []);

        $response->assertStatus(404);
    }

    /**
     * Test vehicle license plate must be unique
     */
    public function test_vehicle_license_plate_must_be_unique()
    {
        $user1 = User::factory()->create(['user_preference' => 'driver']);
        $user2 = User::factory()->create(['user_preference' => 'driver']);

        // First user creates vehicle
        $this->actingAs($user1, 'sanctum')
            ->postJson('/api/v1/vehicles', [
                'vehicle_name' => 'Toyota Innova',
                'vehicle_type' => 'muv',
                'license_plate' => 'KA01AB1234',
                'vehicle_color' => 'white',
                'vehicle_year' => 2022,
            ]);

        // Second user tries to create vehicle with same license plate
        $response = $this->actingAs($user2, 'sanctum')
            ->postJson('/api/v1/vehicles', [
                'vehicle_name' => 'Maruti Swift',
                'vehicle_type' => 'hatchback',
                'license_plate' => 'KA01AB1234',
                'vehicle_color' => 'red',
                'vehicle_year' => 2023,
            ]);

        $response->assertStatus(422);
    }
}
