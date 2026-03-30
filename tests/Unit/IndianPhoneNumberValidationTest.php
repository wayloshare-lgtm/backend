<?php

namespace Tests\Unit;

use App\Rules\IndianPhoneNumber;
use PHPUnit\Framework\TestCase;

class IndianPhoneNumberValidationTest extends TestCase
{
    private IndianPhoneNumber $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new IndianPhoneNumber();
    }

    /**
     * Test valid 10-digit phone numbers
     */
    public function test_valid_10_digit_phone_numbers(): void
    {
        $validPhones = [
            '9876543210',
            '8765432109',
            '7654321098',
            '6543210987',
            '5432109876',
            '1234567890',
            '0000000000',
            '9999999999',
        ];

        foreach ($validPhones as $phone) {
            $this->assertTrue(
                $this->rule->passes('phone', $phone),
                "Phone number {$phone} should be valid"
            );
        }
    }

    /**
     * Test valid phone numbers as integers
     */
    public function test_valid_phone_numbers_as_integers(): void
    {
        $validPhones = [
            9876543210,
            8765432109,
            1234567890,
        ];

        foreach ($validPhones as $phone) {
            $this->assertTrue(
                $this->rule->passes('phone', $phone),
                "Phone number {$phone} should be valid"
            );
        }
    }

    /**
     * Test invalid phone numbers with less than 10 digits
     */
    public function test_invalid_phone_numbers_less_than_10_digits(): void
    {
        $invalidPhones = [
            '123456789',      // 9 digits
            '12345678',       // 8 digits
            '1234567',        // 7 digits
            '123456',         // 6 digits
            '12345',          // 5 digits
            '1234',           // 4 digits
            '123',            // 3 digits
            '12',             // 2 digits
            '1',              // 1 digit
            '',               // empty
        ];

        foreach ($invalidPhones as $phone) {
            $this->assertFalse(
                $this->rule->passes('phone', $phone),
                "Phone number '{$phone}' should be invalid"
            );
        }
    }

    /**
     * Test invalid phone numbers with more than 10 digits
     */
    public function test_invalid_phone_numbers_more_than_10_digits(): void
    {
        $invalidPhones = [
            '12345678901',    // 11 digits
            '123456789012',   // 12 digits
            '1234567890123',  // 13 digits
            '98765432101',    // 11 digits
        ];

        foreach ($invalidPhones as $phone) {
            $this->assertFalse(
                $this->rule->passes('phone', $phone),
                "Phone number {$phone} should be invalid"
            );
        }
    }

    /**
     * Test invalid phone numbers with non-numeric characters
     */
    public function test_invalid_phone_numbers_with_non_numeric_characters(): void
    {
        $invalidPhones = [
            '987654321a',     // contains letter
            '98765432-10',    // contains hyphen
            '9876 543210',    // contains space
            '(987)6543210',   // contains parentheses
            '+919876543210',  // contains plus sign
            '98.76543210',    // contains dot
            '9876543210x',    // contains letter at end
            'abcdefghij',     // all letters
            '98765432@0',     // contains special character
        ];

        foreach ($invalidPhones as $phone) {
            $this->assertFalse(
                $this->rule->passes('phone', $phone),
                "Phone number '{$phone}' should be invalid"
            );
        }
    }

    /**
     * Test error message
     */
    public function test_error_message(): void
    {
        $expectedMessage = 'The :attribute must be a valid Indian phone number (10 digits).';
        $this->assertEquals($expectedMessage, $this->rule->message());
    }
}
