<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsConfigurationTest extends TestCase
{
    /**
     * Test that CORS configuration file exists and is properly configured
     */
    public function test_cors_config_file_exists(): void
    {
        $config = config('cors');
        
        $this->assertNotNull($config);
        $this->assertArrayHasKey('paths', $config);
        $this->assertArrayHasKey('allowed_methods', $config);
        $this->assertArrayHasKey('allowed_origins', $config);
        $this->assertArrayHasKey('allowed_headers', $config);
        $this->assertArrayHasKey('exposed_headers', $config);
        $this->assertArrayHasKey('supports_credentials', $config);
    }

    /**
     * Test that API paths are configured for CORS
     */
    public function test_api_paths_configured(): void
    {
        $config = config('cors');
        
        $this->assertContains('api/*', $config['paths']);
        $this->assertContains('sanctum/csrf-cookie', $config['paths']);
    }

    /**
     * Test that allowed methods include all required HTTP methods
     */
    public function test_allowed_methods_configured(): void
    {
        $config = config('cors');
        $methods = $config['allowed_methods'];
        
        $this->assertContains('GET', $methods);
        $this->assertContains('POST', $methods);
        $this->assertContains('PUT', $methods);
        $this->assertContains('DELETE', $methods);
        $this->assertContains('PATCH', $methods);
        $this->assertContains('OPTIONS', $methods);
    }

    /**
     * Test that production origins are configured
     */
    public function test_production_origins_configured(): void
    {
        $config = config('cors');
        $origins = $config['allowed_origins'];
        
        $this->assertContains('https://wayloshare.com', $origins);
        $this->assertContains('https://app.wayloshare.com', $origins);
        $this->assertContains('https://admin.wayloshare.com', $origins);
    }

    /**
     * Test that development origins are configured
     */
    public function test_development_origins_configured(): void
    {
        $config = config('cors');
        $origins = $config['allowed_origins'];
        
        $this->assertContains('http://localhost:3000', $origins);
        $this->assertContains('http://localhost:8000', $origins);
        $this->assertContains('http://localhost:8080', $origins);
    }

    /**
     * Test that required headers are allowed
     */
    public function test_required_headers_allowed(): void
    {
        $config = config('cors');
        $headers = $config['allowed_headers'];
        
        $this->assertContains('Content-Type', $headers);
        $this->assertContains('Authorization', $headers);
        $this->assertContains('X-Firebase-Token', $headers);
        $this->assertContains('X-Device-ID', $headers);
        $this->assertContains('X-Device-Type', $headers);
        $this->assertContains('X-App-Version', $headers);
    }

    /**
     * Test that exposed headers are configured
     */
    public function test_exposed_headers_configured(): void
    {
        $config = config('cors');
        $exposedHeaders = $config['exposed_headers'];
        
        $this->assertContains('Content-Type', $exposedHeaders);
        $this->assertContains('X-RateLimit-Limit', $exposedHeaders);
        $this->assertContains('X-RateLimit-Remaining', $exposedHeaders);
        $this->assertContains('X-RateLimit-Reset', $exposedHeaders);
    }

    /**
     * Test that credentials are supported
     */
    public function test_credentials_supported(): void
    {
        $config = config('cors');
        
        $this->assertTrue($config['supports_credentials']);
    }

    /**
     * Test that max age is set to 24 hours
     */
    public function test_max_age_set_correctly(): void
    {
        $config = config('cors');
        
        $this->assertEquals(86400, $config['max_age']);  // 24 hours
    }

    /**
     * Test that origin patterns are configured for subdomains
     */
    public function test_origin_patterns_configured(): void
    {
        $config = config('cors');
        $patterns = $config['allowed_origins_patterns'];
        
        $this->assertNotEmpty($patterns);
        $this->assertContains('#^https://.*\.wayloshare\.com$#', $patterns);
    }

    /**
     * Test that CORS middleware is registered in global middleware
     */
    public function test_cors_middleware_registered(): void
    {
        // Verify that the CORS middleware is properly configured by checking
        // that CORS headers are returned in responses
        $response = $this->withHeader('Origin', 'https://app.wayloshare.com')
            ->get('/api/v1/health-check');

        $this->assertNotNull($response->headers->get('Access-Control-Allow-Origin'));
    }

    /**
     * Test that allowed origin returns CORS headers
     */
    public function test_allowed_origin_returns_cors_headers(): void
    {
        $response = $this->withHeader('Origin', 'https://app.wayloshare.com')
            ->get('/api/v1/health-check');

        $this->assertEquals('https://app.wayloshare.com', $response->headers->get('Access-Control-Allow-Origin'));
    }

    /**
     * Test that localhost origin is allowed in development
     */
    public function test_localhost_origin_allowed(): void
    {
        $response = $this->withHeader('Origin', 'http://localhost:3000')
            ->get('/api/v1/health-check');

        $this->assertEquals('http://localhost:3000', $response->headers->get('Access-Control-Allow-Origin'));
    }

    /**
     * Test that credentials header is present
     */
    public function test_credentials_header_present(): void
    {
        $response = $this->withHeader('Origin', 'https://app.wayloshare.com')
            ->get('/api/v1/health-check');

        $this->assertEquals('true', $response->headers->get('Access-Control-Allow-Credentials'));
    }

    /**
     * Test that CORS applies to all API routes
     */
    public function test_cors_applies_to_all_api_routes(): void
    {
        $routes = [
            '/api/v1/health-check',
            '/api/v1/rides',
            '/api/v1/bookings',
            '/api/v1/reviews',
        ];

        foreach ($routes as $route) {
            $response = $this->withHeader('Origin', 'https://app.wayloshare.com')
                ->get($route);

            $this->assertEquals(
                'https://app.wayloshare.com',
                $response->headers->get('Access-Control-Allow-Origin'),
                "CORS headers missing for route: $route"
            );
        }
    }

    /**
     * Test that CORS applies to sanctum csrf-cookie endpoint
     */
    public function test_cors_applies_to_sanctum_csrf_cookie(): void
    {
        $response = $this->withHeader('Origin', 'https://app.wayloshare.com')
            ->get('/sanctum/csrf-cookie');

        $this->assertEquals('https://app.wayloshare.com', $response->headers->get('Access-Control-Allow-Origin'));
    }
}
