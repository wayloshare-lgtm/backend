<?php

namespace App\Http\Middleware;

use App\Services\InputSanitizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sanitize Input Middleware
 * 
 * Automatically sanitizes all incoming request data to prevent XSS and other attacks.
 * This middleware should be applied to all API routes.
 */
class SanitizeInput
{
    private InputSanitizationService $sanitizationService;

    public function __construct(InputSanitizationService $sanitizationService)
    {
        $this->sanitizationService = $sanitizationService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only sanitize POST, PUT, PATCH requests with JSON or form data
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $this->sanitizeRequestData($request);
        }

        return $next($request);
    }

    /**
     * Sanitize all request data
     */
    private function sanitizeRequestData(Request $request): void
    {
        $data = $request->all();
        $sanitized = $this->sanitizeData($data);
        $request->replace($sanitized);
    }

    /**
     * Recursively sanitize data
     */
    private function sanitizeData(array $data): array
    {
        $sanitized = [];
        
        // Fields that should be sanitized as emails
        $emailFields = ['email', 'emergency_contact'];
        // Fields that should be sanitized as phone numbers
        $phoneFields = ['phone', 'phone_number', 'emergency_contact'];

        foreach ($data as $key => $value) {
            // Skip file uploads - they are handled separately
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $sanitized[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value);
            } elseif (is_string($value)) {
                // Apply specific sanitization based on field name
                if (in_array($key, $emailFields) && $key === 'email') {
                    $sanitized[$key] = $this->sanitizationService->sanitizeEmail($value);
                } elseif (in_array($key, $phoneFields)) {
                    $sanitized[$key] = $this->sanitizationService->sanitizePhoneNumber($value);
                } else {
                    // Sanitize string values
                    $sanitized[$key] = $this->sanitizationService->sanitizeString($value, allowNewlines: true);
                }
            } else {
                // Keep other types as-is (numbers, booleans, null, etc.)
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
