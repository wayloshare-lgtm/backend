<?php

namespace Tests\Unit;

use App\Services\InputSanitizationService;
use PHPUnit\Framework\TestCase;

class InputSanitizationServiceTest extends TestCase
{
    private InputSanitizationService $sanitizationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizationService = new InputSanitizationService();
    }

    // String Sanitization Tests
    public function test_sanitize_string_removes_html_tags(): void
    {
        $input = '<script>alert("XSS")</script>Hello';
        $result = $this->sanitizationService->sanitizeString($input);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('</script>', $result);
        $this->assertStringContainsString('Hello', $result);
    }

    public function test_sanitize_string_encodes_special_characters(): void
    {
        $input = '<img src=x onerror="alert(\'XSS\')">';
        $result = $this->sanitizationService->sanitizeString($input);
        $this->assertStringNotContainsString('<img', $result);
        $this->assertStringNotContainsString('onerror', $result);
    }

    public function test_sanitize_string_removes_null_bytes(): void
    {
        $input = "Hello\0World";
        $result = $this->sanitizationService->sanitizeString($input);
        $this->assertStringNotContainsString("\0", $result);
    }

    public function test_sanitize_string_trims_whitespace(): void
    {
        $input = '  Hello World  ';
        $result = $this->sanitizationService->sanitizeString($input);
        $this->assertEquals('Hello World', $result);
    }

    public function test_sanitize_string_handles_null_input(): void
    {
        $result = $this->sanitizationService->sanitizeString(null);
        $this->assertNull($result);
    }

    public function test_sanitize_string_handles_empty_string(): void
    {
        $result = $this->sanitizationService->sanitizeString('');
        $this->assertEquals('', $result);
    }

    // Text Sanitization Tests
    public function test_sanitize_text_preserves_newlines(): void
    {
        $input = "Line 1\nLine 2\nLine 3";
        $result = $this->sanitizationService->sanitizeText($input);
        $this->assertStringContainsString("\n", $result);
    }

    public function test_sanitize_text_removes_html_with_newlines(): void
    {
        $input = "<script>alert('XSS')</script>\nHello\nWorld";
        $result = $this->sanitizationService->sanitizeText($input);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString("\n", $result);
    }

    // Filename Sanitization Tests
    public function test_sanitize_filename_removes_path_separators(): void
    {
        $input = '../../../etc/passwd';
        $result = $this->sanitizationService->sanitizeFilename($input);
        $this->assertStringNotContainsString('/', $result);
        $this->assertStringNotContainsString('\\', $result);
        $this->assertStringNotContainsString('..', $result);
    }

    public function test_sanitize_filename_removes_special_characters(): void
    {
        $input = 'file@#$%^&*().txt';
        $result = $this->sanitizationService->sanitizeFilename($input);
        $this->assertStringNotContainsString('@', $result);
        $this->assertStringNotContainsString('#', $result);
        $this->assertStringNotContainsString('$', $result);
    }

    public function test_sanitize_filename_preserves_valid_characters(): void
    {
        $input = 'my-file_123.txt';
        $result = $this->sanitizationService->sanitizeFilename($input);
        $this->assertStringContainsString('my', $result);
        $this->assertStringContainsString('file', $result);
        $this->assertStringContainsString('123', $result);
        $this->assertStringContainsString('.txt', $result);
    }

    // Path Sanitization Tests
    public function test_sanitize_path_removes_directory_traversal(): void
    {
        $input = '../../sensitive/file.txt';
        $result = $this->sanitizationService->sanitizePath($input);
        $this->assertStringNotContainsString('..', $result);
    }

    public function test_sanitize_path_normalizes_separators(): void
    {
        $input = 'path\\to\\file.txt';
        $result = $this->sanitizationService->sanitizePath($input);
        $this->assertStringNotContainsString('\\', $result);
    }

    public function test_sanitize_path_removes_leading_slashes(): void
    {
        $input = '/path/to/file.txt';
        $result = $this->sanitizationService->sanitizePath($input);
        $this->assertFalse(str_starts_with($result, '/'));
    }

    // Array Sanitization Tests
    public function test_sanitize_array_sanitizes_string_values(): void
    {
        $input = [
            'name' => '<script>alert("XSS")</script>John',
            'email' => 'john@example.com',
        ];
        $result = $this->sanitizationService->sanitizeArray($input);
        $this->assertStringNotContainsString('<script>', $result['name']);
        $this->assertStringContainsString('John', $result['name']);
    }

    public function test_sanitize_array_handles_nested_arrays(): void
    {
        $input = [
            'user' => [
                'name' => '<b>John</b>',
                'profile' => [
                    'bio' => '<script>alert("XSS")</script>',
                ],
            ],
        ];
        $result = $this->sanitizationService->sanitizeArray($input);
        $this->assertStringNotContainsString('<script>', $result['user']['profile']['bio']);
    }

    public function test_sanitize_array_preserves_non_string_values(): void
    {
        $input = [
            'age' => 25,
            'active' => true,
            'score' => 95.5,
        ];
        $result = $this->sanitizationService->sanitizeArray($input);
        $this->assertEquals(25, $result['age']);
        $this->assertTrue($result['active']);
        $this->assertEquals(95.5, $result['score']);
    }

    // Email Sanitization Tests
    public function test_sanitize_email_removes_whitespace(): void
    {
        $input = '  john@example.com  ';
        $result = $this->sanitizationService->sanitizeEmail($input);
        $this->assertEquals('john@example.com', $result);
    }

    public function test_sanitize_email_converts_to_lowercase(): void
    {
        $input = 'JOHN@EXAMPLE.COM';
        $result = $this->sanitizationService->sanitizeEmail($input);
        $this->assertEquals('john@example.com', $result);
    }

    public function test_sanitize_email_handles_null(): void
    {
        $result = $this->sanitizationService->sanitizeEmail(null);
        $this->assertNull($result);
    }

    // Phone Number Sanitization Tests
    public function test_sanitize_phone_number_removes_non_digits(): void
    {
        $input = '+91-9876-543-210';
        $result = $this->sanitizationService->sanitizePhoneNumber($input);
        $this->assertEquals('919876543210', $result);
    }

    public function test_sanitize_phone_number_handles_null(): void
    {
        $result = $this->sanitizationService->sanitizePhoneNumber(null);
        $this->assertNull($result);
    }

    // URL Sanitization Tests
    public function test_sanitize_url_removes_null_bytes(): void
    {
        $input = "https://example.com\0/path";
        $result = $this->sanitizationService->sanitizeUrl($input);
        $this->assertStringNotContainsString("\0", $result);
    }

    public function test_sanitize_url_handles_null(): void
    {
        $result = $this->sanitizationService->sanitizeUrl(null);
        $this->assertNull($result);
    }

    // Numeric Sanitization Tests
    public function test_sanitize_numeric_converts_to_float(): void
    {
        $result = $this->sanitizationService->sanitizeNumeric('123.45');
        $this->assertEquals(123.45, $result);
    }

    public function test_sanitize_numeric_handles_integer(): void
    {
        $result = $this->sanitizationService->sanitizeNumeric('100');
        $this->assertEquals(100.0, $result);
    }

    public function test_sanitize_numeric_handles_null(): void
    {
        $result = $this->sanitizationService->sanitizeNumeric(null);
        $this->assertNull($result);
    }

    // Coordinate Sanitization Tests
    public function test_sanitize_coordinate_returns_float(): void
    {
        $result = $this->sanitizationService->sanitizeCoordinate('28.7041');
        $this->assertIsFloat($result);
        $this->assertEquals(28.7041, $result);
    }

    public function test_sanitize_coordinate_handles_negative(): void
    {
        $result = $this->sanitizationService->sanitizeCoordinate('-77.0369');
        $this->assertEquals(-77.0369, $result);
    }

    // User Profile Sanitization Tests
    public function test_sanitize_user_profile_sanitizes_bio(): void
    {
        $data = [
            'display_name' => 'John Doe',
            'bio' => '<script>alert("XSS")</script>I love coding',
        ];
        $result = $this->sanitizationService->sanitizeUserProfile($data);
        $this->assertStringNotContainsString('<script>', $result['bio']);
        $this->assertStringContainsString('I love coding', $result['bio']);
    }

    public function test_sanitize_user_profile_sanitizes_email(): void
    {
        $data = [
            'email' => '  JOHN@EXAMPLE.COM  ',
        ];
        $result = $this->sanitizationService->sanitizeUserProfile($data);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function test_sanitize_user_profile_sanitizes_phone(): void
    {
        $data = [
            'emergency_contact' => '+91-9876-543-210',
        ];
        $result = $this->sanitizationService->sanitizeUserProfile($data);
        $this->assertEquals('919876543210', $result['emergency_contact']);
    }

    public function test_sanitize_user_profile_preserves_numeric_fields(): void
    {
        $data = [
            'seats_booked' => 3,
        ];
        $result = $this->sanitizationService->sanitizeUserProfile($data);
        $this->assertEquals(3, $result['seats_booked']);
    }

    // Review Sanitization Tests
    public function test_sanitize_review_sanitizes_comment(): void
    {
        $data = [
            'rating' => 5,
            'comment' => '<img src=x onerror="alert(\'XSS\')">Great ride!',
        ];
        $result = $this->sanitizationService->sanitizeReview($data);
        $this->assertStringNotContainsString('<img', $result['comment']);
        $this->assertStringContainsString('Great ride!', $result['comment']);
    }

    public function test_sanitize_review_sanitizes_categories(): void
    {
        $data = [
            'rating' => 5,
            'categories' => [
                'cleanliness' => 5,
                'behavior' => '<script>alert("XSS")</script>4',
            ],
        ];
        $result = $this->sanitizationService->sanitizeReview($data);
        $this->assertStringNotContainsString('<script>', json_encode($result['categories']));
    }

    // Message Sanitization Tests
    public function test_sanitize_message_sanitizes_text(): void
    {
        $data = [
            'message' => '<script>alert("XSS")</script>Hello',
            'message_type' => 'text',
        ];
        $result = $this->sanitizationService->sanitizeMessage($data);
        $this->assertStringNotContainsString('<script>', $result['message']);
        $this->assertStringContainsString('Hello', $result['message']);
    }

    public function test_sanitize_message_sanitizes_filename(): void
    {
        $data = [
            'attachment' => '../../../etc/passwd',
            'message_type' => 'image',
        ];
        $result = $this->sanitizationService->sanitizeMessage($data);
        $this->assertStringNotContainsString('..', $result['attachment']);
    }

    // Saved Route Sanitization Tests
    public function test_sanitize_saved_route_sanitizes_locations(): void
    {
        $data = [
            'from_location' => '<script>alert("XSS")</script>Home',
            'to_location' => 'Office',
        ];
        $result = $this->sanitizationService->sanitizeSavedRoute($data);
        $this->assertStringNotContainsString('<script>', $result['from_location']);
        $this->assertStringContainsString('Home', $result['from_location']);
    }
}
