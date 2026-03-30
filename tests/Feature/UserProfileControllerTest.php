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

    /**
     * Test get privacy settings endpoint
     */
    public function test_get_privacy_settings()
    {
        $user = User::factory()->create([
            'profile_visibility' => 'public',
            'show_phone' => true,
            'show_email' => false,
            'allow_messages' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/privacy');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'privacy' => [
                    'profile_visibility' => 'public',
                    'show_phone' => true,
                    'show_email' => false,
                    'allow_messages' => true,
                ],
            ]);
    }

    /**
     * Test update privacy settings endpoint
     */
    public function test_update_privacy_settings()
    {
        $user = User::factory()->create([
            'profile_visibility' => 'public',
            'show_phone' => true,
            'show_email' => false,
            'allow_messages' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/privacy', [
                'profile_visibility' => 'private',
                'show_phone' => false,
                'show_email' => true,
                'allow_messages' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Privacy settings updated successfully',
                'privacy' => [
                    'profile_visibility' => 'private',
                    'show_phone' => false,
                    'show_email' => true,
                    'allow_messages' => false,
                ],
            ]);

        $this->assertEquals('private', $user->fresh()->profile_visibility);
        $this->assertFalse($user->fresh()->show_phone);
        $this->assertTrue($user->fresh()->show_email);
        $this->assertFalse($user->fresh()->allow_messages);
    }

    /**
     * Test update privacy settings with partial data
     */
    public function test_update_privacy_settings_partial()
    {
        $user = User::factory()->create([
            'profile_visibility' => 'public',
            'show_phone' => true,
            'show_email' => false,
            'allow_messages' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/privacy', [
                'allow_messages' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'privacy' => [
                    'allow_messages' => false,
                ],
            ]);

        $this->assertEquals('public', $user->fresh()->profile_visibility);
        $this->assertTrue($user->fresh()->show_phone);
        $this->assertFalse($user->fresh()->show_email);
        $this->assertFalse($user->fresh()->allow_messages);
    }

    /**
     * Test update privacy settings with invalid profile_visibility
     */
    public function test_update_privacy_settings_invalid_visibility()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/privacy', [
                'profile_visibility' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test get privacy settings without authentication
     */
    public function test_get_privacy_settings_without_authentication()
    {
        $response = $this->getJson('/api/v1/user/privacy');

        $response->assertStatus(401);
    }

    /**
     * Test update privacy settings without authentication
     */
    public function test_update_privacy_settings_without_authentication()
    {
        $response = $this->postJson('/api/v1/user/privacy', [
            'allow_messages' => false,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test get preferences endpoint
     */
    public function test_get_preferences()
    {
        $user = User::factory()->create([
            'user_preference' => 'driver',
            'language' => 'english',
            'theme' => 'dark',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/preferences');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'preferences' => [
                    'user_preference' => 'driver',
                    'language' => 'english',
                    'theme' => 'dark',
                ],
            ]);
    }

    /**
     * Test update preferences endpoint
     */
    public function test_update_preferences()
    {
        $user = User::factory()->create([
            'user_preference' => 'driver',
            'language' => 'english',
            'theme' => 'dark',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/preferences', [
                'user_preference' => 'passenger',
                'language' => 'hindi',
                'theme' => 'light',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Preferences updated successfully',
                'preferences' => [
                    'user_preference' => 'passenger',
                    'language' => 'hindi',
                    'theme' => 'light',
                ],
            ]);

        $this->assertEquals('passenger', $user->fresh()->user_preference);
        $this->assertEquals('hindi', $user->fresh()->language);
        $this->assertEquals('light', $user->fresh()->theme);
    }

    /**
     * Test update preferences with partial data
     */
    public function test_update_preferences_partial()
    {
        $user = User::factory()->create([
            'user_preference' => 'driver',
            'language' => 'english',
            'theme' => 'dark',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/preferences', [
                'theme' => 'light',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'preferences' => [
                    'theme' => 'light',
                ],
            ]);

        $this->assertEquals('driver', $user->fresh()->user_preference);
        $this->assertEquals('english', $user->fresh()->language);
        $this->assertEquals('light', $user->fresh()->theme);
    }

    /**
     * Test update preferences with invalid user_preference
     */
    public function test_update_preferences_invalid_preference()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/preferences', [
                'user_preference' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test update preferences with invalid language
     */
    public function test_update_preferences_invalid_language()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/preferences', [
                'language' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test update preferences with invalid theme
     */
    public function test_update_preferences_invalid_theme()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/preferences', [
                'theme' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test get preferences without authentication
     */
    public function test_get_preferences_without_authentication()
    {
        $response = $this->getJson('/api/v1/user/preferences');

        $response->assertStatus(401);
    }

    /**
     * Test update preferences without authentication
     */
    public function test_update_preferences_without_authentication()
    {
        $response = $this->postJson('/api/v1/user/preferences', [
            'theme' => 'light',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test get profile includes driver profile fields for drivers
     */
    public function test_get_profile_includes_driver_profile_fields()
    {
        $user = User::factory()->create(['role' => 'driver']);
        $user->driverProfile()->create([
            'license_number' => 'DL123456',
            'vehicle_type' => 'sedan',
            'vehicle_number' => 'KA01AB1234',
            'languages_spoken' => json_encode(['english', 'hindi']),
            'emergency_contact' => '9876543210',
            'insurance_provider' => 'HDFC Insurance',
            'insurance_policy_number' => 'POL123456789',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'profile' => [
                    'role' => 'driver',
                    'driver_profile' => [
                        'languages_spoken' => json_encode(['english', 'hindi']),
                        'emergency_contact' => '9876543210',
                        'insurance_provider' => 'HDFC Insurance',
                        'insurance_policy_number' => 'POL123456789',
                    ],
                ],
            ]);
    }

    /**
     * Test update driver profile fields
     */
    public function test_update_driver_profile_fields()
    {
        $user = User::factory()->create(['role' => 'driver']);
        $user->driverProfile()->create([
            'license_number' => 'DL123456',
            'vehicle_type' => 'sedan',
            'vehicle_number' => 'KA01AB1234',
            'languages_spoken' => json_encode(['english']),
            'emergency_contact' => '9876543210',
            'insurance_provider' => 'HDFC Insurance',
            'insurance_policy_number' => 'POL123456789',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile', [
                'languages_spoken' => ['english', 'hindi', 'kannada'],
                'emergency_contact' => '9123456789',
                'insurance_provider' => 'ICICI Insurance',
                'insurance_policy_number' => 'POL987654321',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully',
            ]);

        $driverProfile = $user->fresh()->driverProfile;
        $this->assertEquals(['english', 'hindi', 'kannada'], json_decode($driverProfile->languages_spoken));
        $this->assertEquals('9123456789', $driverProfile->emergency_contact);
        $this->assertEquals('ICICI Insurance', $driverProfile->insurance_provider);
        $this->assertEquals('POL987654321', $driverProfile->insurance_policy_number);
    }
}
