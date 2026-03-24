<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test profile completion endpoint with all required fields
     */
    public function test_complete_profile_with_all_required_fields()
    {
        $user = User::factory()->create([
            'display_name' => 'John Doe',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'profile_photo_url' => 'profile-photos/test.jpg',
            'profile_completed' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile/complete', []);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile marked as complete',
            ]);

        $this->assertTrue($user->fresh()->profile_completed);
    }

    /**
     * Test profile completion endpoint with missing display_name
     */
    public function test_complete_profile_missing_display_name()
    {
        $user = User::factory()->create([
            'display_name' => null,
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'profile_photo_url' => 'profile-photos/test.jpg',
            'profile_completed' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile/complete', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Missing required profile fields',
            ])
            ->assertJsonPath('missing_fields', ['display_name']);

        $this->assertFalse($user->fresh()->profile_completed);
    }

    /**
     * Test profile completion endpoint with missing date_of_birth
     */
    public function test_complete_profile_missing_date_of_birth()
    {
        $user = User::factory()->create([
            'display_name' => 'John Doe',
            'date_of_birth' => null,
            'gender' => 'male',
            'profile_photo_url' => 'profile-photos/test.jpg',
            'profile_completed' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile/complete', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Missing required profile fields',
            ])
            ->assertJsonPath('missing_fields', ['date_of_birth']);

        $this->assertFalse($user->fresh()->profile_completed);
    }

    /**
     * Test profile completion endpoint with missing gender
     */
    public function test_complete_profile_missing_gender()
    {
        $user = User::factory()->create([
            'display_name' => 'John Doe',
            'date_of_birth' => '1990-01-01',
            'gender' => null,
            'profile_photo_url' => 'profile-photos/test.jpg',
            'profile_completed' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile/complete', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Missing required profile fields',
            ])
            ->assertJsonPath('missing_fields', ['gender']);

        $this->assertFalse($user->fresh()->profile_completed);
    }

    /**
     * Test profile completion endpoint with missing profile_photo_url
     */
    public function test_complete_profile_missing_profile_photo_url()
    {
        $user = User::factory()->create([
            'display_name' => 'John Doe',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'profile_photo_url' => null,
            'profile_completed' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile/complete', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Missing required profile fields',
            ])
            ->assertJsonPath('missing_fields', ['profile_photo_url']);

        $this->assertFalse($user->fresh()->profile_completed);
    }

    /**
     * Test profile completion endpoint with multiple missing fields
     */
    public function test_complete_profile_multiple_missing_fields()
    {
        $user = User::factory()->create([
            'display_name' => null,
            'date_of_birth' => null,
            'gender' => 'male',
            'profile_photo_url' => null,
            'profile_completed' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile/complete', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Missing required profile fields',
            ]);

        $missingFields = $response->json('missing_fields');
        $this->assertContains('display_name', $missingFields);
        $this->assertContains('date_of_birth', $missingFields);
        $this->assertContains('profile_photo_url', $missingFields);

        $this->assertFalse($user->fresh()->profile_completed);
    }

    /**
     * Test profile completion endpoint without authentication
     */
    public function test_complete_profile_without_authentication()
    {
        $response = $this->postJson('/api/v1/user/profile/complete', []);

        $response->assertStatus(401);
    }

    /**
     * Test profile completion returns updated profile data
     */
    public function test_complete_profile_returns_updated_profile()
    {
        $user = User::factory()->create([
            'display_name' => 'John Doe',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'profile_photo_url' => 'profile-photos/test.jpg',
            'profile_completed' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile/complete', []);

        $response->assertStatus(200)
            ->assertJsonPath('profile.profile_completed', true)
            ->assertJsonPath('profile.display_name', 'John Doe')
            ->assertJsonPath('profile.gender', 'male');
    }
}
