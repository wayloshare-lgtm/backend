<?php

namespace Tests\Unit;

use App\Rules\Longitude;
use PHPUnit\Framework\TestCase;

class LongitudeValidationTest extends TestCase
{
    private Longitude $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new Longitude();
    }

    /**
     * Test valid longitude - prime meridian
     */
    public function test_valid_longitude_prime_meridian()
    {
        $this->assertTrue($this->rule->passes('longitude', 0));
    }

    /**
     * Test valid longitude - east
     */
    public function test_valid_longitude_east()
    {
        $this->assertTrue($this->rule->passes('longitude', 180));
    }

    /**
     * Test valid longitude - west
     */
    public function test_valid_longitude_west()
    {
        $this->assertTrue($this->rule->passes('longitude', -180));
    }

    /**
     * Test valid longitude - positive value
     */
    public function test_valid_longitude_positive()
    {
        $this->assertTrue($this->rule->passes('longitude', 77.2090));
    }

    /**
     * Test valid longitude - negative value
     */
    public function test_valid_longitude_negative()
    {
        $this->assertTrue($this->rule->passes('longitude', -122.4194));
    }

    /**
     * Test valid longitude - decimal precision
     */
    public function test_valid_longitude_decimal_precision()
    {
        $this->assertTrue($this->rule->passes('longitude', 77.2090));
    }

    /**
     * Test invalid longitude - exceeds east
     */
    public function test_invalid_longitude_exceeds_east()
    {
        $this->assertFalse($this->rule->passes('longitude', 180.1));
    }

    /**
     * Test invalid longitude - exceeds west
     */
    public function test_invalid_longitude_exceeds_west()
    {
        $this->assertFalse($this->rule->passes('longitude', -180.1));
    }

    /**
     * Test invalid longitude - far exceeds east
     */
    public function test_invalid_longitude_far_exceeds_east()
    {
        $this->assertFalse($this->rule->passes('longitude', 360));
    }

    /**
     * Test invalid longitude - far exceeds west
     */
    public function test_invalid_longitude_far_exceeds_west()
    {
        $this->assertFalse($this->rule->passes('longitude', -360));
    }

    /**
     * Test invalid longitude - non-numeric string
     */
    public function test_invalid_longitude_non_numeric_string()
    {
        $this->assertFalse($this->rule->passes('longitude', 'invalid'));
    }

    /**
     * Test invalid longitude - empty string
     */
    public function test_invalid_longitude_empty_string()
    {
        $this->assertFalse($this->rule->passes('longitude', ''));
    }

    /**
     * Test invalid longitude - null
     */
    public function test_invalid_longitude_null()
    {
        $this->assertFalse($this->rule->passes('longitude', null));
    }

    /**
     * Test invalid longitude - array
     */
    public function test_invalid_longitude_array()
    {
        $this->assertFalse($this->rule->passes('longitude', []));
    }

    /**
     * Test error message
     */
    public function test_error_message()
    {
        $message = $this->rule->message();
        $this->assertStringContainsString('longitude', $message);
        $this->assertStringContainsString('-180', $message);
        $this->assertStringContainsString('180', $message);
    }
}
