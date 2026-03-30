<?php

namespace Tests\Unit;

use App\Rules\FutureDate;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class FutureDateValidationTest extends TestCase
{
    private FutureDate $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new FutureDate();
    }

    /**
     * Test valid future date - tomorrow
     */
    public function test_valid_future_date_tomorrow()
    {
        $date = Carbon::now()->addDays(1)->format('Y-m-d');
        $this->assertTrue($this->rule->passes('expiry_date', $date));
    }

    /**
     * Test valid future date - next month
     */
    public function test_valid_future_date_next_month()
    {
        $date = Carbon::now()->addMonths(1)->format('Y-m-d');
        $this->assertTrue($this->rule->passes('expiry_date', $date));
    }

    /**
     * Test valid future date - next year
     */
    public function test_valid_future_date_next_year()
    {
        $date = Carbon::now()->addYears(1)->format('Y-m-d');
        $this->assertTrue($this->rule->passes('expiry_date', $date));
    }

    /**
     * Test valid future date - far future
     */
    public function test_valid_future_date_far_future()
    {
        $date = Carbon::now()->addYears(10)->format('Y-m-d');
        $this->assertTrue($this->rule->passes('expiry_date', $date));
    }

    /**
     * Test invalid future date - today
     */
    public function test_invalid_future_date_today()
    {
        $date = Carbon::now()->format('Y-m-d');
        $this->assertFalse($this->rule->passes('expiry_date', $date));
    }

    /**
     * Test invalid future date - yesterday
     */
    public function test_invalid_future_date_yesterday()
    {
        $date = Carbon::now()->subDays(1)->format('Y-m-d');
        $this->assertFalse($this->rule->passes('expiry_date', $date));
    }

    /**
     * Test invalid future date - last month
     */
    public function test_invalid_future_date_last_month()
    {
        $date = Carbon::now()->subMonths(1)->format('Y-m-d');
        $this->assertFalse($this->rule->passes('expiry_date', $date));
    }

    /**
     * Test invalid future date - last year
     */
    public function test_invalid_future_date_last_year()
    {
        $date = Carbon::now()->subYears(1)->format('Y-m-d');
        $this->assertFalse($this->rule->passes('expiry_date', $date));
    }

    /**
     * Test invalid future date - invalid format
     */
    public function test_invalid_future_date_invalid_format()
    {
        $this->assertFalse($this->rule->passes('expiry_date', 'invalid-date'));
    }

    /**
     * Test invalid future date - empty string
     */
    public function test_invalid_future_date_empty_string()
    {
        $this->assertFalse($this->rule->passes('expiry_date', ''));
    }

    /**
     * Test error message
     */
    public function test_error_message()
    {
        $message = $this->rule->message();
        $this->assertStringContainsString('future date', $message);
    }
}
