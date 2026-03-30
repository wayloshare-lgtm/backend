<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test that login endpoint is rate limited to 5 requests per minute
     */
    public function test_login_endpoint_rate_limited_to_5_per_minute(): void
    {
        // Make 5 successful requests
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);
            // First 5 should not be rate limited (may fail auth but not rate limit)
            $this->assertNotEquals(429, $response->status());
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $this->assertEquals(429, $response->status());
    }

    /**
     * Test that logout endpoint is rate limited to 10 requests per minute
     */
    public function test_logout_endpoint_rate_limited_to_10_per_minute(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        // Make 10 successful requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->withHeader('Authorization', "Bearer $token")
                ->postJson('/api/v1/auth/logout');
            $this->assertNotEquals(429, $response->status());
        }

        // 11th request should be rate limited
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/logout');
        $this->assertEquals(429, $response->status());
    }

    /**
     * Test that delete account endpoint is rate limited to 5 requests per minute
     */
    public function test_delete_account_endpoint_rate_limited_to_5_per_minute(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        // Make 5 requests
        for ($i = 0; $i < 5; $i++) {
            $response = $this->withHeader('Authorization', "Bearer $token")
                ->deleteJson('/api/v1/auth/delete-account');
            $this->assertNotEquals(429, $response->status());
        }

        // 6th request should be rate limited
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson('/api/v1/auth/delete-account');
        $this->assertEquals(429, $response->status());
    }

    /**
     * Test that update profile endpoint is rate limited to 10 requests per minute
     */
    public function test_update_profile_endpoint_rate_limited_to_10_per_minute(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        // Make 10 requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->withHeader('Authorization', "Bearer $token")
                ->postJson('/api/v1/user/profile', [
                    'display_name' => 'Test User ' . $i,
                ]);
            $this->assertNotEquals(429, $response->status());
        }

        // 11th request should be rate limited
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/user/profile', [
                'display_name' => 'Test User',
            ]);
        $this->assertEquals(429, $response->status());
    }

    /**
     * Test that payment method endpoints are rate limited to 10 requests per minute
     */
    public function test_payment_method_endpoints_rate_limited_to_10_per_minute(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        // Make 10 requests to add payment method
        for ($i = 0; $i < 10; $i++) {
            $response = $this->withHeader('Authorization', "Bearer $token")
                ->postJson('/api/v1/payment-methods', [
                    'payment_type' => 'card',
                    'payment_details' => json_encode(['card_number' => '1234567890123456']),
                ]);
            $this->assertNotEquals(429, $response->status());
        }

        // 11th request should be rate limited
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'card',
                'payment_details' => json_encode(['card_number' => '1234567890123456']),
            ]);
        $this->assertEquals(429, $response->status());
    }

    /**
     * Test that booking creation is rate limited to 10 requests per minute
     */
    public function test_create_booking_endpoint_rate_limited_to_10_per_minute(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        // Make 10 requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->withHeader('Authorization', "Bearer $token")
                ->postJson('/api/v1/bookings', [
                    'ride_id' => 1,
                    'seats_booked' => 1,
                ]);
            $this->assertNotEquals(429, $response->status());
        }

        // 11th request should be rate limited
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/bookings', [
                'ride_id' => 1,
                'seats_booked' => 1,
            ]);
        $this->assertEquals(429, $response->status());
    }

    /**
     * Test that rate limiting is per IP address for unauthenticated requests
     */
    public function test_rate_limiting_per_ip_for_unauthenticated_requests(): void
    {
        // Make 5 requests from one IP
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);
            $this->assertNotEquals(429, $response->status());
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $this->assertEquals(429, $response->status());
    }

    /**
     * Test that rate limiting returns proper 429 response with headers
     */
    public function test_rate_limit_response_includes_proper_headers(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        // Make 10 requests to hit the limit
        for ($i = 0; $i < 10; $i++) {
            $this->withHeader('Authorization', "Bearer $token")
                ->postJson('/api/v1/auth/logout');
        }

        // 11th request should be rate limited with proper headers
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/logout');

        $this->assertEquals(429, $response->status());
        $this->assertTrue($response->headers->has('Retry-After'));
        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
    }

    /**
     * Test that read operations are not rate limited
     */
    public function test_read_operations_not_rate_limited(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        // Make many requests to get profile (should not be rate limited)
        for ($i = 0; $i < 20; $i++) {
            $response = $this->withHeader('Authorization', "Bearer $token")
                ->getJson('/api/v1/user/profile');
            $this->assertNotEquals(429, $response->status());
        }
    }

    /**
     * Test that driver verification endpoints are rate limited
     */
    public function test_driver_verification_endpoints_rate_limited(): void
    {
        $driver = User::factory()->create();
        $token = $driver->createToken('test-token')->plainTextToken;

        // Make 10 requests to verification endpoint
        for ($i = 0; $i < 10; $i++) {
            $response = $this->withHeader('Authorization', "Bearer $token")
                ->postJson('/api/v1/driver/verification', [
                    'dl_number' => 'DL123456' . $i,
                ]);
            $this->assertNotEquals(429, $response->status());
        }

        // 11th request should be rate limited
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/driver/verification', [
                'dl_number' => 'DL123456999',
            ]);
        $this->assertEquals(429, $response->status());
    }
}
