<?php

namespace Tests\Unit;

use App\Rules\DateOfBirth;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class DateOfBirthValidationTest extends TestCase
{
    private DateOfBirth $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new DateOfBirth();
    }

    /**
     * Test valid DOB - exactly 18 years old
     */
    public function test_valid_dob_exactly_18_years()
    {
        $dob = Carbon::now()->subYears(18)->format('Y-m-d');
        $this->assertTrue($this->rule->passes('date_of_birth', $dob));
    }

    /**
     * Test valid DOB - older than 18 years
     */
    public function test_valid_dob_older_than_18()
    {
        $dob = Carbon::now()->subYears(30)->format('Y-m-d');
        $this->assertTrue($this->rule->passes('date_of_birth', $dob));
    }

    /**
     * Test valid DOB - much older
     */
    public function test_valid_dob_much_older()
    {
        $dob = Carbon::now()->subYears(60)->format('Y-m-d');
        $this->assertTrue($this->rule->passes('date_of_birth', $dob));
    }

    /**
     * Test invalid DOB - under 18 years
     */
    public function test_invalid_dob_under_18()
    {
        $dob = Carbon::now()->subYears(17)->format('Y-m-d');
        $this->assertFalse($this->rule->passes('date_of_birth', $dob));
    }

    /**
     * Test invalid DOB - just under 18 years
     */
    public function test_invalid_dob_just_under_18()
    {
        $dob = Carbon::now()->subYears(18)->addDays(1)->format('Y-m-d');
        $this->assertFalse($this->rule->passes('date_of_birth', $dob));
    }

    /**
     * Test invalid DOB - future date
     */
    public function test_invalid_dob_future_date()
    {
        $dob = Carbon::now()->addDays(1)->format('Y-m-d');
        $this->assertFalse($this->rule->passes('date_of_birth', $dob));
    }

    /**
     * Test invalid DOB - today's date
     */
    public function test_invalid_dob_today()
    {
        $dob = Carbon::now()->format('Y-m-d');
        $this->assertFalse($this->rule->passes('date_of_birth', $dob));
    }

    /**
     * Test invalid DOB - invalid date format
     */
    public function test_invalid_dob_invalid_format()
    {
        $this->assertFalse($this->rule->passes('date_of_birth', 'invalid-date'));
    }

    /**
     * Test invalid DOB - empty string
     */
    public function test_invalid_dob_empty_string()
    {
        $this->assertFalse($this->rule->passes('date_of_birth', ''));
    }

    /**
     * Test error message
     */
    public function test_error_message()
    {
        $message = $this->rule->message();
        $this->assertStringContainsString('18 years', $message);
        $this->assertStringContainsString('date of birth', $message);
    }
}
