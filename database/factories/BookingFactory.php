<?php

namespace Database\Factories;

use App\Models\Ride;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ride_id' => Ride::factory(),
            'passenger_id' => User::factory(),
            'seats_booked' => fake()->numberBetween(1, 4),
            'passenger_name' => fake()->name(),
            'passenger_phone' => fake()->numerify('##########'),
            'special_instructions' => fake()->sentence(),
            'luggage_info' => fake()->sentence(),
            'accessibility_requirements' => null,
            'booking_status' => 'pending',
            'cancellation_reason' => null,
        ];
    }

    /**
     * Indicate that the booking is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_status' => 'confirmed',
        ]);
    }

    /**
     * Indicate that the booking is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_status' => 'completed',
        ]);
    }

    /**
     * Indicate that the booking is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_status' => 'cancelled',
            'cancellation_reason' => fake()->sentence(),
        ]);
    }
}
