<?php

namespace Database\Factories;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FcmTokenFactory extends Factory
{
    protected $model = FcmToken::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'fcm_token' => $this->faker->unique()->sha256(),
            'device_type' => $this->faker->randomElement(['android', 'ios']),
            'device_id' => $this->faker->uuid(),
            'device_name' => $this->faker->word(),
            'is_active' => true,
        ];
    }

    public function android(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => 'android',
        ]);
    }

    public function ios(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => 'ios',
        ]);
    }
}
