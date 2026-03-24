<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ride>
 */
class RideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rider_id' => User::factory(),
            'driver_id' => User::factory()->driver(),
            'pickup_location' => fake()->address(),
            'pickup_lat' => fake()->latitude(),
            'pickup_lng' => fake()->longitude(),
            'dropoff_location' => fake()->address(),
            'dropoff_lat' => fake()->latitude(),
            'dropoff_lng' => fake()->longitude(),
            'estimated_distance_km' => fake()->randomFloat(2, 1, 50),
            'estimated_duration_minutes' => fake()->numberBetween(5, 120),
            'estimated_fare' => fake()->randomFloat(2, 50, 500),
            'actual_distance_km' => null,
            'actual_duration_minutes' => null,
            'actual_fare' => null,
            'toll_amount' => 0,
            'status' => 'requested',
            'cancellation_reason' => null,
            'requested_at' => now(),
            'accepted_at' => null,
            'arrived_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ];
    }

    /**
     * Indicate that the ride is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'accepted_at' => now()->subHours(2),
            'arrived_at' => now()->subHours(2),
            'started_at' => now()->subHours(1, 50),
            'completed_at' => now(),
            'actual_distance_km' => fake()->randomFloat(2, 1, 50),
            'actual_duration_minutes' => fake()->numberBetween(5, 120),
            'actual_fare' => fake()->randomFloat(2, 50, 500),
        ]);
    }

    /**
     * Indicate that the ride is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'accepted_at' => now()->subHours(1),
            'arrived_at' => now()->subMinutes(30),
            'started_at' => now()->subMinutes(20),
        ]);
    }
}
