<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->user = User::factory()->create();
    }

    public function test_create_vehicle_successfully()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/vehicles', [
                'vehicle_name' => 'My Sedan',
                'vehicle_type' => 'sedan',
                'license_plate' => 'KA01AB1234',
                'vehicle_color' => 'Black',
                'vehicle_year' => 2023,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('vehicle.vehicle_name', 'My Sedan')
            ->assertJsonPath('vehicle.vehicle_type', 'sedan')
            ->assertJsonPath('vehicle.license_plate', 'KA01AB1234');

        $this->assertDatabaseHas('vehicles', [
            'user_id' => $this->user->id,
            'vehicle_name' => 'My Sedan',
            'license_plate' => 'KA01AB1234',
        ]);
    }

    public function test_upload_vehicle_photo_successfully()
    {
        $vehicle = Vehicle::factory()->create(['user_id' => $this->user->id]);
        $file = UploadedFile::fake()->image('vehicle.jpg', 100, 100);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/vehicles/{$vehicle->id}/photo", [
                'vehicle_photo' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Vehicle photo uploaded successfully');

        $this->assertNotNull($response->json('vehicle_photo_url'));
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
        ]);
    }

    public function test_upload_vehicle_photo_with_invalid_file_type()
    {
        $vehicle = Vehicle::factory()->create(['user_id' => $this->user->id]);
        $file = UploadedFile::fake()->create('document.txt', 100);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/vehicles/{$vehicle->id}/photo", [
                'vehicle_photo' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_upload_vehicle_photo_exceeding_size_limit()
    {
        $vehicle = Vehicle::factory()->create(['user_id' => $this->user->id]);
        $file = UploadedFile::fake()->image('vehicle.jpg')->size(11 * 1024); // 11MB

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/vehicles/{$vehicle->id}/photo", [
                'vehicle_photo' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_list_vehicles_for_user()
    {
        Vehicle::factory()->count(3)->create(['user_id' => $this->user->id]);
        Vehicle::factory()->create(); // Vehicle for another user

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/vehicles');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 3);
    }

    public function test_get_specific_vehicle()
    {
        $vehicle = Vehicle::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/vehicles/{$vehicle->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('vehicle.id', $vehicle->id);
    }

    public function test_cannot_get_vehicle_of_another_user()
    {
        $otherUser = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/vehicles/{$vehicle->id}");

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_update_vehicle_successfully()
    {
        $vehicle = Vehicle::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/vehicles/{$vehicle->id}", [
                'vehicle_name' => 'Updated Name',
                'vehicle_color' => 'Red',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('vehicle.vehicle_name', 'Updated Name')
            ->assertJsonPath('vehicle.vehicle_color', 'Red');
    }

    public function test_delete_vehicle_successfully()
    {
        $vehicle = Vehicle::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/vehicles/{$vehicle->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }

    public function test_set_vehicle_as_default()
    {
        $vehicle1 = Vehicle::factory()->create(['user_id' => $this->user->id, 'is_default' => true]);
        $vehicle2 = Vehicle::factory()->create(['user_id' => $this->user->id, 'is_default' => false]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/vehicles/{$vehicle2->id}/set-default");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('vehicle.is_default', true);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle2->id,
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle1->id,
            'is_default' => false,
        ]);
    }

    public function test_unauthenticated_user_cannot_create_vehicle()
    {
        $response = $this->postJson('/api/v1/vehicles', [
            'vehicle_name' => 'My Sedan',
            'vehicle_type' => 'sedan',
            'license_plate' => 'KA01AB1234',
        ]);

        $response->assertStatus(401);
    }
}
