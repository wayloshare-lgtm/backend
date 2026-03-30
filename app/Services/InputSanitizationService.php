<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Input Sanitization Service
 * 
 * Provides comprehensive input sanitization to prevent XSS, SQL injection,
 * and other security vulnerabilities. Uses Laravel's built-in helpers
 * and custom sanitization logic.
 */
class InputSanitizationService
{
    /**
     * Sanitize a string input to prevent XSS attacks
     * Removes HTML/script tags and encodes special characters
     * 
     * @param string|null $input The input to sanitize
     * @param bool $allowNewlines Whether to preserve newlines
     * @return string|null The sanitized input
     */
    public function sanitizeString(?string $input, bool $allowNewlines = false): ?string
    {
        if ($input === null || $input === '') {
            return $input;
        }

        // Remove null bytes
        $input = str_replace("\0", '', $input);

        // Strip HTML and PHP tags
        $input = strip_tags($input);

        // If newlines should be preserved, convert them to placeholders temporarily
        if ($allowNewlines) {
            $input = str_replace(["\r\n", "\r", "\n"], '|||NEWLINE|||', $input);
        }

        // Encode special characters to prevent XSS
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

        // Restore newlines if they were preserved
        if ($allowNewlines) {
            $input = str_replace('|||NEWLINE|||', "\n", $input);
        }

        return trim($input);
    }

    /**
     * Sanitize text input that may contain newlines (like bio, comments, etc.)
     * 
     * @param string|null $input The input to sanitize
     * @return string|null The sanitized input
     */
    public function sanitizeText(?string $input): ?string
    {
        return $this->sanitizeString($input, allowNewlines: true);
    }

    /**
     * Sanitize a filename to prevent directory traversal and other attacks
     * 
     * @param string $filename The filename to sanitize
     * @return string The sanitized filename
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove path separators and null bytes
        $filename = str_replace(['/', '\\', "\0", '..'], '', $filename);

        // Remove special characters that could be problematic
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);

        // Ensure filename is not empty
        if (empty($filename)) {
            $filename = 'file_' . time();
        }

        return $filename;
    }

    /**
     * Sanitize a file path to prevent directory traversal attacks
     * 
     * @param string $path The path to sanitize
     * @return string The sanitized path
     */
    public function sanitizePath(string $path): string
    {
        // Remove null bytes
        $path = str_replace("\0", '', $path);

        // Remove directory traversal attempts
        $path = str_replace(['../', '..\\', '..'], '', $path);

        // Normalize path separators
        $path = str_replace('\\', '/', $path);

        // Remove leading slashes
        $path = ltrim($path, '/');

        return $path;
    }

    /**
     * Sanitize JSON data recursively
     * 
     * @param array|null $data The data to sanitize
     * @return array|null The sanitized data
     */
    public function sanitizeArray(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $sanitized = [];

        foreach ($data as $key => $value) {
            // Sanitize the key
            $sanitizedKey = $this->sanitizeString((string)$key);

            // Sanitize the value based on its type
            if (is_array($value)) {
                $sanitized[$sanitizedKey] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $sanitized[$sanitizedKey] = $this->sanitizeString($value);
            } else {
                // Keep non-string values as-is (numbers, booleans, etc.)
                $sanitized[$sanitizedKey] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize email address
     * 
     * @param string|null $email The email to sanitize
     * @return string|null The sanitized email
     */
    public function sanitizeEmail(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return $email;
        }

        // Remove whitespace
        $email = trim($email);

        // Convert to lowercase
        $email = strtolower($email);

        // Remove any HTML/special characters
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        return $email;
    }

    /**
     * Sanitize phone number
     * Removes all non-digit characters
     * 
     * @param string|null $phone The phone number to sanitize
     * @return string|null The sanitized phone number
     */
    public function sanitizePhoneNumber(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return $phone;
        }

        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        return $phone;
    }

    /**
     * Sanitize URL
     * 
     * @param string|null $url The URL to sanitize
     * @return string|null The sanitized URL
     */
    public function sanitizeUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }

