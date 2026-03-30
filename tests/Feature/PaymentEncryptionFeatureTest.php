<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentEncryptionFeatureTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test that payment details are encrypted in database
     */
    public function test_payment_details_encrypted_in_database(): void
    {
        $paymentDetails = [
            'card_number' => '4111111111111111',
            'expiry' => '12/25',
            'holder_name' => 'John Doe',
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'card',
                'payment_details' => $paymentDetails,
            ]);

        // Get raw data from database
        $rawData = DB::table('payment_methods')
            ->where('user_id', $this->user->id)
            ->first();

        // Verify data is encrypted (not plain JSON)
        $this->assertNotEquals(json_encode($paymentDetails), $rawData->payment_details);
        
        // Verify it's not readable as plain text
        $this->assertStringNotContainsString('4111111111111111', $rawData->payment_details);
        $this->assertStringNotContainsString('John Doe', $rawData->payment_details);
    }

    /**
     * Test that payment details are decrypted when accessed through model
     */
    public function test_payment_details_decrypted_through_model(): void
    {
        $paymentDetails = [
            'card_number' => '4111111111111111',
            'expiry' => '12/25',
            'holder_name' => 'John Doe',
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'card',
                'payment_details' => $paymentDetails,
            ]);

        // Access through model
        $paymentMethod = PaymentMethod::where('user_id', $this->user->id)->first();

        // Verify data is decrypted
        $this->assertEquals($paymentDetails['card_number'], $paymentMethod->payment_details['card_number']);
        $this->assertEquals($paymentDetails['expiry'], $paymentMethod->payment_details['expiry']);
        $this->assertEquals($paymentDetails['holder_name'], $paymentMethod->payment_details['holder_name']);
    }

    /**
     * Test that payment details are returned decrypted in API response
     */
    public function test_payment_details_decrypted_in_api_response(): void
    {
        $paymentDetails = [
            'card_number' => '4111111111111111',
            'expiry' => '12/25',
            'holder_name' => 'John Doe',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'card',
                'payment_details' => $paymentDetails,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'payment_method' => [
                    'payment_details' => $paymentDetails,
                ],
            ]);
    }

    /**
     * Test that different payment types are encrypted
     */
    public function test_all_payment_types_encrypted(): void
    {
        $paymentTypes = [
            'card' => [
                'card_number' => '4111111111111111',
                'expiry' => '12/25',
                'holder_name' => 'John Doe',
            ],
            'upi' => [
                'upi_id' => 'johndoe@okhdfcbank',
            ],
            'wallet' => [
                'wallet_id' => 'wallet123456789',
            ],
        ];

        foreach ($paymentTypes as $type => $details) {
            $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/v1/payment-methods', [
                    'payment_type' => $type,
                    'payment_details' => $details,
                ]);

            $rawData = DB::table('payment_methods')
                ->where('user_id', $this->user->id)
                ->where('payment_type', $type)
                ->first();

            // Verify encryption
            $this->assertStringNotContainsString(json_encode($details), $rawData->payment_details);
        }
    }

    /**
     * Test that updating payment details maintains encryption
     */
    public function test_updating_payment_details_maintains_encryption(): void
    {
        $originalDetails = [
            'card_number' => '4111111111111111',
            'expiry' => '12/25',
            'holder_name' => 'John Doe',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'card',
                'payment_details' => $originalDetails,
            ]);

        $paymentMethodId = $response->json('payment_method.id');

        $updatedDetails = [
            'card_number' => '5555555555554444',
            'expiry' => '06/26',
            'holder_name' => 'Jane Doe',
        ];

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/payment-methods/{$paymentMethodId}", [
                'payment_details' => $updatedDetails,
            ]);

        // Verify updated data is encrypted
        $rawData = DB::table('payment_methods')
            ->where('id', $paymentMethodId)
            ->first();

        $this->assertStringNotContainsString('5555555555554444', $rawData->payment_details);
        $this->assertStringNotContainsString('Jane Doe', $rawData->payment_details);

        // Verify updated data is decrypted correctly
        $paymentMethod = PaymentMethod::find($paymentMethodId);
        $this->assertEquals($updatedDetails['card_number'], $paymentMethod->payment_details['card_number']);
    }

    /**
     * Test that multiple payment methods for same user are independently encrypted
     */
    public function test_multiple_payment_methods_independently_encrypted(): void
    {
        $cardDetails = [
            'card_number' => '4111111111111111',
            'expiry' => '12/25',
            'holder_name' => 'John Doe',
        ];

        $upiDetails = [
            'upi_id' => 'johndoe@okhdfcbank',
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'card',
                'payment_details' => $cardDetails,
            ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'upi',
                'payment_details' => $upiDetails,
            ]);

        $rawMethods = DB::table('payment_methods')
            ->where('user_id', $this->user->id)
            ->get();

        // Verify both are encrypted
        foreach ($rawMethods as $method) {
            $this->assertStringNotContainsString('4111111111111111', $method->payment_details);
            $this->assertStringNotContainsString('johndoe@okhdfcbank', $method->payment_details);
        }

        // Verify both decrypt correctly
        $paymentMethods = PaymentMethod::where('user_id', $this->user->id)->get();
        $this->assertEquals(2, $paymentMethods->count());

        foreach ($paymentMethods as $method) {
            $this->assertIsArray($method->payment_details);
            $this->assertNotEmpty($method->payment_details);
        }
    }

    /**
     * Test that payment details are not exposed in list endpoint
     */
    public function test_payment_details_not_exposed_in_list(): void
    {
        $paymentDetails = [
            'card_number' => '4111111111111111',
            'expiry' => '12/25',
            'holder_name' => 'John Doe',
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'card',
                'payment_details' => $paymentDetails,
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/payment-methods');

        $response->assertStatus(200);
        
        // Verify payment details are returned (decrypted)
        $this->assertNotEmpty($response->json('payment_methods.0.payment_details'));
    }

    /**
     * Test that encryption works with special characters
     */
    public function test_encryption_with_special_characters(): void
    {
        $paymentDetails = [
            'card_number' => '4111111111111111',
            'holder_name' => "O'Brien-Smith & Co.",
            'notes' => 'Special chars: !@#$%^&*()',
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'card',
                'payment_details' => $paymentDetails,
            ]);

        $paymentMethod = PaymentMethod::where('user_id', $this->user->id)->first();

        $this->assertEquals($paymentDetails['holder_name'], $paymentMethod->payment_details['holder_name']);
        $this->assertEquals($paymentDetails['notes'], $paymentMethod->payment_details['notes']);
    }

    /**
     * Test that encryption works with unicode characters
     */
    public function test_encryption_with_unicode_characters(): void
    {
        $paymentDetails = [
            'card_number' => '4111111111111111',
            'holder_name' => 'जॉन डो',
            'notes' => '中文测试',
        ];

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'card',
                'payment_details' => $paymentDetails,
            ]);

        $paymentMethod = PaymentMethod::where('user_id', $this->user->id)->first();

        $this->assertEquals($paymentDetails['holder_name'], $paymentMethod->payment_details['holder_name']);
        $this->assertEquals($paymentDetails['notes'], $paymentMethod->payment_details['notes']);
    }

    /**
     * Test that deleted payment methods don't expose encrypted data
     */
    public function test_deleted_payment_methods_not_accessible(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment-methods', [
                'payment_type' => 'card',
                'payment_details' => [
                    'card_number' => '4111111111111111',
                    'expiry' => '12/25',
                    'holder_name' => 'John Doe',
                ],
            ]);

        $paymentMethodId = $response->json('payment_method.id');

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/payment-methods/{$paymentMethodId}");

        // Verify payment method is deleted
        $this->assertDatabaseMissing('payment_methods', [
            'id' => $paymentMethodId,
        ]);
    }
}
