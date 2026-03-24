<?php

namespace Database\Factories;

use App\Models\Ride;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RideLocation>
 */
class RideLocationFactory extends Factory
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
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'accuracy' => fake()->randomFloat(2, 1, 50),
            'speed' => fake()->randomFloat(2, 0, 100),
            'heading' => fake()->randomFloat(2, 0, 360),
            'altitude' => fake()->randomFloat(2, 0, 1000),
            'timestamp' => now(),
        ];
    }
}
