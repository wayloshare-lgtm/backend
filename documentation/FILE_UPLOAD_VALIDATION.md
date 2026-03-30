# File Upload Validation Documentation

## Overview

This document describes the file upload validation system implemented for the WayloShare backend. The validation ensures that all file uploads meet security and quality requirements.

## Validation Rules

### File Size
- **Maximum Size**: 10 MB (10,485,760 bytes)
- **Minimum Size**: 1 byte
- **Error Message**: "The {field} must not exceed 10 MB."

### Allowed MIME Types
- `image/jpeg` - JPEG images
- `image/png` - PNG images
- `application/pdf` - PDF documents

### Allowed File Extensions
- `.jpg` - JPEG images
- `.jpeg` - JPEG images
- `.png` - PNG images
- `.pdf` - PDF documents

## Implementation

### FileUpload Validation Rule

The `App\Rules\FileUpload` class provides a reusable validation rule for file uploads.

#### Basic Usage

```php
use App\Rules\FileUpload;

$request->validate([
    'photo' => ['required', 'file', new FileUpload()],
]);
```

#### Custom Configuration

```php
use App\Rules\FileUpload;

$request->validate([
    'document' => [
        'required',
        'file',
        (new FileUpload())
            ->maxSize(5 * 1024 * 1024) // 5 MB
            ->mimeTypes(['application/pdf'])
            ->extensions(['pdf']),
    ],
]);
```

### FileUploadService

The `App\Services\FileUploadService` handles the actual file upload operations.

#### Upload a File

```php
use App\Services\FileUploadService;

$service = app(FileUploadService::class);
$filePath = $service->upload($request->file('photo'), 'profile-photos');
$fileUrl = $service->getUrl($filePath);
```

#### Validate a File

```php
$errors = $service->validate($request->file('photo'));
if (!empty($errors)) {
    // Handle validation errors
}
```

#### Delete a File

```php
$service->delete($filePath);
```

## Controllers Using File Upload Validation

### UserProfileController

**Endpoint**: `POST /api/v1/user/profile/photo`

**Validation**:
- Field: `photo`
- Type: File
- Rule: `FileUpload` (default settings)

**Example Request**:
```bash
curl -X POST http://localhost:8000/api/v1/user/profile/photo \
  -H "Authorization: Bearer {token}" \
  -F "photo=@profile.jpg"
```

### DriverVerificationController

**Endpoints**:
- `POST /api/v1/driver/verification/documents` (DL Front)
- `POST /api/v1/driver/verification/documents` (DL Back)
- `POST /api/v1/driver/verification/documents` (RC Front)
- `POST /api/v1/driver/verification/documents` (RC Back)

**Validation**:
- Fields: `dl_front_image`, `dl_back_image`, `rc_front_image`, `rc_back_image`
- Type: File
- Rule: `FileUpload` (default settings)

**Example Request**:
```bash
curl -X POST http://localhost:8000/api/v1/driver/verification/documents \
  -H "Authorization: Bearer {token}" \
  -F "dl_front_image=@dl_front.pdf"
```

### VehicleController

**Endpoints**:
- `POST /api/v1/vehicles` (Create with photo)
- `POST /api/v1/vehicles/{id}/photo` (Upload photo)

**Validation**:
- Field: `vehicle_photo`
- Type: File
- Rule: `FileUpload` (default settings)

**Example Request**:
```bash
curl -X POST http://localhost:8000/api/v1/vehicles \
  -H "Authorization: Bearer {token}" \
  -F "vehicle_name=My Car" \
  -F "vehicle_type=sedan" \
  -F "license_plate=ABC123" \
  -F "vehicle_photo=@car.jpg"
```

## Error Responses

### File Size Exceeds Limit

**Status Code**: 422

**Response**:
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "photo": [
      "The photo must not exceed 10 MB."
    ]
  }
}
```

### Invalid MIME Type

**Status Code**: 422

**Response**:
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "photo": [
      "The photo must be one of: image/jpeg, image/png, application/pdf."
    ]
  }
}
```

### Invalid File Extension

**Status Code**: 422

**Response**:
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "photo": [
      "The photo must have one of these extensions: jpg, jpeg, png, pdf."
    ]
  }
}
```

### Missing File

**Status Code**: 422

**Response**:
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "photo": [
      "The photo field is required."
    ]
  }
}
```

## File Storage

### Storage Location

Files are stored in the private disk, outside the public directory for security.

