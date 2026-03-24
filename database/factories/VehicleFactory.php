<?php

namespace Database\Factories;

use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vehicle_name' => $this->faker->word(),
            'vehicle_type' => $this->faker->randomElement(['sedan', 'suv', 'hatchback', 'muv', 'compact_suv']),
            'license_plate' => strtoupper($this->faker->bothify('??##??####')),
            'vehicle_color' => $this->faker->colorName(),
            'vehicle_year' => $this->faker->year(),
            'seating_capacity' => $this->faker->numberBetween(1, 8),
            'vehicle_photo' => null,
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
