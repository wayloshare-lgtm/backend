<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DriverProfile>
 */
class DriverProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->driver(),
            'license_number' => fake()->unique()->bothify('DL-########'),
            'vehicle_type' => fake()->randomElement(['sedan', 'suv', 'hatchback', 'muv', 'compact_suv']),
            'vehicle_number' => fake()->unique()->bothify('KA-##-??-####'),
            'is_approved' => fake()->boolean(),
            'is_online' => fake()->boolean(),
            'current_lat' => fake()->latitude(),
            'current_lng' => fake()->longitude(),
            'languages_spoken' => json_encode(['English', 'Hindi']),
            'emergency_contact' => fake()->numerify('##########'),
            'insurance_provider' => fake()->company(),
            'insurance_policy_number' => fake()->bothify('POL-########'),
        ];
    }
}
