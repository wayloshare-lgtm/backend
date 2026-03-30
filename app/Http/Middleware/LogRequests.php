<?php

namespace App\Http\Middleware;

use App\Services\RequestLoggingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Log Requests Middleware
 * 
 * Logs all incoming API requests and outgoing responses.
 * Captures request details, response metrics, and error information.
 * Automatically filters sensitive data to prevent logging of passwords, tokens, etc.
 */
class LogRequests
{
    private RequestLoggingService $loggingService;

    public function __construct(RequestLoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log the incoming request
        $this->loggingService->logRequest($request);

        // Record start time for response time calculation
        $startTime = microtime(true);

        try {
            // Process the request
            $response = $next($request);

            // Calculate response time
            $duration = (microtime(true) - $startTime) * 1000;

            // Log the response
            $this->loggingService->logResponse($request, $response, $duration);

            return $response;
        } catch (\Throwable $exception) {
            // Calculate response time
            $duration = (microtime(true) - $startTime) * 1000;

            // Log the error
            $this->loggingService->logError($request, $exception, $duration);

            // Re-throw the exception to be handled by Laravel's exception handler
            throw $exception;
        }
    }
}
