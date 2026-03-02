<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
    ];
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];

    /**
     * Upload file with validation
     */
    public function upload(UploadedFile $file, string $directory = 'uploads'): string
    {
        // Validate file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \Exception('File size exceeds 10MB limit');
        }

        // Validate mime type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new \Exception('Invalid file type. Allowed: JPG, PNG, PDF');
        }

        // Validate extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new \Exception('Invalid file extension');
        }

        // Generate UUID filename
        $filename = Str::uuid() . '.' . $extension;

        // Store in private disk
        $path = $file->storeAs($directory, $filename, 'private');

        if (!$path) {
            throw new \Exception('Failed to upload file');
        }

        return $path;
    }

    /**
     * Delete file
     */
    public function delete(string $path): bool
    {
        return Storage::disk('private')->delete($path);
    }

    /**
     * Get file URL (for private disk)
     */
    public function getUrl(string $path): string
    {
        return Storage::disk('private')->url($path);
    }

    /**
     * Validate file before upload
     */
    public function validate(UploadedFile $file): array
    {
        $errors = [];

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            $errors[] = 'File size exceeds 10MB limit';
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            $errors[] = 'Invalid file type. Allowed: JPG, PNG, PDF';
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            $errors[] = 'Invalid file extension';
        }

        return $errors;
    }
}
