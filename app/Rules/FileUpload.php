<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class FileUpload implements ValidationRule
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
    ];
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];

    private ?int $maxSize = null;
    private array $allowedMimeTypes = self::ALLOWED_MIME_TYPES;
    private array $allowedExtensions = self::ALLOWED_EXTENSIONS;

    /**
     * Set custom max file size (in bytes)
     */
    public function maxSize(int $bytes): self
    {
        $this->maxSize = $bytes;
        return $this;
    }

    /**
     * Set allowed MIME types
     */
    public function mimeTypes(array $types): self
    {
        $this->allowedMimeTypes = $types;
        return $this;
    }

    /**
     * Set allowed file extensions
     */
    public function extensions(array $extensions): self
    {
        $this->allowedExtensions = array_map('strtolower', $extensions);
        return $this;
    }

    /**
     * Run the validation rule
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            $fail('The ' . $attribute . ' must be a file.');
            return;
        }

        $maxSize = $this->maxSize ?? self::MAX_FILE_SIZE;

        // Validate file size
        if ($value->getSize() > $maxSize) {
            $fail('The ' . $attribute . ' must not exceed ' . $this->formatBytes($maxSize) . '.');
            return;
        }

        // Validate MIME type
        if (!in_array($value->getMimeType(), $this->allowedMimeTypes)) {
            $fail('The ' . $attribute . ' must be one of: ' . implode(', ', $this->allowedMimeTypes) . '.');
            return;
        }

        // Validate extension
        $extension = strtolower($value->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedExtensions)) {
            $fail('The ' . $attribute . ' must have one of these extensions: ' . implode(', ', $this->allowedExtensions) . '.');
            return;
        }
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
