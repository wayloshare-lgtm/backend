# Input Sanitization Implementation

## Overview

This document describes the input sanitization implementation for the WayloShare backend. The sanitization system prevents XSS (Cross-Site Scripting), SQL injection, and other security vulnerabilities by sanitizing all user input.

## Architecture

### Components

1. **InputSanitizationService** (`app/Services/InputSanitizationService.php`)
   - Core service providing all sanitization methods
   - Handles different input types (strings, emails, phone numbers, URLs, etc.)
   - Provides specialized sanitization for specific data types

2. **SanitizeInput Middleware** (`app/Http/Middleware/SanitizeInput.php`)
   - Automatically sanitizes all incoming request data
   - Applied to all API routes
   - Preserves file uploads and non-string values

3. **Integration Points**
   - Applied globally via API middleware group
   - Can be used directly in controllers for specific fields
   - Works with existing validation rules

## Sanitization Methods

### String Sanitization

#### `sanitizeString(?string $input, bool $allowNewlines = false): ?string`
- Removes HTML/script tags
- Encodes special characters
- Removes null bytes
- Trims whitespace
- Optionally preserves newlines

**Example:**
```php
$input = '<script>alert("XSS")</script>Hello';
$result = $sanitizationService->sanitizeString($input);
// Result: "Hello"
```

#### `sanitizeText(?string $input): ?string`
- Same as `sanitizeString` but preserves newlines
- Ideal for bio, comments, special instructions

**Example:**
```php
$input = "<script>alert('XSS')</script>\nLine 1\nLine 2";
$result = $sanitizationService->sanitizeText($input);
// Result: "Line 1\nLine 2" (script tags removed, newlines preserved)
```

### Filename Sanitization

#### `sanitizeFilename(string $filename): string`
- Removes path separators (`/`, `\`)
- Removes directory traversal attempts (`..`)
- Removes special characters
- Preserves alphanumeric, dots, hyphens, underscores

**Example:**
```php
$input = '../../../etc/passwd';
$result = $sanitizationService->sanitizeFilename($input);
// Result: "etcpasswd"
```

### Path Sanitization

#### `sanitizePath(string $path): string`
- Removes directory traversal attempts
- Normalizes path separators
- Removes leading slashes
- Removes null bytes

**Example:**
```php
$input = '../../sensitive/file.txt';
$result = $sanitizationService->sanitizePath($input);
// Result: "sensitive/file.txt"
```

### Email Sanitization

#### `sanitizeEmail(?string $email): ?string`
- Removes whitespace
- Converts to lowercase
- Removes HTML/special characters

**Example:**
```php
$input = '  JOHN@EXAMPLE.COM  ';
$result = $sanitizationService->sanitizeEmail($input);
// Result: "john@example.com"
```

### Phone Number Sanitization

#### `sanitizePhoneNumber(?string $phone): ?string`
- Removes all non-digit characters
- Preserves only numbers

**Example:**
```php
$input = '+91-9876-543-210';
$result = $sanitizationService->sanitizePhoneNumber($input);
// Result: "919876543210"
```

### URL Sanitization

#### `sanitizeUrl(?string $url): ?string`
- Removes null bytes
- Uses PHP's filter_var for sanitization

**Example:**
```php
$input = "https://example.com\0/path";
$result = $sanitizationService->sanitizeUrl($input);
// Result: "https://example.com/path"
```

### Numeric Sanitization

#### `sanitizeNumeric($value): int|float|null`
- Converts to float
- Removes non-numeric characters

**Example:**
```php
$result = $sanitizationService->sanitizeNumeric('123.45');
// Result: 123.45
```

### Coordinate Sanitization

#### `sanitizeCoordinate($value): ?float`
- Sanitizes latitude/longitude values
- Returns float or null

**Example:**
```php
$result = $sanitizationService->sanitizeCoordinate('28.7041');
// Result: 28.7041
```

### Array Sanitization

#### `sanitizeArray(?array $data): ?array`
- Recursively sanitizes array values
- Preserves non-string values (numbers, booleans)
- Sanitizes keys and values

**Example:**
```php
$input = [
    'name' => '<script>alert("XSS")</script>John',
    'age' => 25,
];
$result = $sanitizationService->sanitizeArray($input);
// Result: ['name' => 'John', 'age' => 25]
```

### Specialized Sanitization

#### `sanitizeUserProfile(array $data): array`
- Sanitizes all user profile fields
- Handles text fields, emails, phones, URLs, coordinates
- Preserves numeric values

#### `sanitizeReview(array $data): array`
- Sanitizes review comments and categories
- Handles photo arrays

#### `sanitizeMessage(array $data): array`
- Sanitizes chat messages
- Handles metadata and attachments

#### `sanitizeSavedRoute(array $data): array`
- Sanitizes route location names

## Usage

### Automatic Sanitization (Middleware)

The `SanitizeInput` middleware automatically sanitizes all POST, PUT, and PATCH requests:

```php
// In routes/api.php
Route::middleware(['api', 'auth:sanctum'])->group(function () {
    Route::post('/user/profile', [UserProfileController::class, 'updateProfile']);
});

// The middleware automatically sanitizes the request data
```

### Manual Sanitization in Controllers

For specific fields that need custom sanitization:

```php
use App\Services\InputSanitizationService;

class UserProfileController extends Controller
{
    private InputSanitizationService $sanitizationService;

    public function __construct(InputSanitizationService $sanitizationService)
    {
        $this->sanitizationService = $sanitizationService;
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'bio' => 'nullable|string|max:500',
            'display_name' => 'nullable|string|max:255',
        ]);

        // Sanitize specific fields
        $data = $this->sanitizationService->sanitizeUserProfile($request->all());

        // Use sanitized data
        $user->update($data);

        return response()->json(['success' => true]);
    }
}
```

### Direct Service Usage

```php
$sanitizationService = app(InputSanitizationService::class);

