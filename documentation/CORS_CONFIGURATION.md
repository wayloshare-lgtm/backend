# CORS Configuration Documentation

## Overview

Cross-Origin Resource Sharing (CORS) is a security feature that allows the Flutter mobile app and web clients to make requests to the WayloShare API. This document explains the CORS configuration and how it works.

## What is CORS?

CORS is a mechanism that allows restricted resources on a web server to be requested from another domain outside the domain from which the first resource was served. It's essential for:

- Flutter mobile apps making API requests
- Web applications accessing the API
- Third-party integrations
- Development and testing environments

## Configuration Location

The CORS configuration is defined in `config/cors.php` and is automatically applied by the `Illuminate\Http\Middleware\HandleCors` middleware registered in the global middleware stack.

## Configuration Details

### Paths
```php
'paths' => ['api/*', 'sanctum/csrf-cookie']
```

CORS headers are applied to:
- All API routes (`api/*`)
- Sanctum CSRF cookie endpoint

### Allowed Methods

```php
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS']
```

The API supports the following HTTP methods:
- **GET**: Retrieve data
- **POST**: Create new resources
- **PUT**: Replace entire resources
- **DELETE**: Remove resources
- **PATCH**: Partial updates
- **OPTIONS**: Preflight requests (automatic)

### Allowed Origins

```php
'allowed_origins' => [
    'https://wayloshare.com',
    'https://app.wayloshare.com',
    'https://admin.wayloshare.com',
    'http://localhost:3000',      // Local development (web)
    'http://localhost:8000',      // Local development (API)
    'http://localhost:8080',      // Local development (Flutter web)
    'http://127.0.0.1:3000',
    'http://127.0.0.1:8000',
    'http://127.0.0.1:8080',
]
```

**Production Origins:**
- `https://wayloshare.com` - Main domain
- `https://app.wayloshare.com` - Mobile app web version
- `https://admin.wayloshare.com` - Admin dashboard

**Development Origins:**
- `http://localhost:*` - Local development servers
- `http://127.0.0.1:*` - Loopback development servers

### Allowed Origins Patterns

```php
'allowed_origins_patterns' => [
    '#^https://.*\.wayloshare\.com$#',
]
```

Regex pattern to allow any subdomain of `wayloshare.com` in production. This enables:
- `https://api.wayloshare.com`
- `https://staging.wayloshare.com`
- `https://dev.wayloshare.com`
- Any other subdomain

### Allowed Headers

```php
'allowed_headers' => [
    'Accept',
    'Accept-Language',
    'Content-Type',
    'Authorization',
    'X-Requested-With',
    'X-CSRF-Token',
    'X-API-Key',
    'X-Device-ID',
    'X-Device-Type',
    'X-App-Version',
    'X-Firebase-Token',
]
```

**Standard Headers:**
- `Accept` - Response format preference
- `Accept-Language` - Preferred language
- `Content-Type` - Request body format (application/json)
- `Authorization` - Firebase token or Bearer token

**Custom Headers:**
- `X-Requested-With` - Identifies AJAX requests
- `X-CSRF-Token` - CSRF protection
- `X-API-Key` - API key authentication
- `X-Device-ID` - Device identifier
- `X-Device-Type` - Device type (android, ios, web)
- `X-App-Version` - App version
- `X-Firebase-Token` - Firebase authentication token

### Exposed Headers

```php
'exposed_headers' => [
    'Content-Type',
    'X-Total-Count',
    'X-Page-Count',
    'X-Current-Page',
    'X-Per-Page',
    'X-RateLimit-Limit',
    'X-RateLimit-Remaining',
    'X-RateLimit-Reset',
]
```

These headers are exposed to the client and can be read by JavaScript:

**Pagination Headers:**
- `X-Total-Count` - Total number of records
- `X-Page-Count` - Total number of pages
- `X-Current-Page` - Current page number
- `X-Per-Page` - Records per page

**Rate Limiting Headers:**
- `X-RateLimit-Limit` - Maximum requests allowed
- `X-RateLimit-Remaining` - Requests remaining
- `X-RateLimit-Reset` - Unix timestamp when limit resets

### Max Age

```php
'max_age' => 86400  // 24 hours
```

Browsers cache preflight requests for 24 hours. This reduces the number of OPTIONS requests and improves performance.

### Supports Credentials

```php
'supports_credentials' => true
```

Allows credentials (cookies, authorization headers) to be sent with requests. This is required for:
- Firebase authentication
- Session management
- Secure API calls

## How CORS Works

### Preflight Request (OPTIONS)

For complex requests (POST, PUT, DELETE, PATCH), browsers send an automatic preflight request:

```
OPTIONS /api/v1/rides HTTP/1.1
Host: api.wayloshare.com
Origin: https://app.wayloshare.com
Access-Control-Request-Method: POST
Access-Control-Request-Headers: Content-Type, Authorization
```

The server responds with:

```
HTTP/1.1 200 OK
Access-Control-Allow-Origin: https://app.wayloshare.com
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization, X-Firebase-Token
Access-Control-Max-Age: 86400
Access-Control-Allow-Credentials: true
```

### Actual Request

After the preflight succeeds, the browser sends the actual request:

