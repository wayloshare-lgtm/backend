<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DriverVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DriverVerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    /**
     * Test get verification status when no record exists
     */
    public function test_get_verification_status_no_record(): void
    {
        $user = User::factory()->create(['role' => 'driver']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/driver/verification/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'verification' => null,
                'message' => 'No verification record found',
            ]);
    }

    /**
     * Test get verification status with existing record
     */
    public function test_get_verification_status_with_record(): void
    {
        $user = User::factory()->create(['role' => 'driver']);
        $verification = DriverVerification::create([
            'user_id' => $user->id,
            'dl_number' => 'DL123456',
            'verification_status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/driver/verification/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('verification.id', $verification->id)
            ->assertJsonPath('verification.dl_number', 'DL123456')
            ->assertJsonPath('verification.verification_status', 'pending');
    }

    /**
     * Test upload dl_front_image successfully
     */
    public function test_upload_dl_front_image_success(): void
    {
        $user = User::factory()->create(['role' => 'driver']);
        $file = UploadedFile::fake()->image('dl_front.jpg', 100, 100);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification/dl-front-image', [
                'dl_front_image' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Driving license front image uploaded successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'dl_front_image_url',
                'verification' => [
                    'id',
                    'user_id',
                    'dl_front_image',
                    'verification_status',
                ],
            ]);

        // Verify file was stored
        $verification = DriverVerification::where('user_id', $user->id)->first();
        $this->assertNotNull($verification);
        $this->assertNotNull($verification->dl_front_image);
        Storage::disk('private')->assertExists($verification->dl_front_image);
    }

    /**
     * Test upload dl_front_image creates verification record if not exists
     */
    public function test_upload_dl_front_image_creates_verification(): void
    {
        $user = User::factory()->create(['role' => 'driver']);
        $file = UploadedFile::fake()->image('dl_front.jpg', 100, 100);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification/dl-front-image', [
                'dl_front_image' => $file,
            ]);

        $verification = DriverVerification::where('user_id', $user->id)->first();
        $this->assertNotNull($verification);
        $this->assertEquals('pending', $verification->verification_status);
    }

    /**
     * Test upload dl_front_image replaces old image
     */
    public function test_upload_dl_front_image_replaces_old(): void
    {
        $user = User::factory()->create(['role' => 'driver']);
        $verification = DriverVerification::create([
            'user_id' => $user->id,
            'dl_front_image' => 'driver-verifications/dl-front/old-image.jpg',
            'verification_status' => 'pending',
        ]);

        $file = UploadedFile::fake()->image('dl_front_new.jpg', 100, 100);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification/dl-front-image', [
                'dl_front_image' => $file,
            ]);

        $response->assertStatus(200);

        $verification->refresh();
        $this->assertNotEquals('driver-verifications/dl-front/old-image.jpg', $verification->dl_front_image);
    }

    /**
     * Test upload dl_front_image with invalid file type
     */
    public function test_upload_dl_front_image_invalid_type(): void
    {
        $user = User::factory()->create(['role' => 'driver']);
        $file = UploadedFile::fake()->create('document.txt', 100);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification/dl-front-image', [
                'dl_front_image' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test upload dl_front_image without authentication
     */
    public function test_upload_dl_front_image_unauthenticated(): void
    {
        $file = UploadedFile::fake()->image('dl_front.jpg', 100, 100);

        $response = $this->postJson('/api/v1/driver/verification/dl-front-image', [
            'dl_front_image' => $file,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test upload dl_front_image without file
     */
    public function test_upload_dl_front_image_missing_file(): void
    {
        $user = User::factory()->create(['role' => 'driver']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification/dl-front-image', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test get verification status without authentication
     */
    public function test_get_verification_status_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/driver/verification/status');

        $response->assertStatus(401);
    }

    /**
     * Test upload rc_front_image successfully
     */
    public function test_upload_rc_front_image_success(): void
    {
        $user = User::factory()->create(['role' => 'driver']);
        $file = UploadedFile::fake()->image('rc_front.jpg', 100, 100);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification/rc-front-image', [
                'rc_front_image' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Registration certificate front image uploaded successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'rc_front_image_url',
                'verification' => [
                    'id',
                    'user_id',
                    'rc_front_image',
                    'verification_status',
                ],
            ]);

        // Verify file was stored
        $verification = DriverVerification::where('user_id', $user->id)->first();
        $this->assertNotNull($verification);
        $this->assertNotNull($verification->rc_front_image);
        Storage::disk('private')->assertExists($verification->rc_front_image);
    }

    /**
     * Test upload rc_front_image creates verification record if not exists
     */
    public function test_upload_rc_front_image_creates_verification(): void
    {
        $user = User::factory()->create(['role' => 'driver']);
        $file = UploadedFile::fake()->image('rc_front.jpg', 100, 100);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification/rc-front-image', [
                'rc_front_image' => $file,
            ]);

        $verification = DriverVerification::where('user_id', $user->id)->first();
        $this->assertNotNull($verification);
        $this->assertEquals('pending', $verification->verification_status);
    }

    /**
     * Test upload rc_front_image replaces old image
     */
    public function test_upload_rc_front_image_replaces_old(): void
    {
        $user = User::factory()->create(['role' => 'driver']);
        $verification = DriverVerification::create([
            'user_id' => $user->id,
            'rc_front_image' => 'driver-verifications/rc-front/old-image.jpg',
            'verification_status' => 'pending',
        ]);

        $file = UploadedFile::fake()->image('rc_front_new.jpg', 100, 100);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification/rc-front-image', [
                'rc_front_image' => $file,
            ]);

        $response->assertStatus(200);

        $verification->refresh();
        $this->assertNotEquals('driver-verifications/rc-front/old-image.jpg', $verification->rc_front_image);
    }

    /**
     * Test upload rc_front_image with invalid file type
     */
    public function test_upload_rc_front_image_invalid_type(): void
    {
        $user = User::factory()->create(['role' => 'driver']);
        $file = UploadedFile::fake()->create('document.txt', 100);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification/rc-front-image', [
                'rc_front_image' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test upload rc_front_image without authentication
     */
    public function test_upload_rc_front_image_unauthenticated(): void
    {
        $file = UploadedFile::fake()->image('rc_front.jpg', 100, 100);

        $response = $this->postJson('/api/v1/driver/verification/rc-front-image', [
            'rc_front_image' => $file,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test upload rc_front_image without file
     */
    public function test_upload_rc_front_image_missing_file(): void
    {
        $user = User::factory()->create(['role' => 'driver']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/driver/verification/rc-front-image', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test get KYC status when no verification record exists
     */
    public function test_get_kyc_status_not_started(): void
    {
        $user = User::factory()->create(['role' => 'driver']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/driver/kyc-status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'kyc_status' => 'not_started',
                'message' => 'KYC process not started',
            ]);
    }

    /**
     * Test get KYC status with pending verification
     */
    public function test_get_kyc_status_pending(): void
    {
        $user = User::factory()->create(['role' => 'driver']);
        $verification = DriverVerification::create([
            'user_id' => $user->id,
            'dl_number' => 'DL123456',
            'dl_expiry_date' => '2025-12-31',
            'rc_number' => 'RC123456',
            'verification_status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/driver/kyc-status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'kyc_status' => [
                    'status' => 'pending',
                    'documents_uploaded' => [
                        'dl_front' => false,
                        'dl_back' => false,
                        'rc_front' => false,
                        'rc_back' => false,
                    ],
                    'details_filled' => [
                        'dl_number' => true,
                        'dl_expiry_date' => true,
                        'rc_number' => true,
                    ],
                ],
            ]);
    }

    /**
     * Test get KYC status with approved verification
     */
    public function test_get_kyc_status_approved(): void
    {
        $user = User::factory()->create(['role' => 'driver']);
        $verifiedAt = now();
        $verification = DriverVerification::create([
            'user_id' => $user->id,
            'dl_number' => 'DL123456',
            'dl_expiry_date' => '2025-12-31',
            'rc_number' => 'RC123456',
            'verification_status' => 'approved',
            'verified_at' => $verifiedAt,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/driver/kyc-status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'kyc_status' => [
                    'status' => 'approved',
                ],
            ]);
        
        // Verify that verified_at is present and not null
        $this->assertNotNull($response->json('kyc_status.verified_at'));
    }

    /**
     * Test get KYC status with rejected verification
     */
    public function test_get_kyc_status_rejected(): void
    {
        $user = User::factory()->create(['role' => 'driver']);
        $verification = DriverVerification::create([
            'user_id' => $user->id,
            'dl_number' => 'DL123456',
            'dl_expiry_date' => '2025-12-31',
            'rc_number' => 'RC123456',
            'verification_status' => 'rejected',
            'rejection_reason' => 'Document quality is poor',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/driver/kyc-status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'kyc_status' => [
                    'status' => 'rejected',
                    'rejection_reason' => 'Document quality is poor',
                ],
            ]);
    }

    /**
     * Test get KYC status with uploaded documents
     */
    public function test_get_kyc_status_with_documents(): void
    {
        $user = User::factory()->create(['role' => 'driver']);
        $verification = DriverVerification::create([
            'user_id' => $user->id,
            'dl_number' => 'DL123456',
            'dl_expiry_date' => '2025-12-31',
            'dl_front_image' => 'driver-verifications/dl-front/image.jpg',
            'dl_back_image' => 'driver-verifications/dl-back/image.jpg',
            'rc_number' => 'RC123456',
            'rc_front_image' => 'driver-verifications/rc-front/image.jpg',
            'verification_status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/driver/kyc-status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'kyc_status' => [
                    'status' => 'pending',
                    'documents_uploaded' => [
                        'dl_front' => true,
                        'dl_back' => true,
                        'rc_front' => true,
                        'rc_back' => false,
                    ],
                ],
            ]);
    }

    /**
     * Test get KYC status without authentication
     */
    public function test_get_kyc_status_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/driver/kyc-status');

        $response->assertStatus(401);
    }
}