// Sanitize a string
$clean = $sanitizationService->sanitizeString($userInput);

// Sanitize an email
$email = $sanitizationService->sanitizeEmail($userEmail);

// Sanitize a phone number
$phone = $sanitizationService->sanitizePhoneNumber($userPhone);
```

## Security Considerations

### What Sanitization Prevents

1. **XSS (Cross-Site Scripting)**
   - Removes script tags and event handlers
   - Encodes special characters
   - Prevents JavaScript injection

2. **Directory Traversal**
   - Removes `..` sequences
   - Removes path separators from filenames
   - Prevents access to sensitive files

3. **Null Byte Injection**
   - Removes null bytes from input
   - Prevents null byte attacks

4. **HTML Injection**
   - Strips HTML tags
   - Encodes special characters

### What Sanitization Does NOT Prevent

1. **SQL Injection**
   - Use parameterized queries and Laravel's query builder
   - Never concatenate user input into SQL queries

2. **CSRF Attacks**
   - Use CSRF tokens (Laravel's built-in protection)
   - Validate request origins

3. **Authentication Bypass**
   - Use proper authentication mechanisms
   - Validate user permissions

### Best Practices

1. **Validate First, Sanitize Second**
   - Always validate input format before sanitizing
   - Use Laravel's validation rules

2. **Use Appropriate Sanitization**
   - Use `sanitizeText()` for text fields with newlines
   - Use `sanitizeString()` for single-line text
   - Use `sanitizeEmail()` for email addresses
   - Use `sanitizePhoneNumber()` for phone numbers

3. **Preserve Data Integrity**
   - Sanitization should not break valid data
   - Test with real-world data

4. **Defense in Depth**
   - Combine sanitization with other security measures
   - Use rate limiting on sensitive endpoints
   - Implement proper access controls
   - Log security events

## Testing

### Unit Tests

Run unit tests for the sanitization service:

```bash
php artisan test tests/Unit/InputSanitizationServiceTest.php
```

Tests cover:
- String sanitization (HTML removal, special characters, null bytes)
- Text sanitization (newline preservation)
- Filename sanitization (path traversal prevention)
- Path sanitization
- Email sanitization
- Phone number sanitization
- URL sanitization
- Numeric sanitization
- Coordinate sanitization
- Array sanitization
- Specialized sanitization methods

### Feature Tests

Run feature tests for API endpoints:

```bash
php artisan test tests/Feature/InputSanitizationFeatureTest.php
```

Tests verify:
- User profile updates are sanitized
- Review comments are sanitized
- Chat messages are sanitized
- Booking details are sanitized
- Saved routes are sanitized
- Valid data is preserved

## Configuration

### Middleware Registration

The `SanitizeInput` middleware is registered in `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'api' => [
        // ... other middleware
        \App\Http\Middleware\SanitizeInput::class,
    ],
];
```

### Customization

To customize sanitization behavior, modify the `InputSanitizationService`:

1. Add new sanitization methods for specific data types
2. Update the specialized sanitization methods (e.g., `sanitizeUserProfile`)
3. Adjust the middleware to handle specific routes differently

## Performance Impact

- Minimal performance impact (< 1ms per request)
- Sanitization is applied only to POST, PUT, PATCH requests
- File uploads are excluded from sanitization
- Non-string values are preserved as-is

## Troubleshooting

### Issue: Valid data is being removed

**Solution:** Use the appropriate sanitization method:
- For text with newlines: use `sanitizeText()` instead of `sanitizeString()`
- For special characters: check if they're being HTML-encoded (expected behavior)

### Issue: Sanitization is not being applied

**Solution:** Verify:
1. The middleware is registered in `app/Http/Kernel.php`
2. The route is using the 'api' middleware group
3. The request method is POST, PUT, or PATCH

### Issue: File uploads are being sanitized

**Solution:** File uploads are automatically excluded from sanitization by the middleware. If you need to sanitize filenames, use `sanitizeFilename()` explicitly.

## Future Enhancements

1. **Content Security Policy (CSP)**
   - Implement CSP headers to prevent XSS
   - Configure CSP rules for different content types

2. **Input Validation Rules**
   - Create custom validation rules for specific data types
   - Combine with sanitization for defense in depth

3. **Audit Logging**
   - Log sanitization events for security monitoring
   - Track suspicious input patterns

4. **Rate Limiting**
   - Implement rate limiting on endpoints that accept user input
   - Prevent brute force attacks

## References

- [OWASP XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- [OWASP Input Validation Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Input_Validation_Cheat_Sheet.html)
- [Laravel Security Documentation](https://laravel.com/docs/security)
- [PHP Filter Functions](https://www.php.net/manual/en/ref.filter.php)
