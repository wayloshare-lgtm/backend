<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadValidationFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    /**
     * Test profile photo upload with valid file
     */
    public function test_profile_photo_upload_with_valid_file(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('profile.jpg', 100, 100);

        $response = $this->actingAs($user)
            ->post('/api/v1/user/profile/photo', [
                'profile_photo' => $file,
            ]);

        $response->assertStatus(200);
        $this->assertNotNull($user->fresh()->profile_photo_url);
    }

    /**
     * Test profile photo upload with oversized file
     */
    public function test_profile_photo_upload_with_oversized_file(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('large.jpg', 11 * 1024); // 11MB

        $response = $this->actingAs($user)
            ->post('/api/v1/user/profile/photo', [
                'profile_photo' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('profile_photo');
    }

    /**
     * Test profile photo upload with invalid file type
     */
    public function test_profile_photo_upload_with_invalid_file_type(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($user)
            ->post('/api/v1/user/profile/photo', [
                'profile_photo' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('profile_photo');
    }

    /**
     * Test profile photo upload with invalid extension
     */
    public function test_profile_photo_upload_with_invalid_extension(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.txt', 100);

        $response = $this->actingAs($user)
            ->post('/api/v1/user/profile/photo', [
                'profile_photo' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('profile_photo');
    }

    /**
     * Test driver verification document upload with valid file
     */
    public function test_driver_verification_document_upload_with_valid_file(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('dl_front.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)
            ->post('/api/v1/driver/verification/documents', [
                'dl_front_image' => $file,
            ]);

        $response->assertStatus(200);
    }

    /**
     * Test driver verification document upload with oversized file
     */
    public function test_driver_verification_document_upload_with_oversized_file(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('large.pdf', 11 * 1024); // 11MB

        $response = $this->actingAs($user)
            ->post('/api/v1/driver/verification/documents', [
                'dl_front_image' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('dl_front_image');
    }

    /**
     * Test vehicle photo upload with valid file
     */
    public function test_vehicle_photo_upload_with_valid_file(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('vehicle.jpg', 200, 200);

        $response = $this->actingAs($user)
            ->post('/api/v1/vehicles', [
                'vehicle_name' => 'My Car',
                'vehicle_type' => 'sedan',
                'license_plate' => 'ABC123',
                'vehicle_color' => 'black',
                'vehicle_year' => 2023,
                'vehicle_photo' => $file,
            ]);

        $response->assertStatus(201);
    }

    /**
     * Test vehicle photo upload with invalid file type
     */
    public function test_vehicle_photo_upload_with_invalid_file_type(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($user)
            ->post('/api/v1/vehicles', [
                'vehicle_name' => 'My Car',
                'vehicle_type' => 'sedan',
                'license_plate' => 'ABC123',
                'vehicle_color' => 'black',
                'vehicle_year' => 2023,
                'vehicle_photo' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('vehicle_photo');
    }

    /**
     * Test multiple file uploads in single request
     */
    public function test_multiple_file_uploads_in_single_request(): void
    {
        $user = User::factory()->create();
        $dlFront = UploadedFile::fake()->image('dl_front.jpg', 100, 100);
        $dlBack = UploadedFile::fake()->image('dl_back.jpg', 100, 100);

        $response = $this->actingAs($user)
            ->post('/api/v1/driver/verification/documents', [
                'dl_front_image' => $dlFront,
                'dl_back_image' => $dlBack,
            ]);

        $response->assertStatus(200);
    }

    /**
     * Test multiple file uploads with one invalid file
     */
    public function test_multiple_file_uploads_with_one_invalid_file(): void
    {
        $user = User::factory()->create();
        $dlFront = UploadedFile::fake()->image('dl_front.jpg', 100, 100);
        $dlBack = UploadedFile::fake()->create('dl_back.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($user)
            ->post('/api/v1/driver/verification/documents', [
                'dl_front_image' => $dlFront,
                'dl_back_image' => $dlBack,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('dl_back_image');
    }

    /**
     * Test file upload without authentication
     */
    public function test_file_upload_without_authentication(): void
    {
        $file = UploadedFile::fake()->image('profile.jpg', 100, 100);

        $response = $this->post('/api/v1/user/profile/photo', [
            'profile_photo' => $file,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test file upload with PNG format
     */
    public function test_file_upload_with_png_format(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('profile.png', 100, 100);

        $response = $this->actingAs($user)
            ->post('/api/v1/user/profile/photo', [
                'profile_photo' => $file,
            ]);

        $response->assertStatus(200);
    }

    /**
     * Test file upload with JPEG format
     */
    public function test_file_upload_with_jpeg_format(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('profile.jpeg', 100, 100);

        $response = $this->actingAs($user)
            ->post('/api/v1/user/profile/photo', [
                'profile_photo' => $file,
            ]);

        $response->assertStatus(200);
    }

    /**
     * Test file upload with PDF format
     */
    public function test_file_upload_with_pdf_format(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)
            ->post('/api/v1/driver/verification/documents', [
                'dl_front_image' => $file,
            ]);

        $response->assertStatus(200);
    }

    /**
     * Test file upload with maximum allowed size
     */
    public function test_file_upload_with_maximum_allowed_size(): void
    {
        $user = User::factory()->create();
        // Create a file exactly at 10MB limit
        $file = UploadedFile::fake()->create('large.pdf', 10 * 1024, 'application/pdf');

        $response = $this->actingAs($user)
            ->post('/api/v1/driver/verification/documents', [
                'dl_front_image' => $file,
            ]);

        $response->assertStatus(200);
    }

    /**
     * Test file upload with just over maximum allowed size
     */
    public function test_file_upload_with_just_over_maximum_allowed_size(): void
    {
        $user = User::factory()->create();
        // Create a file just over 10MB limit
        $file = UploadedFile::fake()->create('large.pdf', (10 * 1024) + 1, 'application/pdf');

        $response = $this->actingAs($user)
            ->post('/api/v1/driver/verification/documents', [
                'dl_front_image' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('dl_front_image');
    }
}
