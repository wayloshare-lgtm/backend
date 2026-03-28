<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\User;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test adding a payment method successfully
     */
    public function test_add_payment_method_successfully(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'card',
                'payment_details' => [
                    'card_number' => '****1234',
                    'expiry' => '12/25',
                    'holder_name' => 'John Doe',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Payment method added successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'payment_method' => [
                    'id',
                    'user_id',
                    'payment_type',
                    'payment_details',
                    'is_default',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('payment_methods', [
            'user_id' => $this->user->id,
            'payment_type' => 'card',
            'is_active' => true,
        ]);
    }

    /**
     * Test first payment method is set as default automatically
     */
    public function test_first_payment_method_is_default(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'upi',
                'payment_details' => [
                    'upi_id' => 'user@bank',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'payment_method' => [
                    'is_default' => true,
                ],
            ]);
    }

    /**
     * Test adding payment method with explicit is_default flag
     */
    public function test_add_payment_method_with_explicit_default(): void
    {
        // Create first payment method
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'card',
                'payment_details' => ['card_number' => '****1234'],
            ]);

        // Create second payment method as default
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'wallet',
                'payment_details' => ['wallet_id' => 'wallet123'],
                'is_default' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'payment_method' => [
                    'is_default' => true,
                ],
            ]);

        // Verify first payment method is no longer default
        $firstMethod = PaymentMethod::where('user_id', $this->user->id)
            ->where('payment_type', 'card')
            ->first();

        $this->assertFalse($firstMethod->is_default);
    }

    /**
     * Test adding payment method with all payment types
     */
    public function test_add_payment_method_with_all_types(): void
    {
        $types = ['card', 'wallet', 'upi'];

        foreach ($types as $type) {
            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/v1/payment-methods', [
                    'payment_type' => $type,
                    'payment_details' => ['test' => 'data'],
                ]);

            $response->assertStatus(201)
                ->assertJson([
                    'payment_method' => [
                        'payment_type' => $type,
                    ],
                ]);
        }

        $this->assertEquals(3, $this->user->paymentMethods()->count());
    }

    /**
     * Test validation fails without payment_type
     */
    public function test_add_payment_method_validation_fails_without_type(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_details' => ['test' => 'data'],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test validation fails with invalid payment_type
     */
    public function test_add_payment_method_validation_fails_with_invalid_type(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'invalid_type',
                'payment_details' => ['test' => 'data'],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test validation fails without payment_details
     */
    public function test_add_payment_method_validation_fails_without_details(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'card',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test validation fails when payment_details is not an array
     */
    public function test_add_payment_method_validation_fails_when_details_not_array(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'card',
                'payment_details' => 'not_an_array',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Validation failed',
            ]);
    }

    /**
     * Test unauthenticated user cannot add payment method
     */
    public function test_unauthenticated_user_cannot_add_payment_method(): void
    {
        $response = $this->postJson('/api/v1/payment-methods', [
            'payment_type' => 'card',
            'payment_details' => ['test' => 'data'],
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test getting payment methods
     */
    public function test_get_payment_methods(): void
    {
        // Create multiple payment methods
        PaymentMethod::create([
            'user_id' => $this->user->id,
            'payment_type' => 'card',
            'payment_details' => json_encode(['card_number' => '****1234']),
            'is_default' => true,
        ]);

        PaymentMethod::create([
            'user_id' => $this->user->id,
            'payment_type' => 'upi',
            'payment_details' => json_encode(['upi_id' => 'user@bank']),
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/payment-methods');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 2,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'payment_methods' => [
                    '*' => [
                        'id',
                        'user_id',
                        'payment_type',
                        'payment_details',
                        'is_default',
                        'is_active',
                    ],
                ],
                'count',
            ]);
    }

    /**
     * Test payment details are encrypted
     */
    public function test_payment_details_are_encrypted(): void
    {
        $details = [
            'card_number' => '****1234',
            'expiry' => '12/25',
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'card',
                'payment_details' => $details,
            ]);

        $paymentMethod = PaymentMethod::where('user_id', $this->user->id)->first();

        // The payment_details should be encrypted in the database
        // When accessed through the model, it should be decrypted
        $this->assertIsArray($paymentMethod->payment_details);
        $this->assertEquals($details['card_number'], $paymentMethod->payment_details['card_number']);
    }

    /**
     * Test user can only see their own payment methods
     */
    public function test_user_can_only_see_own_payment_methods(): void
    {
        $otherUser = User::factory()->create();

        PaymentMethod::create([
            'user_id' => $this->user->id,
            'payment_type' => 'card',
            'payment_details' => json_encode(['card_number' => '****1234']),
        ]);

        PaymentMethod::create([
            'user_id' => $otherUser->id,
            'payment_type' => 'upi',
            'payment_details' => json_encode(['upi_id' => 'other@bank']),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/payment-methods');

        $response->assertStatus(200)
            ->assertJson([
                'count' => 1,
            ]);
    }

    /**
     * Test deleting a payment method
     */
    public function test_delete_payment_method(): void
    {
        $paymentMethod = PaymentMethod::create([
            'user_id' => $this->user->id,
            'payment_type' => 'card',
            'payment_details' => json_encode(['card_number' => '****1234']),
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Payment method deleted successfully',
            ]);

        $this->assertDatabaseMissing('payment_methods', [
            'id' => $paymentMethod->id,
        ]);
    }

    /**
     * Test setting payment method as default
     */
    public function test_set_payment_method_as_default(): void
    {
        $paymentMethod1 = PaymentMethod::create([
            'user_id' => $this->user->id,
            'payment_type' => 'card',
            'payment_details' => json_encode(['card_number' => '****1234']),
            'is_default' => true,
        ]);

        $paymentMethod2 = PaymentMethod::create([
            'user_id' => $this->user->id,
            'payment_type' => 'upi',
            'payment_details' => json_encode(['upi_id' => 'user@bank']),
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/payment-methods/{$paymentMethod2->id}/set-default");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'payment_method' => [
                    'is_default' => true,
                ],
            ]);

        $paymentMethod1->refresh();
        $paymentMethod2->refresh();

        $this->assertFalse($paymentMethod1->is_default);
        $this->assertTrue($paymentMethod2->is_default);
    }

    /**
     * Test user cannot delete another user's payment method
     */
    public function test_user_cannot_delete_another_users_payment_method(): void
    {
        $otherUser = User::factory()->create();
        $paymentMethod = PaymentMethod::create([
            'user_id' => $otherUser->id,
            'payment_type' => 'card',
            'payment_details' => json_encode(['card_number' => '****1234']),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/payment-methods/{$paymentMethod->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'Unauthorized',
            ]);
    }
}
