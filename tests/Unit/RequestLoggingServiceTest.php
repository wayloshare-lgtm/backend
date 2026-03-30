<?php

namespace Tests\Unit;

use App\Services\RequestLoggingService;
use Illuminate\Http\Request;
use Tests\TestCase;

class RequestLoggingServiceTest extends TestCase
{
    private RequestLoggingService $loggingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loggingService = new RequestLoggingService();
    }

    /**
     * Test that request logging captures basic request information
     */
    public function test_log_request_captures_basic_info(): void
    {
        $request = Request::create('/api/v1/users', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $context = $this->loggingService->logRequest($request);

        $this->assertArrayHasKey('request_id', $context);
        $this->assertArrayHasKey('method', $context);
        $this->assertArrayHasKey('path', $context);
        $this->assertArrayHasKey('timestamp', $context);
        $this->assertEquals('POST', $context['method']);
        $this->assertEquals('/api/v1/users', $context['path']);
    }

    /**
     * Test that sensitive fields are redacted from logs
     */
    public function test_log_request_redacts_sensitive_fields(): void
    {
        $request = Request::create('/api/v1/auth/login', 'POST', [
            'email' => 'user@example.com',
            'password' => 'secret123',
            'token' => 'abc123xyz',
        ]);

        $context = $this->loggingService->logRequest($request);

        $this->assertEquals('***REDACTED***', $context['body']['password']);
        $this->assertEquals('***REDACTED***', $context['body']['token']);
        $this->assertEquals('user@example.com', $context['body']['email']);
    }

    /**
     * Test that query parameters are captured
     */
    public function test_log_request_captures_query_params(): void
    {
        $request = Request::create('/api/v1/rides?page=1&limit=10', 'GET');

        $context = $this->loggingService->logRequest($request);

        $this->assertArrayHasKey('query_params', $context);
        $this->assertEquals(1, $context['query_params']['page']);
        $this->assertEquals(10, $context['query_params']['limit']);
    }

    /**
     * Test that headers are captured and sensitive headers are redacted
     */
    public function test_log_request_filters_sensitive_headers(): void
    {
        $request = Request::create('/api/v1/users', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer token123',
            'HTTP_X_API_KEY' => 'api_key_secret',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $context = $this->loggingService->logRequest($request);

        $this->assertArrayHasKey('headers', $context);
        // Headers might be stored with different keys, so check if any authorization header is redacted
        $hasRedactedAuth = false;
        foreach ($context['headers'] as $key => $value) {
            if (stripos($key, 'authorization') !== false) {
                $hasRedactedAuth = true;
                $this->assertTrue(
                    (is_array($value) && $value === ['***REDACTED***']) || $value === '***REDACTED***',
                    "Authorization header should be redacted"
                );
            }
        }
        $this->assertTrue($hasRedactedAuth, "Authorization header should be present");
    }

    /**
     * Test that nested sensitive data is redacted
     */
    public function test_log_request_redacts_nested_sensitive_data(): void
    {
        $request = Request::create('/api/v1/payment', 'POST', [
            'amount' => 100,
            'payment_details' => [
                'card_number' => '4111111111111111',
                'cvv' => '123',
                'name' => 'John Doe',
            ],
        ]);

        $context = $this->loggingService->logRequest($request);

        $this->assertEquals('***REDACTED***', $context['body']['payment_details']['card_number']);
        $this->assertEquals('***REDACTED***', $context['body']['payment_details']['cvv']);
        $this->assertEquals('John Doe', $context['body']['payment_details']['name']);
    }

    /**
     * Test that GET requests don't include body
     */
    public function test_log_request_excludes_body_for_get(): void
    {
        $request = Request::create('/api/v1/users', 'GET');

        $context = $this->loggingService->logRequest($request);

        $this->assertArrayNotHasKey('body', $context);
    }

    /**
     * Test that request ID is stored in request attributes
     */
    public function test_log_request_stores_request_id(): void
    {
        $request = Request::create('/api/v1/users', 'GET');

        $this->loggingService->logRequest($request);

        $this->assertNotNull($request->attributes->get('request_id'));
        $this->assertStringStartsWith('req_', $request->attributes->get('request_id'));
    }

    /**
     * Test that multiple sensitive field variations are redacted
     */
    public function test_log_request_redacts_all_sensitive_variations(): void
    {
        $request = Request::create('/api/v1/auth', 'POST', [
            'password' => 'secret',
            'password_confirmation' => 'secret',
            'access_token' => 'token123',
            'refresh_token' => 'refresh123',
            'api_key' => 'key123',
            'credit_card' => '4111111111111111',
            'cvv' => '123',
            'ssn' => '123-45-6789',
        ]);

        $context = $this->loggingService->logRequest($request);

        foreach (['password', 'password_confirmation', 'access_token', 'refresh_token', 'api_key', 'credit_card', 'cvv', 'ssn'] as $field) {
            $this->assertEquals('***REDACTED***', $context['body'][$field], "Field $field should be redacted");
        }
    }

    /**
     * Test that request ID is unique
     */
    public function test_request_ids_are_unique(): void
    {
        $request1 = Request::create('/api/v1/users', 'GET');
        $request2 = Request::create('/api/v1/users', 'GET');

        $context1 = $this->loggingService->logRequest($request1);
        $context2 = $this->loggingService->logRequest($request2);

        $this->assertNotEquals($context1['request_id'], $context2['request_id']);
    }

    /**
     * Test that user agent is captured
     */
    public function test_log_request_captures_user_agent(): void
    {
        $request = Request::create('/api/v1/users', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Flutter/1.0',
        ]);

        $context = $this->loggingService->logRequest($request);

        $this->assertArrayHasKey('user_agent', $context);
        $this->assertNotEmpty($context['user_agent']);
    }

    /**
     * Test that IP address is captured
     */
    public function test_log_request_captures_ip(): void
    {
        $request = Request::create('/api/v1/users', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $context = $this->loggingService->logRequest($request);

        $this->assertArrayHasKey('ip', $context);
        $this->assertNotEmpty($context['ip']);
    }

    /**
     * Test that PUT request body is captured
     */
    public function test_log_request_captures_put_body(): void
    {
        $request = Request::create('/api/v1/user/profile', 'PUT', [
            'display_name' => 'John Doe',
            'bio' => 'Test bio',
        ]);

        $context = $this->loggingService->logRequest($request);

        $this->assertArrayHasKey('body', $context);
        $this->assertEquals('John Doe', $context['body']['display_name']);
    }

    /**
     * Test that PATCH request body is captured
     */
    public function test_log_request_captures_patch_body(): void
    {
        $request = Request::create('/api/v1/user/profile', 'PATCH', [
            'display_name' => 'Jane Doe',
        ]);

        $context = $this->loggingService->logRequest($request);

        $this->assertArrayHasKey('body', $context);
        $this->assertEquals('Jane Doe', $context['body']['display_name']);
    }

    /**
     * Test that timestamp is in ISO8601 format
     */
    public function test_log_request_timestamp_is_iso8601(): void
    {
        $request = Request::create('/api/v1/users', 'GET');

        $context = $this->loggingService->logRequest($request);

        $this->assertArrayHasKey('timestamp', $context);
        // Check if it matches ISO8601 format
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $context['timestamp']);
    }

    /**
     * Test that URL is captured
     */
    public function test_log_request_captures_url(): void
    {
        $request = Request::create('/api/v1/users?page=1', 'GET');

        $context = $this->loggingService->logRequest($request);

        $this->assertArrayHasKey('url', $context);
        $this->assertStringContainsString('/api/v1/users', $context['url']);
    }
}
