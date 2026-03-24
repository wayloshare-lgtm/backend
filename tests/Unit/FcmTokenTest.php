<?php

namespace Tests\Unit;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FcmTokenTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that FcmToken model can be created with fcm_token field
     */
    public function test_fcm_token_can_be_created_with_token_field(): void
    {
        $user = User::factory()->create();

        $fcmToken = FcmToken::create([
            'user_id' => $user->id,
            'fcm_token' => 'test_fcm_token_12345',
            'device_type' => 'android',
            'device_id' => 'device_123',
            'device_name' => 'Samsung Galaxy S21',
            'is_active' => true,
        ]);

        $this->assertNotNull($fcmToken->id);
        $this->assertEquals('test_fcm_token_12345', $fcmToken->fcm_token);
        $this->assertEquals('android', $fcmToken->device_type);
        $this->assertTrue($fcmToken->is_active);
    }

    /**
     * Test that fcm_token field is in fillable array
     */
    public function test_fcm_token_field_is_fillable(): void
    {
        $fillable = (new FcmToken())->getFillable();
        $this->assertContains('fcm_token', $fillable);
    }

    /**
     * Test that FcmToken belongs to User
     */
    public function test_fcm_token_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $fcmToken = FcmToken::create([
            'user_id' => $user->id,
            'fcm_token' => 'test_token_123',
            'device_type' => 'ios',
        ]);

        $this->assertInstanceOf(User::class, $fcmToken->user);
        $this->assertEquals($user->id, $fcmToken->user->id);
    }

    /**
     * Test that User has many FcmTokens
     */
    public function test_user_has_many_fcm_tokens(): void
    {
        $user = User::factory()->create();

        FcmToken::create([
            'user_id' => $user->id,
            'fcm_token' => 'token_1',
            'device_type' => 'android',
        ]);

        FcmToken::create([
            'user_id' => $user->id,
            'fcm_token' => 'token_2',
            'device_type' => 'ios',
        ]);

        $this->assertCount(2, $user->fcmTokens);
    }

    /**
     * Test that fcm_token field has unique constraint
     */
    public function test_fcm_token_must_be_unique(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        FcmToken::create([
            'user_id' => $user1->id,
            'fcm_token' => 'unique_token_123',
            'device_type' => 'android',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        FcmToken::create([
            'user_id' => $user2->id,
            'fcm_token' => 'unique_token_123',
            'device_type' => 'ios',
        ]);
    }

    /**
     * Test that device_type field is in fillable array
     */
    public function test_device_type_field_is_fillable(): void
    {
        $fillable = (new FcmToken())->getFillable();
        $this->assertContains('device_type', $fillable);
    }

    /**
     * Test that device_type accepts both android and ios values
     */
    public function test_device_type_accepts_valid_values(): void
    {
        $user = User::factory()->create();

        $androidToken = FcmToken::create([
            'user_id' => $user->id,
            'fcm_token' => 'android_token_123',
            'device_type' => 'android',
        ]);

        $iosToken = FcmToken::create([
            'user_id' => $user->id,
            'fcm_token' => 'ios_token_123',
            'device_type' => 'ios',
        ]);

        $this->assertEquals('android', $androidToken->device_type);
        $this->assertEquals('ios', $iosToken->device_type);
    }

    /**
     * Test that device_type is cast to string
     */
    public function test_device_type_is_cast_to_string(): void
    {
        $user = User::factory()->create();

        $fcmToken = FcmToken::create([
            'user_id' => $user->id,
            'fcm_token' => 'test_token_cast',
            'device_type' => 'android',
        ]);

        $this->assertIsString($fcmToken->device_type);
    }

    /**
     * Test that device_id field is in fillable array
     */
    public function test_device_id_field_is_fillable(): void
    {
        $fillable = (new FcmToken())->getFillable();
        $this->assertContains('device_id', $fillable);
    }

    /**
     * Test that device_id can be set and retrieved
     */
    public function test_device_id_can_be_set_and_retrieved(): void
    {
        $user = User::factory()->create();

        $fcmToken = FcmToken::create([
            'user_id' => $user->id,
            'fcm_token' => 'test_token_device_id',
            'device_type' => 'android',
            'device_id' => 'device_abc123xyz',
        ]);

        $this->assertEquals('device_abc123xyz', $fcmToken->device_id);
    }

    /**
     * Test that device_id is nullable
     */
    public function test_device_id_is_nullable(): void
    {
        $user = User::factory()->create();

        $fcmToken = FcmToken::create([
            'user_id' => $user->id,
            'fcm_token' => 'test_token_nullable',
            'device_type' => 'ios',
        ]);

        $this->assertNull($fcmToken->device_id);
    }
}
