<?php

namespace Tests\Unit;

use App\Rules\Latitude;
use PHPUnit\Framework\TestCase;

class LatitudeValidationTest extends TestCase
{
    private Latitude $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new Latitude();
    }

    /**
     * Test valid latitude - equator
     */
    public function test_valid_latitude_equator()
    {
        $this->assertTrue($this->rule->passes('latitude', 0));
    }

    /**
     * Test valid latitude - north pole
     */
    public function test_valid_latitude_north_pole()
    {
        $this->assertTrue($this->rule->passes('latitude', 90));
    }

    /**
     * Test valid latitude - south pole
     */
    public function test_valid_latitude_south_pole()
    {
        $this->assertTrue($this->rule->passes('latitude', -90));
    }

    /**
     * Test valid latitude - positive value
     */
    public function test_valid_latitude_positive()
    {
        $this->assertTrue($this->rule->passes('latitude', 45.5));
    }

    /**
     * Test valid latitude - negative value
     */
    public function test_valid_latitude_negative()
    {
        $this->assertTrue($this->rule->passes('latitude', -45.5));
    }

    /**
     * Test valid latitude - decimal precision
     */
    public function test_valid_latitude_decimal_precision()
    {
        $this->assertTrue($this->rule->passes('latitude', 28.7041));
    }

    /**
     * Test invalid latitude - exceeds north pole
     */
    public function test_invalid_latitude_exceeds_north()
    {
        $this->assertFalse($this->rule->passes('latitude', 90.1));
    }

    /**
     * Test invalid latitude - exceeds south pole
     */
    public function test_invalid_latitude_exceeds_south()
    {
        $this->assertFalse($this->rule->passes('latitude', -90.1));
    }

    /**
     * Test invalid latitude - far exceeds north
     */
    public function test_invalid_latitude_far_exceeds_north()
    {
        $this->assertFalse($this->rule->passes('latitude', 180));
    }

    /**
     * Test invalid latitude - far exceeds south
     */
    public function test_invalid_latitude_far_exceeds_south()
    {
        $this->assertFalse($this->rule->passes('latitude', -180));
    }

    /**
     * Test invalid latitude - non-numeric string
     */
    public function test_invalid_latitude_non_numeric_string()
    {
        $this->assertFalse($this->rule->passes('latitude', 'invalid'));
    }

    /**
     * Test invalid latitude - empty string
     */
    public function test_invalid_latitude_empty_string()
    {
        $this->assertFalse($this->rule->passes('latitude', ''));
    }

    /**
     * Test invalid latitude - null
     */
    public function test_invalid_latitude_null()
    {
        $this->assertFalse($this->rule->passes('latitude', null));
    }

    /**
     * Test invalid latitude - array
     */
    public function test_invalid_latitude_array()
    {
        $this->assertFalse($this->rule->passes('latitude', []));
    }

    /**
     * Test error message
     */
    public function test_error_message()
    {
        $message = $this->rule->message();
        $this->assertStringContainsString('latitude', $message);
        $this->assertStringContainsString('-90', $message);
        $this->assertStringContainsString('90', $message);
    }
}
