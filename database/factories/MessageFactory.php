<?php

namespace Database\Factories;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_id' => Chat::factory(),
            'sender_id' => User::factory(),
            'message' => fake()->sentence(),
            'message_type' => 'text',
            'attachment' => null,
            'metadata' => null,
            'is_read' => false,
            'read_at' => null,
        ];
    }

    /**
     * Indicate that the message is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Indicate that the message is an image.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => 'image',
            'attachment' => 'messages/' . fake()->uuid() . '.jpg',
        ]);
    }

    /**
     * Indicate that the message is a location.
     */
    public function location(): static
    {
        return $this->state(fn (array $attributes) => [
            'message_type' => 'location',
            'message' => null,
            'metadata' => json_encode([
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
            ]),
        ]);
    }
}
