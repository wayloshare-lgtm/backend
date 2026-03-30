<?php

namespace Database\Factories;

use App\Models\DriverVerification;
use App\Models\User;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverVerificationFactory extends Factory
{
    protected $model = DriverVerification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->driver(),
            'dl_number' => $this->faker->unique()->bothify('DL##??#####'),
            'dl_expiry_date' => $this->faker->dateTimeBetween('+1 year', '+10 years')->format('Y-m-d'),
            'dl_front_image' => 'path/to/dl_front.jpg',
            'dl_back_image' => 'path/to/dl_back.jpg',
            'rc_number' => $this->faker->unique()->bothify('RC##??#####'),
            'rc_expiry_date' => $this->faker->dateTimeBetween('+1 year', '+10 years')->format('Y-m-d'),
            'rc_front_image' => 'path/to/rc_front.jpg',
            'rc_back_image' => 'path/to/rc_back.jpg',
            'verification_status' => VerificationStatus::PENDING,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::APPROVED,
            'verified_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::REJECTED,
            'rejection_reason' => 'Document expired',
        ]);
    }
}
