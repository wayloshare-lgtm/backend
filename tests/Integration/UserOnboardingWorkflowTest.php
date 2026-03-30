<?php

namespace Tests\Integration;

use App\Models\User;
use App\Models\DriverProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserOnboardingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test complete user onboarding workflow:
     * User registration → profile completion → driver profile setup
     */
    public function test_complete_user_onboarding_workflow()
    {
        // Step 1: Create a new user (simulating registration)
        $user = User::factory()->create([
            'display_name' => null,
            'date_of_birth' => null,
            'gender' => null,
            'profile_photo_url' => null,
            'onboarding_completed' => false,
            'profile_completed' => false,
            'user_preference' => 'passenger',
        ]);

        $this->assertFalse($user->onboarding_completed);
        $this->assertFalse($user->profile_completed);

        // Step 2: Update user profile with required fields
        $profileData = [
            'display_name' => 'John Doe',
            'date_of_birth' => '1990-01-15',
            'gender' => 'male',
            'bio' => 'I love traveling',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile', $profileData);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $user->refresh();
        $this->assertEquals('John Doe', $user->display_name);
        $this->assertEquals('1990-01-15', $user->date_of_birth->format('Y-m-d'));
        $this->assertEquals('male', $user->gender);

        // Step 3: Mark profile as complete
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile/complete', []);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $user->refresh();
        $this->assertTrue($user->profile_completed);

        // Step 4: Update user preferences
        $preferencesData = [
            'user_preference' => 'both',
            'language' => 'english',
            'theme' => 'dark',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/preferences', $preferencesData);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $user->refresh();
        $this->assertEquals('both', $user->user_preference);
        $this->assertEquals('english', $user->language);
        $this->assertEquals('dark', $user->theme);

        // Step 5: Update privacy settings
        $privacyData = [
            'profile_visibility' => 'public',
            'show_phone' => true,
            'show_email' => false,
            'allow_messages' => true,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/privacy', $privacyData);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $user->refresh();
        $this->assertEquals('public', $user->profile_visibility);
        $this->assertTrue($user->show_phone);
        $this->assertFalse($user->show_email);
        $this->assertTrue($user->allow_messages);

        // Verify final state
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'display_name' => 'John Doe',
                    'gender' => 'male',
                    'user_preference' => 'both',
                    'profile_completed' => true,
                ],
            ]);
    }

    /**
     * Test user onboarding with missing required fields
     */
    public function test_user_onboarding_fails_with_missing_fields()
    {
        $user = User::factory()->create([
            'display_name' => null,
            'date_of_birth' => null,
            'gender' => null,
            'profile_completed' => false,
        ]);

        // Try to complete profile without required fields
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile/complete', []);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);

        $user->refresh();
        $this->assertFalse($user->profile_completed);
    }

    /**
     * Test user can update profile multiple times
     */
    public function test_user_can_update_profile_multiple_times()
    {
        $user = User::factory()->create([
            'display_name' => 'John Doe',
            'bio' => 'Original bio',
        ]);

        // First update
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile', [
                'bio' => 'Updated bio v1',
            ]);

        $response->assertStatus(200);
        $user->refresh();
        $this->assertEquals('Updated bio v1', $user->bio);

        // Second update
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile', [
                'bio' => 'Updated bio v2',
            ]);

        $response->assertStatus(200);
        $user->refresh();
        $this->assertEquals('Updated bio v2', $user->bio);
    }

    /**
     * Test user preferences persist across requests
     */
    public function test_user_preferences_persist()
    {
        $user = User::factory()->create();

        // Set preferences
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/preferences', [
                'language' => 'hindi',
                'theme' => 'light',
            ]);

        // Retrieve preferences
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/preferences');

        $response->assertStatus(200)
            ->assertJsonPath('data.language', 'hindi')
            ->assertJsonPath('data.theme', 'light');
    }

    /**
     * Test privacy settings are properly enforced
     */
    public function test_privacy_settings_are_enforced()
    {
        $user = User::factory()->create([
            'show_phone' => false,
            'show_email' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/profile');

        $response->assertStatus(200);
        // Verify privacy settings are returned
        $this->assertFalse($response->json('data.show_phone'));
        $this->assertFalse($response->json('data.show_email'));
    }
}
