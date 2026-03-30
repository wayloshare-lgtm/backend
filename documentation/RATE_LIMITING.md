# Rate Limiting Documentation

## Overview

Rate limiting is implemented on sensitive endpoints to protect against abuse and ensure fair usage of the API. The implementation uses Laravel's built-in throttle middleware with configurable limits per endpoint category.

## Rate Limiting Configuration

Rate limiters are configured in `app/Providers/AppServiceProvider.php`:

### Configured Limiters

1. **auth** - 5 requests per minute
   - Used for authentication endpoints
   - Limits: `/api/v1/auth/login`, `/api/v1/auth/delete-account`

2. **sensitive** - 10 requests per minute
   - Used for sensitive write operations
   - Limits: Profile updates, payment methods, bookings, driver verification

3. **api** - 60 requests per minute
   - Default limit for general API endpoints
   - Uses user ID for authenticated requests, IP for unauthenticated

## Protected Endpoints

### Authentication Endpoints
- `POST /api/v1/auth/login` - **5 requests/minute** (auth limiter)
- `POST /api/v1/auth/logout` - **10 requests/minute** (sensitive limiter)
- `DELETE /api/v1/auth/delete-account` - **5 requests/minute** (auth limiter)

### User Profile Endpoints
- `POST /api/v1/user/profile` - **10 requests/minute** (sensitive limiter)
- `POST /api/v1/user/profile/photo` - **10 requests/minute** (sensitive limiter)
- `POST /api/v1/user/profile/complete` - **10 requests/minute** (sensitive limiter)
- `POST /api/v1/user/complete-onboarding` - **10 requests/minute** (sensitive limiter)

### Payment Method Endpoints
- `POST /api/v1/payment-methods` - **10 requests/minute** (sensitive limiter)
- `PUT /api/v1/payment-methods/{id}` - **10 requests/minute** (sensitive limiter)
- `DELETE /api/v1/payment-methods/{id}` - **10 requests/minute** (sensitive limiter)
- `POST /api/v1/payment-methods/{id}/set-default` - **10 requests/minute** (sensitive limiter)

### Driver Verification Endpoints
- `POST /api/v1/driver/verification` - **10 requests/minute** (sensitive limiter)
- `POST /api/v1/driver/verification/dl-front-image` - **10 requests/minute** (sensitive limiter)
- `POST /api/v1/driver/verification/rc-front-image` - **10 requests/minute** (sensitive limiter)

### Booking Endpoints
- `POST /api/v1/bookings` - **10 requests/minute** (sensitive limiter)
- `POST /api/v1/bookings/{id}/cancel` - **10 requests/minute** (sensitive limiter)

## Rate Limit Response

When a client exceeds the rate limit, they receive a **429 Too Many Requests** response:

```json
{
  "success": false,
  "error": "429",
  "message": "Too Many Requests",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

### Response Headers

The response includes the following headers:

- `Retry-After` - Seconds to wait before retrying
- `X-RateLimit-Limit` - Maximum requests allowed in the window
- `X-RateLimit-Remaining` - Requests remaining in the current window
- `X-RateLimit-Reset` - Unix timestamp when the limit resets

## Rate Limiting Strategy

### Per-User vs Per-IP

- **Authenticated Requests**: Rate limits are applied per user ID
- **Unauthenticated Requests**: Rate limits are applied per IP address

This ensures that:
- Multiple users from the same IP (e.g., corporate network) are not affected by each other
- A single user cannot bypass limits by changing IP addresses

### Time Windows

All rate limits use a **1-minute sliding window**. This means:
- Limits reset every 60 seconds
- The window slides continuously (not fixed hourly/daily)
- Requests are counted within the last 60 seconds

## Implementation Details

### Middleware Application

Rate limiting is applied using Laravel's `throttle` middleware:

```php
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');
```

### Cache Backend

Rate limiting uses the configured cache backend (typically Redis in production). The cache stores:
- Request count per user/IP
- Timestamp of the first request in the window
- Timestamp of the last request

### Performance Impact

- Minimal overhead: Single cache lookup per request
- Redis operations are typically <1ms
- No database queries required

## Testing

Rate limiting is tested in `tests/Feature/RateLimitingTest.php`:

```bash
php artisan test tests/Feature/RateLimitingTest.php
```

Tests verify:
- Correct limits per endpoint
- 429 response status code
- Proper response headers
- Per-IP and per-user limiting
- Read operations are not limited

## Configuration Changes

To modify rate limits, edit `app/Providers/AppServiceProvider.php`:

```php
RateLimiter::for('auth', function (Request $request) {
    // Change 5 to desired limit
    return Limit::perMinute(5)->by($request->ip());
});
```

## Monitoring

Monitor rate limiting in production:

1. **Log Rate Limit Hits**: Check application logs for 429 responses
2. **Cache Metrics**: Monitor Redis memory usage for rate limit keys
3. **Client Feedback**: Track client errors related to rate limiting

## Best Practices for Clients

1. **Implement Exponential Backoff**: When receiving 429, wait before retrying
2. **Check Retry-After Header**: Use the header value to determine wait time
3. **Cache Responses**: Reduce requests by caching successful responses
4. **Batch Operations**: Combine multiple operations into single requests where possible
5. **Monitor Headers**: Track `X-RateLimit-Remaining` to avoid hitting limits

## Future Enhancements

Potential improvements:

1. **Dynamic Limits**: Adjust limits based on user tier/subscription
2. **Endpoint-Specific Limits**: Different limits for different endpoints
3. **Burst Allowance**: Allow temporary bursts above the limit
4. **Whitelist**: Exempt certain IPs or users from rate limiting
5. **Analytics**: Track rate limit violations for security analysis

## Troubleshooting

### Getting 429 Errors Too Frequently

1. Check if requests are being retried automatically
2. Verify the rate limit is appropriate for your use case
3. Consider batching requests
4. Check for duplicate requests in your client code

### Rate Limits Not Working

1. Verify cache backend is running (Redis)
2. Check middleware is applied to routes
3. Verify `AppServiceProvider` is registered
4. Check application logs for errors

### Different Limits for Different Users

This is expected behavior:
- Each user has their own limit counter
- Limits are per-user for authenticated requests
- Limits are per-IP for unauthenticated requests