```
POST /api/v1/rides HTTP/1.1
Host: api.wayloshare.com
Origin: https://app.wayloshare.com
Content-Type: application/json
Authorization: Bearer <firebase-token>

{
  "from_location": "...",
  "to_location": "..."
}
```

The server responds with:

```
HTTP/1.1 201 Created
Access-Control-Allow-Origin: https://app.wayloshare.com
Access-Control-Allow-Credentials: true
Content-Type: application/json

{
  "success": true,
  "data": { ... }
}
```

## Flutter Mobile App Integration

### Android Configuration

The Flutter app on Android doesn't require special CORS configuration as it makes direct HTTP requests. However, ensure:

1. The API URL is correct in the app configuration
2. Firebase authentication is properly configured
3. SSL/TLS certificates are valid

### iOS Configuration

Similar to Android, iOS doesn't require special CORS configuration. Ensure:

1. The API URL is correct in the app configuration
2. Firebase authentication is properly configured
3. SSL/TLS certificates are valid
4. App Transport Security (ATS) allows the API domain

### Flutter Web Configuration

For Flutter web, CORS is critical:

1. The web app origin must be in `allowed_origins`
2. All required headers must be in `allowed_headers`
3. Credentials must be supported (`supports_credentials: true`)

## Testing CORS Configuration

### Using cURL

```bash
# Preflight request
curl -X OPTIONS https://api.wayloshare.com/api/v1/rides \
  -H "Origin: https://app.wayloshare.com" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type, Authorization" \
  -v

# Actual request
curl -X POST https://api.wayloshare.com/api/v1/rides \
  -H "Origin: https://app.wayloshare.com" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"from_location": "...", "to_location": "..."}' \
  -v
```

### Using Postman

1. Open Postman
2. Create a new request
3. Set the URL to `https://api.wayloshare.com/api/v1/rides`
4. Set the method to POST
5. Add headers:
   - `Content-Type: application/json`
   - `Authorization: Bearer <token>`
6. Add the request body
7. Send the request
8. Check the response headers for CORS headers

### Using Browser DevTools

1. Open the Flutter web app in a browser
2. Open DevTools (F12)
3. Go to the Network tab
4. Make an API request
5. Look for the preflight OPTIONS request
6. Check the response headers for CORS headers

## Common CORS Issues

### Issue: "No 'Access-Control-Allow-Origin' header"

**Cause:** The origin is not in the `allowed_origins` list.

**Solution:** Add the origin to `config/cors.php`:

```php
'allowed_origins' => [
    // ... existing origins
    'https://your-domain.com',
],
```

### Issue: "Method not allowed"

**Cause:** The HTTP method is not in `allowed_methods`.

**Solution:** Ensure the method is in the list:

```php
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
```

### Issue: "Header not allowed"

**Cause:** The header is not in `allowed_headers`.

**Solution:** Add the header to the list:

```php
'allowed_headers' => [
    // ... existing headers
    'X-Custom-Header',
],
```

### Issue: "Credentials not allowed"

**Cause:** `supports_credentials` is set to `false`.

**Solution:** Set it to `true`:

```php
'supports_credentials' => true,
```

## Security Considerations

1. **Whitelist Origins:** Only allow trusted origins. Avoid using `*` for `allowed_origins`.
2. **Limit Methods:** Only allow necessary HTTP methods.
3. **Limit Headers:** Only allow necessary headers.
4. **Use HTTPS:** Always use HTTPS in production.
5. **Validate Tokens:** Always validate Firebase tokens on the server.
6. **Rate Limiting:** Implement rate limiting to prevent abuse.
7. **Monitor Requests:** Log and monitor CORS requests for suspicious activity.

## Environment-Specific Configuration

### Development

For local development, the configuration includes localhost origins:

```php
'allowed_origins' => [
    'http://localhost:3000',
    'http://localhost:8000',
    'http://localhost:8080',
    'http://127.0.0.1:3000',
    'http://127.0.0.1:8000',
    'http://127.0.0.1:8080',
],
```

### Production

For production, only include production domains:

```php
'allowed_origins' => [
    'https://wayloshare.com',
    'https://app.wayloshare.com',
    'https://admin.wayloshare.com',
],
```

To use environment-specific configuration, modify `config/cors.php`:

```php
'allowed_origins' => env('APP_ENV') === 'production'
    ? [
        'https://wayloshare.com',
        'https://app.wayloshare.com',
        'https://admin.wayloshare.com',
    ]
    : [
        'http://localhost:3000',
        'http://localhost:8000',
        'http://localhost:8080',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8000',
        'http://127.0.0.1:8080',
    ],
```

## Middleware Stack

The CORS middleware is registered in the global middleware stack in `app/Http/Kernel.php`:

```php
protected $middleware = [
    \App\Http\Middleware\TrustProxies::class,
    \Illuminate\Http\Middleware\HandleCors::class,  // ← CORS middleware
    \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
    // ... other middleware
];
```

This ensures CORS headers are applied to all requests before other middleware processes them.

## References

- [MDN: Cross-Origin Resource Sharing (CORS)](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)
- [Laravel CORS Documentation](https://laravel.com/docs/11.x/cors)
- [Flutter Web CORS Guide](https://flutter.dev/docs/development/platform-integration/web)
- [Firebase Authentication](https://firebase.google.com/docs/auth)

## Support

For CORS-related issues or questions, contact the development team or refer to the API documentation.
