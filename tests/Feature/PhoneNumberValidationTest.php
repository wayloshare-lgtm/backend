<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ride;
use App\Models\Booking;
use App\Models\DriverProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneNumberValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $driver;
    private Ride $ride;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a driver user
        $this->driver = User::factory()->driver()->create();

        // Create driver profile
        DriverProfile::factory()->create([
            'user_id' => $this->driver->id,
        ]);

        // Create a rider user
        $this->user = User::factory()->create([
            'role' => 'rider',
        ]);

        // Create a ride
        $this->ride = Ride::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'requested',
        ]);
    }

    /**
     * Test booking creation with valid phone number
     */
    public function test_booking_creation_with_valid_phone_number(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/bookings', [
                'ride_id' => $this->ride->id,
                'seats_booked' => 1,
                'passenger_name' => 'John Doe',
                'passenger_phone' => '9876543210',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('booking.passenger_phone', '9876543210');
    }

    /**
     * Test booking creation with valid phone number as integer
     */
    public function test_booking_creation_with_valid_phone_number_as_integer(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/bookings', [
                'ride_id' => $this->ride->id,
                'seats_booked' => 1,
                'passenger_name' => 'John Doe',
                'passenger_phone' => 9876543210,
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
    }

    /**
     * Test booking creation with invalid phone number (less than 10 digits)
     */
    public function test_booking_creation_with_invalid_phone_number_less_than_10_digits(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/bookings', [
                'ride_id' => $this->ride->id,
                'seats_booked' => 1,
                'passenger_name' => 'John Doe',
                'passenger_phone' => '123456789',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'Validation failed');
        $this->assertArrayHasKey('passenger_phone', $response->json('errors'));
    }

    /**
     * Test booking creation with invalid phone number (more than 10 digits)
     */
    public function test_booking_creation_with_invalid_phone_number_more_than_10_digits(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/bookings', [
                'ride_id' => $this->ride->id,
                'seats_booked' => 1,
                'passenger_name' => 'John Doe',
                'passenger_phone' => '12345678901',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'Validation failed');
        $this->assertArrayHasKey('passenger_phone', $response->json('errors'));
    }

    /**
     * Test booking creation with invalid phone number (non-numeric)
     */
    public function test_booking_creation_with_invalid_phone_number_non_numeric(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/bookings', [
                'ride_id' => $this->ride->id,
                'seats_booked' => 1,
                'passenger_name' => 'John Doe',
                'passenger_phone' => '987654321a',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'Validation failed');
        $this->assertArrayHasKey('passenger_phone', $response->json('errors'));
    }

    /**
     * Test booking creation with invalid phone number (with special characters)
     */
    public function test_booking_creation_with_invalid_phone_number_with_special_characters(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/bookings', [
                'ride_id' => $this->ride->id,
                'seats_booked' => 1,
                'passenger_name' => 'John Doe',
                'passenger_phone' => '+919876543210',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'Validation failed');
        $this->assertArrayHasKey('passenger_phone', $response->json('errors'));
    }

    /**
     * Test driver profile creation with valid emergency contact
     */
    public function test_driver_profile_creation_with_valid_emergency_contact(): void
    {
        $newDriver = User::factory()->driver()->create();

        $response = $this->actingAs($newDriver)
            ->postJson('/api/v1/driver/profile', [
                'license_number' => 'DL123456',
                'vehicle_type' => 'sedan',
                'vehicle_number' => 'KA01AB1234',
                'emergency_contact' => '9876543210',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
    }

    /**
     * Test driver profile creation with invalid emergency contact (less than 10 digits)
     */
    public function test_driver_profile_creation_with_invalid_emergency_contact_less_than_10_digits(): void
    {
        $newDriver = User::factory()->driver()->create();

        $response = $this->actingAs($newDriver)
            ->postJson('/api/v1/driver/profile', [
                'license_number' => 'DL123456',
                'vehicle_type' => 'sedan',
                'vehicle_number' => 'KA01AB1234',
                'emergency_contact' => '123456789',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'Validation failed');
        $this->assertArrayHasKey('emergency_contact', $response->json('errors'));
    }

    /**
     * Test driver profile creation with invalid emergency contact (more than 10 digits)
     */
    public function test_driver_profile_creation_with_invalid_emergency_contact_more_than_10_digits(): void
    {
        $newDriver = User::factory()->driver()->create();

        $response = $this->actingAs($newDriver)
            ->postJson('/api/v1/driver/profile', [
                'license_number' => 'DL123456',
                'vehicle_type' => 'sedan',
                'vehicle_number' => 'KA01AB1234',
                'emergency_contact' => '12345678901',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'Validation failed');
        $this->assertArrayHasKey('emergency_contact', $response->json('errors'));
    }

    /**
     * Test driver profile creation with invalid emergency contact (non-numeric)
     */
    public function test_driver_profile_creation_with_invalid_emergency_contact_non_numeric(): void
    {
        $newDriver = User::factory()->driver()->create();

        $response = $this->actingAs($newDriver)
            ->postJson('/api/v1/driver/profile', [
                'license_number' => 'DL123456',
                'vehicle_type' => 'sedan',
                'vehicle_number' => 'KA01AB1234',
                'emergency_contact' => '987654321a',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'Validation failed');
        $this->assertArrayHasKey('emergency_contact', $response->json('errors'));
    }

    /**
     * Test user profile update with valid emergency contact
     */
    public function test_user_profile_update_with_valid_emergency_contact(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'emergency_contact' => '9876543210',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    /**
     * Test user profile update with invalid emergency contact
     */
    public function test_user_profile_update_with_invalid_emergency_contact(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/user/profile', [
                'emergency_contact' => '123456789',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'Validation failed');
        $this->assertArrayHasKey('emergency_contact', $response->json('errors'));
    }

    /**
     * Test error message contains helpful information
     */
    public function test_error_message_contains_helpful_information(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/bookings', [
                'ride_id' => $this->ride->id,
                'seats_booked' => 1,
                'passenger_name' => 'John Doe',
                'passenger_phone' => 'invalid',
            ]);

        $response->assertStatus(422);
        $errors = $response->json('errors.passenger_phone');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('10 digits', $errors[0]);
    }
}
