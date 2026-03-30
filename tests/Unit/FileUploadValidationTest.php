<?php

namespace Tests\Unit;

use App\Rules\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    /**
     * Test file size validation - valid file
     */
    public function test_valid_file_size_passes_validation(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);
        $rule = new FileUpload();

        $errors = [];
        $rule->validate('file', $file, function ($message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertEmpty($errors);
    }

    /**
     * Test file size validation - exceeds limit
     */
    public function test_file_exceeding_size_limit_fails_validation(): void
    {
        // Create a file that exceeds 10MB
        $file = UploadedFile::fake()->create('large.pdf', 11 * 1024); // 11MB
        $rule = new FileUpload();

        $errors = [];
        $rule->validate('file', $file, function ($message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('must not exceed', $errors[0]);
    }

    /**
     * Test custom max size validation
     */
    public function test_custom_max_size_validation(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 6 * 1024); // 6MB
        $rule = (new FileUpload())->maxSize(5 * 1024 * 1024); // 5MB limit

        $errors = [];
        $rule->validate('file', $file, function ($message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('must not exceed', $errors[0]);
    }

    /**
     * Test MIME type validation - valid type
     */
    public function test_valid_mime_type_passes_validation(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $rule = new FileUpload();

        $errors = [];
        $rule->validate('file', $file, function ($message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertEmpty($errors);
    }

    /**
     * Test MIME type validation - invalid type
     */
    public function test_invalid_mime_type_fails_validation(): void
    {
        $file = UploadedFile::fake()->create('test.exe', 100, 'application/x-msdownload');
        $rule = new FileUpload();

        $errors = [];
        $rule->validate('file', $file, function ($message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('must be one of', $errors[0]);
    }

    /**
     * Test custom MIME types validation
     */
    public function test_custom_mime_types_validation(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $rule = (new FileUpload())->mimeTypes(['image/png']);

        $errors = [];
        $rule->validate('file', $file, function ($message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('must be one of', $errors[0]);
    }

    /**
     * Test file extension validation - valid extension
     */
    public function test_valid_extension_passes_validation(): void
    {
        $file = UploadedFile::fake()->image('test.png');
        $rule = new FileUpload();

        $errors = [];
        $rule->validate('file', $file, function ($message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertEmpty($errors);
    }

    /**
     * Test file extension validation - invalid extension
     */
    public function test_invalid_extension_fails_validation(): void
    {
        $file = UploadedFile::fake()->create('test.txt', 100);
        $rule = new FileUpload();

        $errors = [];
        $rule->validate('file', $file, function ($message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('must have one of these extensions', $errors[0]);
    }

    /**
     * Test custom extensions validation
     */
    public function test_custom_extensions_validation(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $rule = (new FileUpload())->extensions(['png', 'gif']);

        $errors = [];
        $rule->validate('file', $file, function ($message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('must have one of these extensions', $errors[0]);
    }

    /**
     * Test PDF file validation
     */
    public function test_pdf_file_passes_validation(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');
        $rule = new FileUpload();

        $errors = [];
        $rule->validate('file', $file, function ($message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertEmpty($errors);
    }

    /**
     * Test non-file value fails validation
     */
    public function test_non_file_value_fails_validation(): void
    {
        $rule = new FileUpload();

        $errors = [];
        $rule->validate('file', 'not-a-file', function ($message) use (&$errors) {
            $errors[] = $message;
        });

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('must be a file', $errors[0]);
    }

    /**
     * Test chaining multiple validations
     */
    public function test_chaining_multiple_validations(): void
    {
        $file = UploadedFile::fake()->create('test.jpg', 6 * 1024);
        $rule = (new FileUpload())
            ->maxSize(5 * 1024 * 1024)
            ->extensions(['png', 'gif']);

        $errors = [];
        $rule->validate('file', $file, function ($message) use (&$errors) {
            $errors[] = $message;
        });

        // Should fail on size first
        $this->assertNotEmpty($errors);
    }

    /**
     * Test all allowed file types
     */
    public function test_all_allowed_file_types(): void
    {
        $files = [
            UploadedFile::fake()->image('test.jpg'),
            UploadedFile::fake()->image('test.jpeg'),
            UploadedFile::fake()->image('test.png'),
            UploadedFile::fake()->create('test.pdf', 100, 'application/pdf'),
        ];

        $rule = new FileUpload();

        foreach ($files as $file) {
            $errors = [];
            $rule->validate('file', $file, function ($message) use (&$errors) {
                $errors[] = $message;
            });

            $this->assertEmpty($errors, "File {$file->getClientOriginalName()} should pass validation");
        }
    }
}
