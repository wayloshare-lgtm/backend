<?php

namespace Tests\Unit;

use App\Models\DriverVerification;
use App\Models\User;
use App\Enums\VerificationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that driver verification can be created with all attributes
     */
    public function test_driver_verification_can_be_created(): void
    {
        $user = User::factory()->driver()->create();

        $verification = DriverVerification::create([
            'user_id' => $user->id,
            'dl_number' => 'DL123456789',
            'dl_expiry_date' => now()->addYears(5)->toDateString(),
            'dl_front_image' => 'path/to/dl_front.jpg',
            'dl_back_image' => 'path/to/dl_back.jpg',
            'rc_number' => 'RC987654321',
            'rc_expiry_date' => now()->addYears(5)->toDateString(),
            'rc_front_image' => 'path/to/rc_front.jpg',
            'rc_back_image' => 'path/to/rc_back.jpg',
            'verification_status' => VerificationStatus::PENDING,
        ]);

        $this->assertNotNull($verification->id);
        $this->assertEquals($user->id, $verification->user_id);
        $this->assertEquals('DL123456789', $verification->dl_number);
        $this->assertEquals('RC987654321', $verification->rc_number);
    }

    /**
     * Test that driver verification belongs to a user
     */
    public function test_driver_verification_belongs_to_user(): void
    {
        $user = User::factory()->driver()->create();
        $verification = DriverVerification::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($verification->user()->is($user));
    }

    /**
     * Test that verification status is cast to enum
     */
    public function test_verification_status_is_cast_to_enum(): void
    {
        $user = User::factory()->driver()->create();

        $verification = DriverVerification::create([
            'user_id' => $user->id,
            'dl_number' => 'DL123456789',
            'verification_status' => VerificationStatus::APPROVED,
        ]);

        $this->assertInstanceOf(VerificationStatus::class, $verification->verification_status);
        $this->assertEquals(VerificationStatus::APPROVED, $verification->verification_status);
    }

    /**
     * Test that dates are cast to date objects
     */
    public function test_dates_are_cast_to_date_objects(): void
    {
        $user = User::factory()->driver()->create();
        $expiryDate = now()->addYears(5)->toDateString();

        $verification = DriverVerification::create([
            'user_id' => $user->id,
            'dl_number' => 'DL123456789',
            'dl_expiry_date' => $expiryDate,
            'rc_expiry_date' => $expiryDate,
        ]);

        $this->assertIsObject($verification->dl_expiry_date);
        $this->assertIsObject($verification->rc_expiry_date);
    }

    /**
     * Test that verified_at timestamp can be set
     */
    public function test_verified_at_timestamp_can_be_set(): void
    {
        $user = User::factory()->driver()->create();
        $verifiedAt = now();

        $verification = DriverVerification::create([
            'user_id' => $user->id,
            'dl_number' => 'DL123456789',
            'verification_status' => VerificationStatus::APPROVED,
            'verified_at' => $verifiedAt,
        ]);

        $this->assertNotNull($verification->verified_at);
        $this->assertIsObject($verification->verified_at);
    }

    /**
     * Test that rejection reason can be stored
     */
    public function test_rejection_reason_can_be_stored(): void
    {
        $user = User::factory()->driver()->create();
        $reason = 'Document expired';

        $verification = DriverVerification::create([
            'user_id' => $user->id,
            'dl_number' => 'DL123456789',
            'verification_status' => VerificationStatus::REJECTED,
            'rejection_reason' => $reason,
        ]);

        $this->assertEquals($reason, $verification->rejection_reason);
    }

    /**
     * Test that verification status can be updated
     */
    public function test_verification_status_can_be_updated(): void
    {
        $user = User::factory()->driver()->create();
        $verification = DriverVerification::factory()->create([
            'user_id' => $user->id,
            'verification_status' => VerificationStatus::PENDING,
        ]);

        $verification->update(['verification_status' => VerificationStatus::APPROVED]);

        $this->assertEquals(VerificationStatus::APPROVED, $verification->verification_status);
    }

    /**
     * Test that all document fields can be null
     */
    public function test_document_fields_can_be_null(): void
    {
        $user = User::factory()->driver()->create();

        $verification = DriverVerification::create([
            'user_id' => $user->id,
            'dl_number' => 'DL123456789',
            'dl_front_image' => null,
            'dl_back_image' => null,
            'rc_front_image' => null,
            'rc_back_image' => null,
        ]);

        $this->assertNull($verification->dl_front_image);
        $this->assertNull($verification->dl_back_image);
        $this->assertNull($verification->rc_front_image);
        $this->assertNull($verification->rc_back_image);
    }

    /**
     * Test that verification is deleted when user is deleted
     */
    public function test_verification_deleted_when_user_deleted(): void
    {
        $user = User::factory()->driver()->create();
        $verification = DriverVerification::factory()->create(['user_id' => $user->id]);

        $verificationId = $verification->id;
        $user->delete();

        $this->assertNull(DriverVerification::find($verificationId));
    }
}
