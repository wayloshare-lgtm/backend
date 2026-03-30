<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Request Logging Service
 * 
 * Handles comprehensive logging of all API requests and responses.
 * Captures request details, response metrics, and error information
 * while filtering sensitive data.
 */
class RequestLoggingService
{
    /**
     * Sensitive fields that should never be logged
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'secret',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
        'bank_account',
        'firebase_token',
        'fcm_token',
    ];

    /**
     * Log an incoming API request
     *
     * @param Request $request
     * @return array The log context data
     */
    public function logRequest(Request $request): array
    {
        $context = [
            'request_id' => $this->generateRequestId(),
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'url' => $request->url(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ];

        // Add query parameters if present
        if ($request->query()) {
            $context['query_params'] = $this->filterSensitiveData($request->query());
        }

        // Add request headers (excluding sensitive ones)
        $context['headers'] = $this->filterHeaders($request->headers->all());

        // Add request body for POST/PUT/PATCH requests
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $body = $request->all();
            $context['body'] = $this->filterSensitiveData($body);
        }

        // Add authenticated user info if available
        if (Auth::check()) {
            $context['user_id'] = Auth::id();
            $context['user_email'] = Auth::user()->email ?? null;
        }

        // Store request ID in request for later retrieval
        $request->attributes->set('request_id', $context['request_id']);

        Log::channel('requests')->info('API Request', $context);

        return $context;
    }

    /**
     * Log the API response
     *
     * @param Request $request
     * @param Response $response
     * @param float $duration Response time in milliseconds
     * @return void
     */
    public function logResponse(Request $request, Response $response, float $duration): void
    {
        $requestId = $request->attributes->get('request_id', $this->generateRequestId());

        $context = [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'status_code' => $response->getStatusCode(),
            'response_time_ms' => round($duration, 2),
            'timestamp' => now()->toIso8601String(),
        ];

        // Add response size
        $context['response_size_bytes'] = strlen($response->getContent());

        // Add authenticated user info if available
        if (Auth::check()) {
            $context['user_id'] = Auth::id();
        }

        // Determine log level based on status code
        $level = $this->getLogLevel($response->getStatusCode());

        Log::channel('requests')->log($level, 'API Response', $context);
    }

    /**
     * Log an error that occurred during request processing
     *
     * @param Request $request
     * @param \Throwable $exception
     * @param float $duration Response time in milliseconds
     * @return void
     */
    public function logError(Request $request, \Throwable $exception, float $duration): void
    {
        $requestId = $request->attributes->get('request_id', $this->generateRequestId());

        $context = [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'error_type' => class_basename($exception),
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'response_time_ms' => round($duration, 2),
            'timestamp' => now()->toIso8601String(),
        ];

        // Add stack trace in debug mode
        if (config('app.debug')) {
            $context['stack_trace'] = $exception->getTraceAsString();
        }

        // Add authenticated user info if available
        if (Auth::check()) {
            $context['user_id'] = Auth::id();
        }

        Log::channel('requests')->error('API Error', $context);
    }

    /**
     * Filter sensitive data from arrays
     *
     * @param array $data
     * @return array
     */
    private function filterSensitiveData(array $data): array
    {
        $filtered = [];

        foreach ($data as $key => $value) {
            if ($this->isSensitiveField($key)) {
                $filtered[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $filtered[$key] = $this->filterSensitiveData($value);
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Filter sensitive headers
     *
     * @param array $headers
     * @return array
     */
    private function filterHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'x-api-key',
            'x-auth-token',
            'x-access-token',
        ];

        $filtered = [];

        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);

            if (in_array($lowerKey, $sensitiveHeaders)) {
                $filtered[$key] = is_array($value) ? ['***REDACTED***'] : '***REDACTED***';
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Check if a field name is sensitive
     *
     * @param string $fieldName
     * @return bool
     */
    private function isSensitiveField(string $fieldName): bool
    {
        $lowerField = strtolower($fieldName);

        foreach (self::SENSITIVE_FIELDS as $sensitive) {
            if (stripos($lowerField, $sensitive) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine log level based on HTTP status code
     *
     * @param int $statusCode
     * @return string
     */
    private function getLogLevel(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'error';
        } elseif ($statusCode >= 400) {
            return 'warning';
        } elseif ($statusCode >= 300) {
            return 'info';
        }

        return 'info';
    }

    /**
     * Generate a unique request ID
     *
     * @return string
     */
    private function generateRequestId(): string
    {
        return 'req_' . uniqid() . '_' . bin2hex(random_bytes(4));
    }
}
