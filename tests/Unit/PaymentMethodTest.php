<?php

namespace Tests\Unit;

use App\Models\PaymentMethod;
use App\Models\User;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    /**
     * Test that a payment method can be created with encrypted JSON details
     */
    public function test_payment_method_can_be_created_with_details(): void
    {
        $user = User::factory()->create();

        $paymentMethod = PaymentMethod::create([
            'user_id' => $user->id,
            'payment_type' => 'card',
            'payment_details' => json_encode([
                'card_number' => '****1234',
                'expiry' => '12/25',
                'holder_name' => 'John Doe',
            ]),
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->assertNotNull($paymentMethod->id);
        $this->assertEquals($user->id, $paymentMethod->user_id);
        $this->assertEquals('card', $paymentMethod->payment_type);
        $this->assertTrue($paymentMethod->is_default);
        $this->assertTrue($paymentMethod->is_active);
    }

    /**
     * Test that payment method belongs to a user
     */
    public function test_payment_method_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($paymentMethod->user()->is($user));
    }

    /**
     * Test that payment details can be stored as JSON
     */
    public function test_payment_details_stored_as_json(): void
    {
        $user = User::factory()->create();
        $details = [
            'upi_id' => 'user@bank',
            'provider' => 'GooglePay',
        ];

        $paymentMethod = PaymentMethod::create([
            'user_id' => $user->id,
            'payment_type' => 'upi',
            'payment_details' => json_encode($details),
            'is_active' => true,
        ]);

        $storedDetails = json_decode($paymentMethod->payment_details, true);
        $this->assertEquals($details['upi_id'], $storedDetails['upi_id']);
        $this->assertEquals($details['provider'], $storedDetails['provider']);
    }

    /**
     * Test that multiple payment methods can be created for a user
     */
    public function test_user_can_have_multiple_payment_methods(): void
    {
        $user = User::factory()->create();

        PaymentMethod::create([
            'user_id' => $user->id,
            'payment_type' => 'card',
            'payment_details' => json_encode(['card_number' => '****1234']),
        ]);

        PaymentMethod::create([
            'user_id' => $user->id,
            'payment_type' => 'upi',
            'payment_details' => json_encode(['upi_id' => 'user@bank']),
        ]);

        $this->assertEquals(2, $user->paymentMethods()->count());
    }

    /**
     * Test that payment method is deleted when user is deleted
     */
    public function test_payment_method_deleted_when_user_deleted(): void
    {
        $user = User::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create(['user_id' => $user->id]);

        $paymentMethodId = $paymentMethod->id;
        $user->delete();

        $this->assertNull(PaymentMethod::find($paymentMethodId));
    }
}
