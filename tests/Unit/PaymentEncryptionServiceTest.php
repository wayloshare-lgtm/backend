<?php

namespace Tests\Unit;

use App\Services\PaymentEncryptionService;
use Tests\TestCase;

class PaymentEncryptionServiceTest extends TestCase
{
    /**
     * Test encryption and decryption of payment details
     */
    public function test_encrypt_and_decrypt_payment_details(): void
    {
        $originalData = [
            'card_number' => '4111111111111111',
            'expiry' => '12/25',
            'holder_name' => 'John Doe',
        ];

        $encrypted = PaymentEncryptionService::encrypt($originalData);
        $decrypted = PaymentEncryptionService::decrypt($encrypted);

        $this->assertNotEquals($originalData, $encrypted);
        $this->assertEquals($originalData, $decrypted);
    }

    /**
     * Test that encrypted data is different each time
     */
    public function test_encryption_produces_different_output_each_time(): void
    {
        $data = ['card_number' => '4111111111111111'];

        $encrypted1 = PaymentEncryptionService::encrypt($data);
        $encrypted2 = PaymentEncryptionService::encrypt($data);

        // Due to IV randomization, encrypted output should be different
        $this->assertNotEquals($encrypted1, $encrypted2);
        
        // But both should decrypt to the same value
        $this->assertEquals($data, PaymentEncryptionService::decrypt($encrypted1));
        $this->assertEquals($data, PaymentEncryptionService::decrypt($encrypted2));
    }

    /**
     * Test masking card payment details
     */
    public function test_mask_card_payment_details(): void
    {
        $details = [
            'card_number' => '4111111111111111',
            'expiry' => '12/25',
            'holder_name' => 'John Doe',
            'cvv' => '123',
        ];

        $masked = PaymentEncryptionService::maskSensitiveData($details, 'card');

        $this->assertEquals('****1111', $masked['card_number']);
        $this->assertEquals('12/25', $masked['expiry']);
        $this->assertEquals('John Doe', $masked['holder_name']);
        $this->assertArrayNotHasKey('cvv', $masked);
    }

    /**
     * Test masking UPI payment details
     */
    public function test_mask_upi_payment_details(): void
    {
        $details = [
            'upi_id' => 'johndoe@okhdfcbank',
        ];

        $masked = PaymentEncryptionService::maskSensitiveData($details, 'upi');

        $this->assertStringContainsString('****', $masked['upi_id']);
        $this->assertStringContainsString('@okhdfcbank', $masked['upi_id']);
        $this->assertStringNotContainsString('johndoe', $masked['upi_id']);
    }

    /**
     * Test masking wallet payment details
     */
    public function test_mask_wallet_payment_details(): void
    {
        $details = [
            'wallet_id' => 'wallet123456789',
        ];

        $masked = PaymentEncryptionService::maskSensitiveData($details, 'wallet');

        $this->assertEquals('****6789', $masked['wallet_id']);
    }

    /**
     * Test validation of card payment details
     */
    public function test_validate_card_payment_details(): void
    {
        $validDetails = [
            'card_number' => '4111111111111111',
            'expiry' => '12/25',
            'holder_name' => 'John Doe',
        ];

        $invalidDetails = [
            'card_number' => '4111111111111111',
            'expiry' => '12/25',
        ];

        $this->assertTrue(PaymentEncryptionService::validatePaymentDetails($validDetails, 'card'));
        $this->assertFalse(PaymentEncryptionService::validatePaymentDetails($invalidDetails, 'card'));
    }

    /**
     * Test validation of UPI payment details
     */
    public function test_validate_upi_payment_details(): void
    {
        $validDetails = [
            'upi_id' => 'johndoe@okhdfcbank',
        ];

        $invalidDetails = [
            'provider' => 'GooglePay',
        ];

        $this->assertTrue(PaymentEncryptionService::validatePaymentDetails($validDetails, 'upi'));
        $this->assertFalse(PaymentEncryptionService::validatePaymentDetails($invalidDetails, 'upi'));
    }

    /**
     * Test validation of wallet payment details
     */
    public function test_validate_wallet_payment_details(): void
    {
        $validDetails = [
            'wallet_id' => 'wallet123456789',
        ];

        $invalidDetails = [
            'provider' => 'Paytm',
        ];

        $this->assertTrue(PaymentEncryptionService::validatePaymentDetails($validDetails, 'wallet'));
        $this->assertFalse(PaymentEncryptionService::validatePaymentDetails($invalidDetails, 'wallet'));
    }

    /**
     * Test validation with invalid payment type
     */
    public function test_validate_with_invalid_payment_type(): void
    {
        $details = ['test' => 'data'];

        $this->assertFalse(PaymentEncryptionService::validatePaymentDetails($details, 'invalid_type'));
    }

    /**
     * Test encryption configuration check
     */
    public function test_encryption_is_configured(): void
    {
        $this->assertTrue(PaymentEncryptionService::isEncryptionConfigured());
    }

    /**
     * Test get encryption algorithm
     */
    public function test_get_encryption_algorithm(): void
    {
        $algorithm = PaymentEncryptionService::getEncryptionAlgorithm();

        $this->assertIsString($algorithm);
        $this->assertStringContainsString('AES', $algorithm);
    }

    /**
     * Test encryption with complex nested data
     */
    public function test_encrypt_complex_nested_data(): void
    {
        $complexData = [
            'card_number' => '4111111111111111',
            'expiry' => '12/25',
            'holder_name' => 'John Doe',
            'billing_address' => [
                'street' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
            ],
            'metadata' => [
                'issuer' => 'Visa',
                'country' => 'US',
            ],
        ];

        $encrypted = PaymentEncryptionService::encrypt($complexData);
        $decrypted = PaymentEncryptionService::decrypt($encrypted);

        $this->assertEquals($complexData, $decrypted);
        $this->assertEquals('123 Main St', $decrypted['billing_address']['street']);
        $this->assertEquals('Visa', $decrypted['metadata']['issuer']);
    }

    /**
     * Test that decryption fails with invalid data
     */
    public function test_decryption_fails_with_invalid_data(): void
    {
        $this->expectException(\Exception::class);

        PaymentEncryptionService::decrypt('invalid_encrypted_data');
    }

    /**
     * Test masking preserves non-sensitive data
     */
    public function test_masking_preserves_non_sensitive_data(): void
    {
        $details = [
            'card_number' => '4111111111111111',
            'expiry' => '12/25',
            'holder_name' => 'John Doe',
            'issuer' => 'Visa',
        ];

        $masked = PaymentEncryptionService::maskSensitiveData($details, 'card');

        $this->assertEquals('12/25', $masked['expiry']);
        $this->assertEquals('John Doe', $masked['holder_name']);
        $this->assertEquals('Visa', $masked['issuer']);
    }
}
