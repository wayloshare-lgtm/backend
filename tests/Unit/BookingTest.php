<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that booking can be created with all attributes
     */
    public function test_booking_can_be_created(): void
    {
        $passenger = User::factory()->create();
        $ride = Ride::factory()->create();

        $booking = Booking::create([
            'ride_id' => $ride->id,
            'passenger_id' => $passenger->id,
            'seats_booked' => 2,
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
            'special_instructions' => 'Please wait at the gate',
            'luggage_info' => '2 bags',
            'accessibility_requirements' => 'Wheelchair accessible',
            'booking_status' => 'confirmed',
        ]);

        $this->assertNotNull($booking->id);
        $this->assertEquals($ride->id, $booking->ride_id);
        $this->assertEquals($passenger->id, $booking->passenger_id);
        $this->assertEquals(2, $booking->seats_booked);
        $this->assertEquals('confirmed', $booking->booking_status);
    }

    /**
     * Test that booking belongs to a ride
     */
    public function test_booking_belongs_to_ride(): void
    {
        $ride = Ride::factory()->create();
        $booking = Booking::factory()->create(['ride_id' => $ride->id]);

        $this->assertTrue($booking->ride()->is($ride));
    }

    /**
     * Test that booking belongs to a passenger
     */
    public function test_booking_belongs_to_passenger(): void
    {
        $passenger = User::factory()->create();
        $booking = Booking::factory()->create(['passenger_id' => $passenger->id]);

        $this->assertTrue($booking->passenger()->is($passenger));
    }

    /**
     * Test that seats_booked is cast to integer
     */
    public function test_seats_booked_is_cast_to_integer(): void
    {
        $ride = Ride::factory()->create();
        $passenger = User::factory()->create();

        $booking = Booking::create([
            'ride_id' => $ride->id,
            'passenger_id' => $passenger->id,
            'seats_booked' => '3',
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
        ]);

        $this->assertIsInt($booking->seats_booked);
        $this->assertEquals(3, $booking->seats_booked);
    }

    /**
     * Test that booking status can be pending
     */
    public function test_booking_status_can_be_pending(): void
    {
        $ride = Ride::factory()->create();
        $passenger = User::factory()->create();

        $booking = Booking::create([
            'ride_id' => $ride->id,
            'passenger_id' => $passenger->id,
            'seats_booked' => 1,
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
            'booking_status' => 'pending',
        ]);

        $this->assertEquals('pending', $booking->booking_status);
    }

    /**
     * Test that booking status can be confirmed
     */
    public function test_booking_status_can_be_confirmed(): void
    {
        $ride = Ride::factory()->create();
        $passenger = User::factory()->create();

        $booking = Booking::create([
            'ride_id' => $ride->id,
            'passenger_id' => $passenger->id,
            'seats_booked' => 1,
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
            'booking_status' => 'confirmed',
        ]);

        $this->assertEquals('confirmed', $booking->booking_status);
    }

    /**
     * Test that booking status can be completed
     */
    public function test_booking_status_can_be_completed(): void
    {
        $ride = Ride::factory()->create();
        $passenger = User::factory()->create();

        $booking = Booking::create([
            'ride_id' => $ride->id,
            'passenger_id' => $passenger->id,
            'seats_booked' => 1,
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
            'booking_status' => 'completed',
        ]);

        $this->assertEquals('completed', $booking->booking_status);
    }

    /**
     * Test that booking status can be cancelled
     */
    public function test_booking_status_can_be_cancelled(): void
    {
        $ride = Ride::factory()->create();
        $passenger = User::factory()->create();

        $booking = Booking::create([
            'ride_id' => $ride->id,
            'passenger_id' => $passenger->id,
            'seats_booked' => 1,
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
            'booking_status' => 'cancelled',
        ]);

        $this->assertEquals('cancelled', $booking->booking_status);
    }

    /**
     * Test that special instructions can be null
     */
    public function test_special_instructions_can_be_null(): void
    {
        $ride = Ride::factory()->create();
        $passenger = User::factory()->create();

        $booking = Booking::create([
            'ride_id' => $ride->id,
            'passenger_id' => $passenger->id,
            'seats_booked' => 1,
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
            'special_instructions' => null,
        ]);

        $this->assertNull($booking->special_instructions);
    }

    /**
     * Test that luggage info can be null
     */
    public function test_luggage_info_can_be_null(): void
    {
        $ride = Ride::factory()->create();
        $passenger = User::factory()->create();

        $booking = Booking::create([
            'ride_id' => $ride->id,
            'passenger_id' => $passenger->id,
            'seats_booked' => 1,
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
            'luggage_info' => null,
        ]);

        $this->assertNull($booking->luggage_info);
    }

    /**
     * Test that accessibility requirements can be null
     */
    public function test_accessibility_requirements_can_be_null(): void
    {
        $ride = Ride::factory()->create();
        $passenger = User::factory()->create();

        $booking = Booking::create([
            'ride_id' => $ride->id,
            'passenger_id' => $passenger->id,
            'seats_booked' => 1,
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
            'accessibility_requirements' => null,
        ]);

        $this->assertNull($booking->accessibility_requirements);
    }

    /**
     * Test that cancellation reason can be stored
     */
    public function test_cancellation_reason_can_be_stored(): void
    {
        $ride = Ride::factory()->create();
        $passenger = User::factory()->create();

        $booking = Booking::create([
            'ride_id' => $ride->id,
            'passenger_id' => $passenger->id,
            'seats_booked' => 1,
            'passenger_name' => 'John Doe',
            'passenger_phone' => '9876543210',
            'booking_status' => 'cancelled',
            'cancellation_reason' => 'Driver cancelled',
        ]);

        $this->assertEquals('Driver cancelled', $booking->cancellation_reason);
    }

    /**
     * Test that booking can be updated
     */
    public function test_booking_can_be_updated(): void
    {
        $ride = Ride::factory()->create();
        $passenger = User::factory()->create();
        $booking = Booking::factory()->create([
            'ride_id' => $ride->id,
            'passenger_id' => $passenger->id,
        ]);

        $booking->update(['booking_status' => 'completed']);

        $this->assertEquals('completed', $booking->booking_status);
    }

    /**
     * Test that booking is deleted when ride is deleted
     */
    public function test_booking_deleted_when_ride_deleted(): void
    {
        $ride = Ride::factory()->create();
        $booking = Booking::factory()->create(['ride_id' => $ride->id]);

        $bookingId = $booking->id;
        $ride->delete();

        $this->assertNull(Booking::find($bookingId));
    }

    /**
     * Test that multiple bookings can be created for a ride
     */
    public function test_ride_can_have_multiple_bookings(): void
    {
        $ride = Ride::factory()->create();

        Booking::factory()->create(['ride_id' => $ride->id]);
        Booking::factory()->create(['ride_id' => $ride->id]);

        $this->assertEquals(2, $ride->bookings()->count());
    }

    /**
     * Test that multiple bookings can be created by a passenger
     */
    public function test_passenger_can_have_multiple_bookings(): void
    {
        $passenger = User::factory()->create();

        Booking::factory()->create(['passenger_id' => $passenger->id]);
        Booking::factory()->create(['passenger_id' => $passenger->id]);

        $this->assertEquals(2, $passenger->bookings()->count());
    }
}
