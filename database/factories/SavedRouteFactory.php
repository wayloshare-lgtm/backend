<?php

namespace Database\Factories;

use App\Models\SavedRoute;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavedRouteFactory extends Factory
{
    protected $model = SavedRoute::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'from_location' => $this->faker->address(),
            'to_location' => $this->faker->address(),
            'is_pinned' => false,
            'last_used_at' => null,
        ];
    }
}
