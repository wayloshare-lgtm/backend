<?php

namespace Tests\Unit;

use App\Rules\ValidEmail;
use PHPUnit\Framework\TestCase;

class EmailValidationTest extends TestCase
{
    private ValidEmail $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new ValidEmail();
    }

    /**
     * Test valid email addresses
     */
    public function test_valid_email_addresses(): void
    {
        $validEmails = [
            'user@example.com',
            'john.doe@example.com',
            'john+tag@example.co.uk',
            'test.email@subdomain.example.com',
            'user123@test-domain.com',
            'a@b.co',
            'test@localhost.localdomain',
            'user_name@example.com',
            'user-name@example.com',
            'user.name+tag@example.com',
        ];

        foreach ($validEmails as $email) {
            $this->assertTrue(
                $this->rule->passes('email', $email),
                "Email {$email} should be valid"
            );
        }
    }

    /**
     * Test invalid email addresses - missing @ symbol
     */
    public function test_invalid_email_missing_at_symbol(): void
    {
        $invalidEmails = [
            'userexample.com',
            'user.example.com',
            'user example.com',
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                $this->rule->passes('email', $email),
                "Email '{$email}' should be invalid"
            );
        }
    }

    /**
     * Test invalid email addresses - missing domain
     */
    public function test_invalid_email_missing_domain(): void
    {
        $invalidEmails = [
            'user@',
            'user@.',
            'user@.com',
            '@example.com',
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                $this->rule->passes('email', $email),
                "Email '{$email}' should be invalid"
            );
        }
    }

    /**
     * Test invalid email addresses - special characters
     */
    public function test_invalid_email_with_invalid_characters(): void
    {
        $invalidEmails = [
            'user@exam ple.com',
            'user@exam<ple.com',
            'user@exam>ple.com',
            'user@exam,ple.com',
            'user@exam;ple.com',
            'user@exam:ple.com',
            'user@exam[ple.com',
            'user@exam]ple.com',
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                $this->rule->passes('email', $email),
                "Email '{$email}' should be invalid"
            );
        }
    }

    /**
     * Test invalid email addresses - empty and whitespace
     */
    public function test_invalid_email_empty_and_whitespace(): void
    {
        $invalidEmails = [
            '',
            ' ',
            '  ',
            "\t",
            "\n",
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                $this->rule->passes('email', $email),
                "Email '{$email}' should be invalid"
            );
        }
    }

    /**
     * Test invalid email addresses - multiple @ symbols
     */
    public function test_invalid_email_multiple_at_symbols(): void
    {
        $invalidEmails = [
            'user@@example.com',
            'user@exam@ple.com',
            'user@example@com',
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                $this->rule->passes('email', $email),
                "Email '{$email}' should be invalid"
            );
        }
    }

    /**
     * Test error message
     */
    public function test_error_message(): void
    {
        $expectedMessage = 'The :attribute must be a valid email address.';
        $this->assertEquals($expectedMessage, $this->rule->message());
    }
}
