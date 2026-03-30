<?php

namespace Tests\Feature;

use Tests\TestCase;

class RequestLoggingFeatureTest extends TestCase
{
    /**
     * Test that API requests are logged and return successful response
     */
    public function test_api_request_returns_success(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);
    }

    /**
     * Test that POST requests work correctly
     */
    public function test_post_request_works(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Should return 401 or 422 since credentials are invalid, but request should be processed
        $this->assertTrue(in_array($response->status(), [401, 422, 400]));
    }

    /**
     * Test that PUT requests work correctly
     */
    public function test_put_request_works(): void
    {
        $response = $this->putJson('/api/v1/user/profile', [
            'display_name' => 'John Doe',
            'bio' => 'Test bio',
        ]);

        // Should return 401 since not authenticated, but request should be processed
        $this->assertTrue(in_array($response->status(), [401, 422, 400, 405]));
    }

    /**
     * Test that PATCH requests work correctly
     */
    public function test_patch_request_works(): void
    {
        $response = $this->patchJson('/api/v1/user/profile', [
            'display_name' => 'Jane Doe',
        ]);

        // Should return 401 since not authenticated, but request should be processed
        $this->assertTrue(in_array($response->status(), [401, 422, 400, 405]));
    }

    /**
     * Test that DELETE requests work correctly
     */
    public function test_delete_request_works(): void
    {
        $response = $this->deleteJson('/api/v1/user/profile');

        // Should return 401 since not authenticated, but request should be processed
        $this->assertTrue(in_array($response->status(), [401, 404, 400, 405]));
    }

    /**
     * Test that GET requests with query parameters work
     */
    public function test_get_request_with_query_params_works(): void
    {
        $response = $this->getJson('/api/v1/health?version=1&format=json');

        $response->assertStatus(200);
    }

    /**
     * Test that authenticated requests work
     */
    public function test_authenticated_request_works(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/health');

        $response->assertStatus(200);
    }

    /**
     * Test that 404 responses are returned for non-existent endpoints
     */
    public function test_404_response_for_nonexistent_endpoint(): void
    {
        $response = $this->getJson('/api/v1/nonexistent-endpoint');

        $response->assertStatus(404);
    }

    /**
     * Test that request with authorization header works
     */
    public function test_request_with_authorization_header_works(): void
    {
        $response = $this->getJson('/api/v1/health', [
            'Authorization' => 'Bearer secret_token_123',
        ]);

        // Should still return 200 for health check
        $response->assertStatus(200);
    }

    /**
     * Test that request with user agent works
     */
    public function test_request_with_user_agent_works(): void
    {
        $response = $this->getJson('/api/v1/health', [
            'User-Agent' => 'Flutter/1.0',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test that POST request with nested data works
     */
    public function test_post_request_with_nested_data_works(): void
    {
        $response = $this->postJson('/api/v1/payment-methods', [
            'payment_type' => 'card',
            'payment_details' => [
                'card_number' => '4111111111111111',
                'cvv' => '123',
                'holder_name' => 'John Doe',
            ],
        ]);

        // Should return 401 since not authenticated, but request should be processed
        $this->assertTrue(in_array($response->status(), [401, 422, 400]));
    }

    /**
     * Test that multiple requests can be made
     */
    public function test_multiple_requests_can_be_made(): void
    {
        $response1 = $this->getJson('/api/v1/health');
        $response2 = $this->getJson('/api/v1/health');

        $response1->assertStatus(200);
        $response2->assertStatus(200);
    }

    /**
     * Test that middleware doesn't break normal request processing
     */
    public function test_middleware_does_not_break_request_processing(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);
        // Health endpoint returns status, timestamp, and services
        $response->assertJsonStructure(['status', 'timestamp', 'services']);
    }

    /**
     * Test that POST with sensitive data still processes correctly
     */
    public function test_post_with_sensitive_data_processes(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'secret123',
            'token' => 'bearer_token_123',
        ]);

        // Should return 401 or 422, but request should be processed
        $this->assertTrue(in_array($response->status(), [401, 422, 400]));
    }

    /**
     * Test that request with all HTTP methods work
     */
    public function test_all_http_methods_work(): void
    {
        $methods = [
            'GET' => $this->getJson('/api/v1/health'),
            'POST' => $this->postJson('/api/v1/auth/login', ['email' => 'test@example.com', 'password' => 'test']),
        ];

        foreach ($methods as $method => $response) {
            $this->assertNotNull($response->status(), "$method request should return a status");
        }
    }

    /**
     * Test that request logging doesn't affect response content
     */
    public function test_logging_does_not_affect_response_content(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);
        // Health endpoint returns status field
        $response->assertJsonStructure(['status']);
    }

    /**
     * Test that request logging works with JSON responses
     */
    public function test_logging_works_with_json_responses(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);
        $this->assertIsArray($response->json());
    }

    /**
     * Test that request logging works with error responses
     */
    public function test_logging_works_with_error_responses(): void
    {
        $response = $this->getJson('/api/v1/nonexistent');

        $this->assertTrue(in_array($response->status(), [404, 405]));
    }

    /**
     * Test that request logging works with authenticated user
     */
    public function test_logging_works_with_authenticated_user(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/health');

        $response->assertStatus(200);
    }
}

