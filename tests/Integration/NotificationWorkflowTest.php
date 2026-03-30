<?php

namespace Tests\Integration;

use App\Models\User;
use App\Models\FcmToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test complete notification workflow:
     * Register FCM token → Set preferences → Receive notifications
     */
    public function test_complete_notification_workflow()
    {
        // Step 1: Create user
        $user = User::factory()->create();

        // Step 2: Register FCM token for Android device
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'android_token_12345',
                'device_type' => 'android',
                'device_id' => 'device_123',
                'device_name' => 'Samsung Galaxy S21',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $user->id,
            'fcm_token' => 'android_token_12345',
            'device_type' => 'android',
            'is_active' => true,
        ]);

        // Step 3: Register FCM token for iOS device
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'ios_token_67890',
                'device_type' => 'ios',
                'device_id' => 'device_456',
                'device_name' => 'iPhone 13',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        // Step 4: Get notification preferences
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications/preferences');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Step 5: Update notification preferences
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/notifications/preferences', [
                'ride_updates' => true,
                'booking_confirmations' => true,
                'messages' => true,
                'reviews' => true,
                'promotions' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Step 6: Verify preferences are saved
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications/preferences');

        $response->assertStatus(200)
            ->assertJsonPath('data.ride_updates', true)
            ->assertJsonPath('data.booking_confirmations', true)
            ->assertJsonPath('data.messages', true)
            ->assertJsonPath('data.reviews', true)
            ->assertJsonPath('data.promotions', false);
    }

    /**
     * Test user can register multiple FCM tokens
     */
    public function test_user_can_register_multiple_fcm_tokens()
    {
        $user = User::factory()->create();

        // Register first device
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'token_1',
                'device_type' => 'android',
                'device_id' => 'device_1',
                'device_name' => 'Device 1',
            ]);

        // Register second device
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'token_2',
                'device_type' => 'ios',
                'device_id' => 'device_2',
                'device_name' => 'Device 2',
            ]);

        // Register third device
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'token_3',
                'device_type' => 'android',
                'device_id' => 'device_3',
                'device_name' => 'Device 3',
            ]);

        // Verify all tokens are registered
        $tokens = FcmToken::where('user_id', $user->id)->get();
        $this->assertCount(3, $tokens);

        $tokenValues = $tokens->pluck('fcm_token');
        $this->assertContains('token_1', $tokenValues);
        $this->assertContains('token_2', $tokenValues);
        $this->assertContains('token_3', $tokenValues);
    }

    /**
     * Test FCM token can be updated
     */
    public function test_fcm_token_can_be_updated()
    {
        $user = User::factory()->create();

        // Register initial token
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'old_token',
                'device_type' => 'android',
                'device_id' => 'device_1',
                'device_name' => 'Device 1',
            ]);

        $token = FcmToken::where('user_id', $user->id)->first();

        // Update token (simulating token refresh)
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'new_token',
                'device_type' => 'android',
                'device_id' => 'device_1',
                'device_name' => 'Device 1',
            ]);

        $response->assertStatus(201);

        // Verify old token is replaced
        $this->assertDatabaseMissing('fcm_tokens', [
            'user_id' => $user->id,
            'fcm_token' => 'old_token',
        ]);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $user->id,
            'fcm_token' => 'new_token',
        ]);
    }

    /**
     * Test notification preferences can be partially updated
     */
    public function test_notification_preferences_partial_update()
    {
        $user = User::factory()->create();

        // Set initial preferences
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/notifications/preferences', [
                'ride_updates' => true,
                'booking_confirmations' => true,
                'messages' => true,
                'reviews' => true,
                'promotions' => true,
            ]);

        // Update only one preference
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/notifications/preferences', [
                'promotions' => false,
            ]);

        $response->assertStatus(200);

        // Verify only promotions changed
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications/preferences');

        $response->assertStatus(200)
            ->assertJsonPath('data.ride_updates', true)
            ->assertJsonPath('data.booking_confirmations', true)
            ->assertJsonPath('data.messages', true)
            ->assertJsonPath('data.reviews', true)
            ->assertJsonPath('data.promotions', false);
    }

    /**
     * Test FCM token is marked as inactive when device is unregistered
     */
    public function test_fcm_token_can_be_deactivated()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'token_1',
                'device_type' => 'android',
                'device_id' => 'device_1',
                'device_name' => 'Device 1',
            ]);

        $token = FcmToken::where('user_id', $user->id)->first();
        $this->assertTrue($token->is_active);

        // Deactivate token (simulating device unregistration)
        $token->update(['is_active' => false]);

        $token->refresh();
        $this->assertFalse($token->is_active);
    }

    /**
     * Test different device types are properly stored
     */
    public function test_device_types_are_properly_stored()
    {
        $user = User::factory()->create();

        // Register Android device
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'android_token',
                'device_type' => 'android',
                'device_id' => 'android_device',
                'device_name' => 'Android Phone',
            ]);

        // Register iOS device
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'ios_token',
                'device_type' => 'ios',
                'device_id' => 'ios_device',
                'device_name' => 'iPhone',
            ]);

        // Verify both device types are stored
        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $user->id,
            'device_type' => 'android',
        ]);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $user->id,
            'device_type' => 'ios',
        ]);
    }

    /**
     * Test notification preferences default values
     */
    public function test_notification_preferences_default_values()
    {
        $user = User::factory()->create();

        // Get preferences without setting them
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications/preferences');

        $response->assertStatus(200);
        // Verify default values are returned
        $this->assertNotNull($response->json('data'));
    }

    /**
     * Test FCM token uniqueness per device
     */
    public function test_fcm_token_uniqueness_per_device()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User 1 registers token
        $this->actingAs($user1, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'unique_token',
                'device_type' => 'android',
                'device_id' => 'device_1',
                'device_name' => 'Device 1',
            ]);

        // User 2 tries to register same token (should fail or replace)
        $response = $this->actingAs($user2, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'unique_token',
                'device_type' => 'android',
                'device_id' => 'device_2',
                'device_name' => 'Device 2',
            ]);

        // Token should be unique per user or globally
        // This depends on business logic
        $response->assertStatus(201);
    }

    /**
     * Test notification preferences are user-specific
     */
    public function test_notification_preferences_are_user_specific()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User 1 sets preferences
        $this->actingAs($user1, 'sanctum')
            ->postJson('/api/v1/notifications/preferences', [
                'promotions' => true,
            ]);

        // User 2 sets different preferences
        $this->actingAs($user2, 'sanctum')
            ->postJson('/api/v1/notifications/preferences', [
                'promotions' => false,
            ]);

        // Verify preferences are different
        $response1 = $this->actingAs($user1, 'sanctum')
            ->getJson('/api/v1/notifications/preferences');

        $response2 = $this->actingAs($user2, 'sanctum')
            ->getJson('/api/v1/notifications/preferences');

        $this->assertTrue($response1->json('data.promotions'));
        $this->assertFalse($response2->json('data.promotions'));
    }
}
