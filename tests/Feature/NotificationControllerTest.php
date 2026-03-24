<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\FcmToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test registering a new FCM token
     */
    public function test_register_fcm_token_creates_new_token(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'test_fcm_token_12345',
                'device_type' => 'android',
                'device_id' => 'device_123',
                'device_name' => 'Samsung Galaxy S21',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'FCM token registered successfully',
            ]);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $this->user->id,
            'fcm_token' => 'test_fcm_token_12345',
            'device_type' => 'android',
            'device_id' => 'device_123',
            'device_name' => 'Samsung Galaxy S21',
            'is_active' => true,
        ]);
    }

    /**
     * Test updating an existing FCM token
     */
    public function test_register_fcm_token_updates_existing_token(): void
    {
        // Create an existing token
        FcmToken::create([
            'user_id' => $this->user->id,
            'fcm_token' => 'test_fcm_token_12345',
            'device_type' => 'android',
            'device_id' => 'device_123',
            'device_name' => 'Old Device Name',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'test_fcm_token_12345',
                'device_type' => 'ios',
                'device_id' => 'device_456',
                'device_name' => 'iPhone 13',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'FCM token registered successfully',
            ]);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $this->user->id,
            'fcm_token' => 'test_fcm_token_12345',
            'device_type' => 'ios',
            'device_id' => 'device_456',
            'device_name' => 'iPhone 13',
            'is_active' => true,
        ]);

        // Verify only one token exists
        $this->assertEquals(1, FcmToken::where('user_id', $this->user->id)->count());
    }

    /**
     * Test registering FCM token without authentication
     */
    public function test_register_fcm_token_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/notifications/fcm-token', [
            'fcm_token' => 'test_fcm_token_12345',
            'device_type' => 'android',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test registering FCM token with invalid device_type
     */
    public function test_register_fcm_token_validates_device_type(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'test_fcm_token_12345',
                'device_type' => 'windows',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test registering FCM token without fcm_token
     */
    public function test_register_fcm_token_requires_fcm_token(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'device_type' => 'android',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test registering FCM token with optional fields
     */
    public function test_register_fcm_token_with_optional_fields(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'test_fcm_token_optional',
                'device_type' => 'ios',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'FCM token registered successfully',
            ]);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $this->user->id,
            'fcm_token' => 'test_fcm_token_optional',
            'device_type' => 'ios',
            'device_id' => null,
            'device_name' => null,
            'is_active' => true,
        ]);
    }

    /**
     * Test response includes token details
     */
    public function test_register_fcm_token_response_includes_token_details(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'test_fcm_token_details',
                'device_type' => 'android',
                'device_id' => 'device_789',
                'device_name' => 'Test Device',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'fcm_token',
                    'device_type',
                    'device_id',
                    'device_name',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertEquals('test_fcm_token_details', $response->json('data.fcm_token'));
        $this->assertEquals('android', $response->json('data.device_type'));
        $this->assertTrue($response->json('data.is_active'));
    }

    /**
     * Test multiple users can register different tokens
     */
    public function test_multiple_users_can_register_different_tokens(): void
    {
        $user2 = User::factory()->create();

        // Register token for user 1
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'user1_token',
                'device_type' => 'android',
            ]);

        // Register token for user 2
        $this->actingAs($user2, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'user2_token',
                'device_type' => 'ios',
            ]);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $this->user->id,
            'fcm_token' => 'user1_token',
        ]);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $user2->id,
            'fcm_token' => 'user2_token',
        ]);
    }

    /**
     * Test same user can register multiple tokens for different devices
     */
    public function test_same_user_can_register_multiple_tokens(): void
    {
        // Register first token
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'token_device_1',
                'device_type' => 'android',
                'device_name' => 'Device 1',
            ]);

        // Register second token
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'token_device_2',
                'device_type' => 'ios',
                'device_name' => 'Device 2',
            ]);

        $this->assertEquals(2, FcmToken::where('user_id', $this->user->id)->count());
    }

    /**
     * Test updating notification preferences
     */
    public function test_update_notification_preferences(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/preferences', [
                'preferences' => [
                    [
                        'notification_type' => 'ride_updates',
                        'is_enabled' => true,
                    ],
                    [
                        'notification_type' => 'messages',
                        'is_enabled' => false,
                    ],
                    [
                        'notification_type' => 'reviews',
                        'is_enabled' => true,
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification preferences updated successfully',
            ]);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'notification_type' => 'ride_updates',
            'is_enabled' => true,
        ]);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'notification_type' => 'messages',
            'is_enabled' => false,
        ]);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'notification_type' => 'reviews',
            'is_enabled' => true,
        ]);
    }

    /**
     * Test updating notification preferences requires authentication
     */
    public function test_update_notification_preferences_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/notifications/preferences', [
            'preferences' => [
                [
                    'notification_type' => 'ride_updates',
                    'is_enabled' => true,
                ],
            ],
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test updating notification preferences with invalid notification type
     */
    public function test_update_notification_preferences_validates_notification_type(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/preferences', [
                'preferences' => [
                    [
                        'notification_type' => 'invalid_type',
                        'is_enabled' => true,
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test updating notification preferences with missing is_enabled
     */
    public function test_update_notification_preferences_requires_is_enabled(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/preferences', [
                'preferences' => [
                    [
                        'notification_type' => 'ride_updates',
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test updating notification preferences with empty array
     */
    public function test_update_notification_preferences_requires_preferences_array(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/preferences', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test updating notification preferences response structure
     */
    public function test_update_notification_preferences_response_structure(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/preferences', [
                'preferences' => [
                    [
                        'notification_type' => 'promotions',
                        'is_enabled' => true,
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    [
                        'id',
                        'notification_type',
                        'is_enabled',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    /**
     * Test updating existing notification preferences
     */
    public function test_update_existing_notification_preferences(): void
    {
        // Create initial preference
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/preferences', [
                'preferences' => [
                    [
                        'notification_type' => 'system_alerts',
                        'is_enabled' => true,
                    ],
                ],
            ]);

        // Update the same preference
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/preferences', [
                'preferences' => [
                    [
                        'notification_type' => 'system_alerts',
                        'is_enabled' => false,
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification preferences updated successfully',
            ]);

        // Verify only one preference exists
        $this->assertEquals(1, \App\Models\NotificationPreference::where('user_id', $this->user->id)->count());

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'notification_type' => 'system_alerts',
            'is_enabled' => false,
        ]);
    }

    /**
     * Test updating all notification types
     */
    public function test_update_all_notification_types(): void
    {
        $allTypes = [
            'ride_updates',
            'messages',
            'reviews',
            'promotions',
            'system_alerts',
            'driver_requests',
            'booking_confirmations',
        ];

        $preferences = array_map(fn($type) => [
            'notification_type' => $type,
            'is_enabled' => true,
        ], $allTypes);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/preferences', [
                'preferences' => $preferences,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification preferences updated successfully',
            ]);

        $this->assertEquals(count($allTypes), \App\Models\NotificationPreference::where('user_id', $this->user->id)->count());
    }

    /**
     * Test getting notification preferences
     */
    public function test_get_notification_preferences(): void
    {
        // Create some preferences
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/preferences', [
                'preferences' => [
                    [
                        'notification_type' => 'ride_updates',
                        'is_enabled' => true,
                    ],
                    [
                        'notification_type' => 'messages',
                        'is_enabled' => false,
                    ],
                ],
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications/preferences');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test getting notification preferences requires authentication
     */
    public function test_get_notification_preferences_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/notifications/preferences');

        $response->assertStatus(401);
    }

    /**
     * Test getting all notifications (FCM tokens and preferences)
     */
    public function test_get_all_notifications(): void
    {
        // Register FCM token
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/fcm-token', [
                'fcm_token' => 'test_token_123',
                'device_type' => 'android',
            ]);

        // Create preferences
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/preferences', [
                'preferences' => [
                    [
                        'notification_type' => 'ride_updates',
                        'is_enabled' => true,
                    ],
                ],
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'fcm_tokens',
                    'preferences',
                ],
            ]);

        $this->assertCount(1, $response->json('data.fcm_tokens'));
        $this->assertCount(1, $response->json('data.preferences'));
    }
}
