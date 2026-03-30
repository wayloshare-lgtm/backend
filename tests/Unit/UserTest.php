<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\DriverProfile;
use App\Models\DriverVerification;
use App\Models\Vehicle;
use App\Models\Booking;
use App\Models\SavedRoute;
use App\Models\FcmToken;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that user can be created with all profile fields
     */
    public function test_user_can_be_created_with_profile_fields(): void
    {
        $user = User::create([
            'firebase_uid' => 'test-uid-123',
            'name' => 'John Doe',
            'display_name' => 'John',
            'phone' => '9876543210',
            'email' => 'john@example.com',
            'role' => 'driver',
            'gender' => 'male',
            'bio' => 'I am a driver',
            'profile_photo_url' => 'path/to/photo.jpg',
            'user_preference' => 'driver',
            'date_of_birth' => '1990-01-01',
            'onboarding_completed' => true,
            'profile_completed' => true,
        ]);

        $this->assertNotNull($user->id);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('John', $user->display_name);
        $this->assertEquals('male', $user->gender);
        $this->assertTrue($user->onboarding_completed);
        $this->assertTrue($user->profile_completed);
    }

    /**
     * Test that user has one driver profile
     */
    public function test_user_has_one_driver_profile(): void
    {
        $user = User::factory()->driver()->create();
        $profile = DriverProfile::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->driverProfile()->is($profile));
    }

    /**
     * Test that user has one driver verification
     */
    public function test_user_has_one_driver_verification(): void
    {
        $user = User::factory()->driver()->create();
        $verification = DriverVerification::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->driverVerification()->is($verification));
    }

    /**
     * Test that user has many vehicles
     */
    public function test_user_has_many_vehicles(): void
    {
        $user = User::factory()->driver()->create();

        Vehicle::factory()->create(['user_id' => $user->id]);
        Vehicle::factory()->create(['user_id' => $user->id]);

        $this->assertEquals(2, $user->vehicles()->count());
    }

    /**
     * Test that user has many bookings as passenger
     */
    public function test_user_has_many_bookings_as_passenger(): void
    {
        $user = User::factory()->create();

        Booking::factory()->create(['passenger_id' => $user->id]);
        Booking::factory()->create(['passenger_id' => $user->id]);

        $this->assertEquals(2, $user->bookings()->count());
    }

    /**
     * Test that user has many saved routes
     */
    public function test_user_has_many_saved_routes(): void
    {
        $user = User::factory()->create();

        SavedRoute::factory()->create(['user_id' => $user->id]);
        SavedRoute::factory()->create(['user_id' => $user->id]);

        $this->assertEquals(2, $user->savedRoutes()->count());
    }

    /**
     * Test that user has many FCM tokens
     */
    public function test_user_has_many_fcm_tokens(): void
    {
        $user = User::factory()->create();

        FcmToken::factory()->create(['user_id' => $user->id]);
        FcmToken::factory()->create(['user_id' => $user->id]);

        $this->assertEquals(2, $user->fcmTokens()->count());
    }

    /**
     * Test that user has many payment methods
     */
    public function test_user_has_many_payment_methods(): void
    {
        $user = User::factory()->create();

        PaymentMethod::factory()->create(['user_id' => $user->id]);
        PaymentMethod::factory()->create(['user_id' => $user->id]);

        $this->assertEquals(2, $user->paymentMethods()->count());
    }

    /**
     * Test that gender field is cast to string
     */
    public function test_gender_field_is_cast_to_string(): void
    {
        $user = User::create([
            'firebase_uid' => 'test-uid',
            'name' => 'Test User',
            'gender' => 'female',
        ]);

        $this->assertIsString($user->gender);
        $this->assertEquals('female', $user->gender);
    }

    /**
     * Test that user preference field is cast to string
     */
    public function test_user_preference_field_is_cast_to_string(): void
    {
        $user = User::create([
            'firebase_uid' => 'test-uid',
            'name' => 'Test User',
            'user_preference' => 'both',
        ]);

        $this->assertIsString($user->user_preference);
        $this->assertEquals('both', $user->user_preference);
    }

    /**
     * Test that date_of_birth is cast to date
     */
    public function test_date_of_birth_is_cast_to_date(): void
    {
        $user = User::create([
            'firebase_uid' => 'test-uid',
            'name' => 'Test User',
            'date_of_birth' => '1990-01-01',
        ]);

        $this->assertIsObject($user->date_of_birth);
    }

    /**
     * Test that onboarding_completed is cast to boolean
     */
    public function test_onboarding_completed_is_cast_to_boolean(): void
    {
        $user = User::create([
            'firebase_uid' => 'test-uid',
            'name' => 'Test User',
            'onboarding_completed' => 1,
        ]);

        $this->assertIsBool($user->onboarding_completed);
        $this->assertTrue($user->onboarding_completed);
    }

    /**
     * Test that profile_completed is cast to boolean
     */
    public function test_profile_completed_is_cast_to_boolean(): void
    {
        $user = User::create([
            'firebase_uid' => 'test-uid',
            'name' => 'Test User',
            'profile_completed' => 0,
        ]);

        $this->assertIsBool($user->profile_completed);
        $this->assertFalse($user->profile_completed);
    }

    /**
     * Test that profile_visibility field can be set
     */
    public function test_profile_visibility_field_can_be_set(): void
    {
        $user = User::create([
            'firebase_uid' => 'test-uid',
            'name' => 'Test User',
            'profile_visibility' => 'private',
        ]);

        $this->assertEquals('private', $user->profile_visibility);
    }

    /**
     * Test that show_phone field is cast to boolean
     */
    public function test_show_phone_field_is_cast_to_boolean(): void
    {
        $user = User::create([
            'firebase_uid' => 'test-uid',
            'name' => 'Test User',
            'show_phone' => true,
        ]);

        $this->assertIsBool($user->show_phone);
        $this->assertTrue($user->show_phone);
    }

    /**
     * Test that show_email field is cast to boolean
     */
    public function test_show_email_field_is_cast_to_boolean(): void
    {
        $user = User::create([
            'firebase_uid' => 'test-uid',
            'name' => 'Test User',
            'show_email' => false,
        ]);

        $this->assertIsBool($user->show_email);
        $this->assertFalse($user->show_email);
    }

    /**
     * Test that allow_messages field is cast to boolean
     */
    public function test_allow_messages_field_is_cast_to_boolean(): void
    {
        $user = User::create([
            'firebase_uid' => 'test-uid',
            'name' => 'Test User',
            'allow_messages' => true,
        ]);

        $this->assertIsBool($user->allow_messages);
        $this->assertTrue($user->allow_messages);
    }

    /**
     * Test that user can be updated with new profile fields
     */
    public function test_user_can_be_updated_with_profile_fields(): void
    {
        $user = User::factory()->create();

        $user->update([
            'display_name' => 'Updated Name',
            'bio' => 'Updated bio',
            'profile_completed' => true,
        ]);

        $this->assertEquals('Updated Name', $user->display_name);
        $this->assertEquals('Updated bio', $user->bio);
        $this->assertTrue($user->profile_completed);
    }

    /**
     * Test that profile_photo_url can be null
     */
    public function test_profile_photo_url_can_be_null(): void
    {
        $user = User::create([
            'firebase_uid' => 'test-uid',
            'name' => 'Test User',
            'profile_photo_url' => null,
        ]);

        $this->assertNull($user->profile_photo_url);
    }

    /**
     * Test that bio can be null
     */
    public function test_bio_can_be_null(): void
    {
        $user = User::create([
            'firebase_uid' => 'test-uid',
            'name' => 'Test User',
            'bio' => null,
        ]);

        $this->assertNull($user->bio);
    }
}