**Directory Structure**:
```
storage/app/private/
├── profile-photos/
│   └── {uuid}.{ext}
├── driver-verifications/
│   ├── dl-front/
│   │   └── {uuid}.{ext}
│   ├── dl-back/
│   │   └── {uuid}.{ext}
│   ├── rc-front/
│   │   └── {uuid}.{ext}
│   └── rc-back/
│       └── {uuid}.{ext}
└── vehicle-photos/
    └── {uuid}.{ext}
```

### File Naming

Files are renamed to UUIDs to prevent directory traversal attacks and filename conflicts.

**Example**: `550e8400-e29b-41d4-a716-446655440000.jpg`

### File Access

Files are accessed through signed URLs that expire after a certain period.

```php
$fileUrl = $service->getUrl($filePath);
// Returns: /storage/app/private/profile-photos/550e8400-e29b-41d4-a716-446655440000.jpg?expires=...&signature=...
```

## Security Considerations

### MIME Type Validation

The validation checks both the file extension and MIME type to prevent malicious files from being uploaded.

### File Size Limits

The 10 MB limit prevents disk space exhaustion and improves performance.

### Private Storage

Files are stored in the private disk, which is not directly accessible from the web. Access is controlled through the application.

### UUID Filenames

Files are renamed to UUIDs to prevent:
- Directory traversal attacks
- Filename conflicts
- Information disclosure

### Signed URLs

File access is controlled through signed URLs that expire after a certain period.

## Testing

### Unit Tests

Unit tests are located in `tests/Unit/FileUploadValidationTest.php`.

**Run Tests**:
```bash
php artisan test tests/Unit/FileUploadValidationTest.php
```

### Feature Tests

Feature tests are located in `tests/Feature/FileUploadValidationFeatureTest.php`.

**Run Tests**:
```bash
php artisan test tests/Feature/FileUploadValidationFeatureTest.php
```

### Test Coverage

- Valid file uploads
- File size validation
- MIME type validation
- File extension validation
- Custom validation rules
- Multiple file uploads
- Error handling
- Authentication checks

## Best Practices

### 1. Always Validate Files

Always use the `FileUpload` validation rule or the `FileUploadService::validate()` method before uploading files.

```php
$request->validate([
    'photo' => ['required', 'file', new FileUpload()],
]);
```

### 2. Handle Validation Errors

Always handle validation errors gracefully and provide meaningful error messages to the client.

```php
try {
    $request->validate([
        'photo' => ['required', 'file', new FileUpload()],
    ]);
} catch (ValidationException $e) {
    return response()->json([
        'success' => false,
        'error' => 'Validation failed',
        'errors' => $e->errors(),
    ], 422);
}
```

### 3. Delete Old Files

Always delete old files before uploading new ones to prevent disk space exhaustion.

```php
if ($user->profile_photo_url) {
    try {
        $this->fileUploadService->delete($user->profile_photo_url);
    } catch (\Exception $e) {
        // Log error but continue with upload
    }
}
```

### 4. Use Signed URLs

Always use signed URLs to access private files.

```php
$fileUrl = $this->fileUploadService->getUrl($filePath);
```

### 5. Log File Operations

Log all file upload and deletion operations for audit purposes.

```php
Log::info('File uploaded', [
    'user_id' => $user->id,
    'file_path' => $filePath,
    'file_size' => $file->getSize(),
]);
```

## Configuration

### Environment Variables

No additional environment variables are required for file upload validation.

### Filesystem Configuration

The private disk is configured in `config/filesystems.php`:

```php
'private' => [
    'driver' => 'local',
    'root' => storage_path('app/private'),
    'url' => env('APP_URL') . '/storage/app/private',
    'visibility' => 'private',
],
```

## Troubleshooting

### File Upload Fails with "File validation failed"

**Cause**: The file does not meet the validation requirements.

**Solution**: Check the file size, MIME type, and extension.

### File Upload Fails with "Failed to upload file"

**Cause**: The file could not be stored on disk.

**Solution**: Check disk space and file permissions.

### File Access Returns 404

**Cause**: The file does not exist or the signed URL has expired.

**Solution**: Re-upload the file or generate a new signed URL.

## Future Enhancements

1. **Malware Scanning**: Integrate with ClamAV or similar service to scan files for malware.
2. **Image Optimization**: Automatically optimize images to reduce file size.
3. **Virus Scanning**: Integrate with VirusTotal API for additional security.
4. **CDN Integration**: Store files on CDN for faster access.
5. **Compression**: Automatically compress files to reduce storage usage.

## References

- [Laravel File Uploads](https://laravel.com/docs/11.x/requests#files)
- [Laravel Validation](https://laravel.com/docs/11.x/validation)
- [Laravel Storage](https://laravel.com/docs/11.x/filesystem)
- [OWASP File Upload](https://owasp.org/www-community/vulnerabilities/Unrestricted_File_Upload)
