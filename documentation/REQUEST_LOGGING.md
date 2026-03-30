# Request Logging Implementation

## Overview

The request logging system provides comprehensive logging of all API requests and responses. It captures request details, response metrics, and error information while automatically filtering sensitive data to prevent logging of passwords, tokens, and payment information.

## Features

### Request Logging
- **Method & Path**: Captures HTTP method and request path
- **Query Parameters**: Logs all query parameters
- **Request Headers**: Logs headers (excluding sensitive ones)
- **Request Body**: Logs POST/PUT/PATCH request bodies
- **User Information**: Logs authenticated user ID and email
- **Client Information**: Captures IP address and user agent
- **Request ID**: Generates unique request ID for tracking

### Response Logging
- **Status Code**: Logs HTTP response status code
- **Response Time**: Measures and logs response time in milliseconds
- **Response Size**: Logs response body size in bytes
- **Log Level**: Automatically determines log level based on status code
  - 5xx errors → error level
  - 4xx errors → warning level
  - 2xx/3xx → info level

### Error Logging
- **Exception Type**: Logs the exception class name
- **Error Message**: Logs the error message
- **Error Code**: Logs the error code
- **Stack Trace**: Logs full stack trace in debug mode
- **Response Time**: Logs how long the request took before failing

### Security Features
- **Sensitive Data Filtering**: Automatically redacts sensitive fields
- **Header Filtering**: Redacts sensitive headers like Authorization
- **Nested Data Filtering**: Recursively filters sensitive data in nested arrays
- **No Logging of Passwords**: Passwords, tokens, and payment details are never logged

## Sensitive Fields

The following fields are automatically redacted from logs:

- `password`
- `password_confirmation`
- `token`
- `access_token`
- `refresh_token`
- `api_key`
- `secret`
- `credit_card`
- `card_number`
- `cvv`
- `ssn`
- `bank_account`
- `firebase_token`
- `fcm_token`

## Sensitive Headers

The following headers are automatically redacted:

- `Authorization`
- `Cookie`
- `X-Api-Key`
- `X-Auth-Token`
- `X-Access-Token`

## Configuration

### Logging Channel

The request logging uses a dedicated `requests` channel configured in `config/logging.php`:

```php
'requests' => [
    'driver' => 'daily',
    'path' => storage_path('logs/requests.log'),
    'level' => env('LOG_REQUESTS_LEVEL', 'info'),
    'days' => env('LOG_REQUESTS_DAYS', 30),
    'replace_placeholders' => true,
],
```

### Environment Variables

Configure request logging via environment variables:

```env
# Log level for request logs (debug, info, notice, warning, error, critical, alert, emergency)
LOG_REQUESTS_LEVEL=info

# Number of days to keep request logs
LOG_REQUESTS_DAYS=30
```

## Log Format

### Request Log Example

```json
{
  "request_id": "req_65a1b2c3d_4e5f6g7h",
  "method": "POST",
  "path": "/api/v1/auth/login",
  "url": "http://localhost:8000/api/v1/auth/login",
  "ip": "192.168.1.1",
  "user_agent": "Flutter/1.0",
  "timestamp": "2024-01-15T10:30:45.123456Z",
  "query_params": {
    "version": "1"
  },
  "headers": {
    "Authorization": ["***REDACTED***"],
    "Accept": ["application/json"],
    "Content-Type": ["application/json"]
  },
  "body": {
    "email": "user@example.com",
    "password": "***REDACTED***"
  }
}
```

### Response Log Example

```json
{
  "request_id": "req_65a1b2c3d_4e5f6g7h",
  "method": "POST",
  "path": "/api/v1/auth/login",
  "status_code": 200,
  "response_time_ms": 45.5,
  "response_size_bytes": 256,
  "timestamp": "2024-01-15T10:30:45.123456Z",
  "user_id": 1
}
```

### Error Log Example

```json
{
  "request_id": "req_65a1b2c3d_4e5f6g7h",
  "method": "POST",
  "path": "/api/v1/users",
  "error_type": "ValidationException",
  "error_message": "The email field is required",
  "error_code": 422,
  "response_time_ms": 12.3,
  "timestamp": "2024-01-15T10:30:45.123456Z",
  "user_id": 1,
  "stack_trace": "..."
}
```

## Implementation Details

### Middleware

The `LogRequests` middleware is registered in the global middleware stack in `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ... other middleware
    \App\Http\Middleware\LogRequests::class,
];
```

This ensures all requests are logged, including those that fail authentication.

### Service

The `RequestLoggingService` handles all logging logic:

- `logRequest()`: Logs incoming requests
- `logResponse()`: Logs successful responses
- `logError()`: Logs errors and exceptions

### Request ID Tracking

Each request is assigned a unique request ID in the format: `req_{uniqid}_{random_hex}`

This ID is:
- Generated when the request is logged
- Stored in the request attributes
- Included in all related logs (request, response, error)
- Can be used to correlate logs across the request lifecycle

## Usage

### Accessing Logs

Request logs are stored in `storage/logs/requests.log` (daily rotation).

View recent logs:
```bash
tail -f storage/logs/requests.log
```

### Filtering Logs

Filter logs by request ID:
```bash
grep "req_65a1b2c3d_4e5f6g7h" storage/logs/requests.log
```

Filter logs by status code:
```bash
grep '"status_code": 500' storage/logs/requests.log
```

Filter logs by user ID:
```bash
grep '"user_id": 1' storage/logs/requests.log
```

### Monitoring

Monitor slow requests (>100ms):
```bash
grep -E '"response_time_ms": [0-9]{3,}' storage/logs/requests.log
```

Monitor errors:
```bash
grep '"error_type"' storage/logs/requests.log
```

## Performance Considerations

- Request logging adds minimal overhead (~1-2ms per request)
- Logging is performed synchronously but is very fast
- For high-traffic applications, consider using async logging
- Log rotation is configured to keep 30 days of logs by default

## Security Considerations

- Sensitive data is automatically redacted
- Logs should be stored securely with restricted access
- Consider encrypting logs in production
- Regularly review logs for suspicious activity
- Use log aggregation services for centralized monitoring

## Testing

Request logging is tested with:

### Unit Tests
- `tests/Unit/RequestLoggingServiceTest.php`
- Tests the service logic in isolation
- Verifies sensitive data filtering
- Tests all HTTP methods

### Feature Tests
- `tests/Feature/RequestLoggingFeatureTest.php`
- Tests the middleware integration
- Verifies logging doesn't break request processing
- Tests with real API endpoints

Run tests:
```bash
php artisan test tests/Unit/RequestLoggingServiceTest.php
php artisan test tests/Feature/RequestLoggingFeatureTest.php
```

## Troubleshooting

### Logs Not Being Created

1. Check that `storage/logs` directory exists and is writable
2. Verify `LOG_REQUESTS_LEVEL` is set to a valid level
3. Check Laravel logs for errors: `tail -f storage/logs/laravel.log`

### Sensitive Data Not Being Redacted

1. Check that the field name matches one in the `SENSITIVE_FIELDS` list
2. Field names are case-insensitive
3. Partial matches are supported (e.g., "card_number" matches "credit_card_number")

### Performance Issues

1. Check log file size: `du -h storage/logs/requests.log`
2. Consider increasing `LOG_REQUESTS_DAYS` to rotate logs more frequently
3. Consider using async logging for high-traffic applications

## Future Enhancements

- Async logging for better performance
- Log aggregation integration (ELK, Splunk, etc.)
- Real-time alerting on errors
- Performance analytics dashboard
- Request/response sampling for high-traffic applications
- Structured logging with additional context