        // Remove null bytes
        $url = str_replace("\0", '', $url);

        // Use filter_var to validate and sanitize
        $sanitized = filter_var($url, FILTER_SANITIZE_URL);

        return $sanitized;
    }

    /**
     * Sanitize numeric input
     * 
     * @param mixed $value The value to sanitize
     * @return int|float|null The sanitized numeric value
     */
    public function sanitizeNumeric($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Try to convert to float first
        $numeric = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        if ($numeric === false) {
            return null;
        }

        return (float)$numeric;
    }

    /**
     * Sanitize coordinate (latitude/longitude)
     * 
     * @param mixed $value The coordinate value to sanitize
     * @return float|null The sanitized coordinate
     */
    public function sanitizeCoordinate($value): ?float
    {
        $numeric = $this->sanitizeNumeric($value);

        if ($numeric === null) {
            return null;
        }

        return (float)$numeric;
    }

    /**
     * Sanitize a complete user profile data array
     * 
     * @param array $data The profile data to sanitize
     * @return array The sanitized profile data
     */
    public function sanitizeUserProfile(array $data): array
    {
        $sanitized = [];

        // Text fields that should preserve newlines
        $textFields = ['bio', 'special_instructions', 'luggage_info', 'accessibility_requirements'];

        // String fields
        $stringFields = ['display_name', 'passenger_name', 'insurance_provider', 'insurance_policy_number'];

        // Email fields
        $emailFields = ['email'];

        // Phone fields
        $phoneFields = ['passenger_phone', 'emergency_contact'];

        // URL fields
        $urlFields = ['profile_photo_url'];

        // Numeric fields
        $numericFields = ['seats_booked'];

        // Coordinate fields
        $coordinateFields = ['latitude', 'longitude'];

        foreach ($data as $key => $value) {
            if ($value === null) {
                $sanitized[$key] = null;
                continue;
            }

            if (in_array($key, $textFields)) {
                $sanitized[$key] = $this->sanitizeText($value);
            } elseif (in_array($key, $stringFields)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } elseif (in_array($key, $emailFields)) {
                $sanitized[$key] = $this->sanitizeEmail($value);
            } elseif (in_array($key, $phoneFields)) {
                $sanitized[$key] = $this->sanitizePhoneNumber($value);
            } elseif (in_array($key, $urlFields)) {
                $sanitized[$key] = $this->sanitizeUrl($value);
            } elseif (in_array($key, $numericFields)) {
                $sanitized[$key] = $this->sanitizeNumeric($value);
            } elseif (in_array($key, $coordinateFields)) {
                $sanitized[$key] = $this->sanitizeCoordinate($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize review data
     * 
     * @param array $data The review data to sanitize
     * @return array The sanitized review data
     */
    public function sanitizeReview(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                $sanitized[$key] = null;
                continue;
            }

            if ($key === 'comment') {
                $sanitized[$key] = $this->sanitizeText($value);
            } elseif ($key === 'categories' && is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } elseif ($key === 'photos' && is_array($value)) {
                $sanitized[$key] = array_map(fn($photo) => $this->sanitizeString($photo), $value);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize chat message data
     * 
     * @param array $data The message data to sanitize
     * @return array The sanitized message data
     */
    public function sanitizeMessage(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                $sanitized[$key] = null;
                continue;
            }

            if ($key === 'message') {
                $sanitized[$key] = $this->sanitizeText($value);
            } elseif ($key === 'metadata' && is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } elseif ($key === 'attachment') {
                $sanitized[$key] = $this->sanitizeFilename($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize saved route data
     * 
     * @param array $data The route data to sanitize
     * @return array The sanitized route data
     */
    public function sanitizeSavedRoute(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                $sanitized[$key] = null;
                continue;
            }

            if (in_array($key, ['from_location', 'to_location'])) {
                $sanitized[$key] = $this->sanitizeString($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
