<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DriverVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class DateValidationTest extends TestCase
{
    use RefreshDatabase;

    // ==================== Date of Birth Validation Tests ====================

    /**
     * Test DOB validation - valid age (18+ years)
     */
    public function test_dob_validation_valid_age_18_years()
    {
        $user = User::factory()->create();
        $dobDate = Carbon::now()->subYears(18)->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile', [
                'date_of_birth' => $dobDate,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test DOB validation - valid age (older than 18)
     */
    public function test_dob_validation_valid_age_older_than_18()
    {
        $user = User::factory()->create();
        $dobDate = Carbon::now()->subYears(30)->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile', [
                'date_of_birth' => $dobDate,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test DOB validation - invalid age (under 18)
     */
    public function test_dob_validation_invalid_age_under_18()
    {
        $user = User::factory()->create();
        $dobDate = Carbon::now()->subYears(17)->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile', [
                'date_of_birth' => $dobDate,
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonPath('errors.date_of_birth.0', 'The date of birth must be a valid date of birth with age at least 18 years.');
    }

    /**
     * Test DOB validation - future date (not born yet)
     */
    public function test_dob_validation_future_date()
    {
        $user = User::factory()->create();
        $dobDate = Carbon::now()->addDays(1)->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile', [
                'date_of_birth' => $dobDate,
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /**
     * Test DOB validation - today's date (age 0)
     */
    public function test_dob_validation_today_date()
    {
        $user = User::factory()->create();
        $dobDate = Carbon::now()->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/profile', [
                'date_of_birth' => $dobDate,
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /**
     * Test DOB validation in onboarding - valid age
     */
    public function test_dob_validation_onboarding_valid()
    {
        $user = User::factory()->create();
        $dobDate = Carbon::now()->subYears(25)->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/complete-onboarding', [
                'display_name' => 'John Doe',
                'date_of_birth' => $dobDate,
                'gender' => 'male',
                'user_preference' => 'driver',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test DOB validation in onboarding - invalid age
     */
    public function test_dob_validation_onboarding_invalid()
    {
        $user = User::factory()->create();
        $dobDate = Carbon::now()->subYears(16)->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/complete-onboarding', [
                'display_name' => 'John Doe',
                'date_of_birth' => $dobDate,
                'gender' => 'male',
                'user_preference' => 'driver',
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    // ==================== Expiry Date Validation Tests ====================

    /**
     * Test DL expiry date validation - valid future date
     */
    public function test_dl_expiry_date_validation_valid_future()
    {
        $user = User::factory()->create(['role' => 'driver']);
        $expiryDate = Carbon::now()->addYears(2)->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification', [
                'dl_number' => 'DL123456789',
                'dl_expiry_date' => $expiryDate,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test DL expiry date validation - invalid past date
     */
    public function test_dl_expiry_date_validation_invalid_past()
    {
        $user = User::factory()->create(['role' => 'driver']);
        $expiryDate = Carbon::now()->subDays(1)->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification', [
                'dl_number' => 'DL123456789',
                'dl_expiry_date' => $expiryDate,
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonPath('errors.dl_expiry_date.0', 'The dl expiry date must be a future date.');
    }

    /**
     * Test DL expiry date validation - today's date (invalid)
     */
    public function test_dl_expiry_date_validation_today()
    {
        $user = User::factory()->create(['role' => 'driver']);
        $expiryDate = Carbon::now()->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification', [
                'dl_number' => 'DL123456789',
                'dl_expiry_date' => $expiryDate,
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /**
     * Test RC expiry date validation - valid future date
     */
    public function test_rc_expiry_date_validation_valid_future()
    {
        $user = User::factory()->create(['role' => 'driver']);
        $expiryDate = Carbon::now()->addYears(3)->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification', [
                'rc_number' => 'RC123456789',
                'rc_expiry_date' => $expiryDate,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test RC expiry date validation - invalid past date
     */
    public function test_rc_expiry_date_validation_invalid_past()
    {
        $user = User::factory()->create(['role' => 'driver']);
        $expiryDate = Carbon::now()->subMonths(6)->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification', [
                'rc_number' => 'RC123456789',
                'rc_expiry_date' => $expiryDate,
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonPath('errors.rc_expiry_date.0', 'The rc expiry date must be a future date.');
    }

    /**
     * Test both DL and RC expiry dates validation
     */
    public function test_both_expiry_dates_validation()
    {
        $user = User::factory()->create(['role' => 'driver']);
        $dlExpiryDate = Carbon::now()->addYears(2)->format('Y-m-d');
        $rcExpiryDate = Carbon::now()->addYears(3)->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification', [
                'dl_number' => 'DL123456789',
                'dl_expiry_date' => $dlExpiryDate,
                'rc_number' => 'RC123456789',
                'rc_expiry_date' => $rcExpiryDate,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test both expiry dates with one invalid
     */
    public function test_both_expiry_dates_one_invalid()
    {
        $user = User::factory()->create(['role' => 'driver']);
        $dlExpiryDate = Carbon::now()->addYears(2)->format('Y-m-d');
        $rcExpiryDate = Carbon::now()->subDays(1)->format('Y-m-d');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification', [
                'dl_number' => 'DL123456789',
                'dl_expiry_date' => $dlExpiryDate,
                'rc_number' => 'RC123456789',
                'rc_expiry_date' => $rcExpiryDate,
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonPath('errors.rc_expiry_date.0', 'The rc expiry date must be a future date.');
    }

    /**
     * Test expiry date validation - nullable field
     */
    public function test_expiry_date_validation_nullable()
    {
        $user = User::factory()->create(['role' => 'driver']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification', [
                'dl_number' => 'DL123456789',
                'dl_expiry_date' => null,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /**
     * Test expiry date validation - invalid date format
     */
    public function test_expiry_date_validation_invalid_format()
    {
        $user = User::factory()->create(['role' => 'driver']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification', [
                'dl_number' => 'DL123456789',
                'dl_expiry_date' => 'invalid-date',
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /**
     * Test KYC status includes expiry dates
     */
    public function test_kyc_status_includes_expiry_dates()
    {
        $user = User::factory()->create(['role' => 'driver']);
        $dlExpiryDate = Carbon::now()->addYears(2)->format('Y-m-d');
        $rcExpiryDate = Carbon::now()->addYears(3)->format('Y-m-d');

        DriverVerification::create([
            'user_id' => $user->id,
            'dl_number' => 'DL123456789',
            'dl_expiry_date' => $dlExpiryDate,
            'rc_number' => 'RC123456789',
            'rc_expiry_date' => $rcExpiryDate,
            'verification_status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/driver/kyc-status');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('kyc_status.details_filled.dl_expiry_date', true)
            ->assertJsonPath('kyc_status.details_filled.rc_expiry_date', true);
    }
}
