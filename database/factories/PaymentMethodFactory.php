<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentTypes = ['card', 'wallet', 'upi'];
        $paymentType = $this->faker->randomElement($paymentTypes);

        $details = match ($paymentType) {
            'card' => [
                'card_number' => '****' . $this->faker->numerify('####'),
                'expiry' => $this->faker->creditCardExpirationDateString(),
                'holder_name' => $this->faker->name(),
            ],
            'wallet' => [
                'wallet_id' => $this->faker->uuid(),
                'provider' => $this->faker->randomElement(['PayPal', 'GooglePay', 'ApplePay']),
            ],
            'upi' => [
                'upi_id' => $this->faker->email(),
                'provider' => $this->faker->randomElement(['GooglePay', 'PhonePe', 'Paytm']),
            ],
        };

        return [
            'user_id' => User::factory(),
            'payment_type' => $paymentType,
            'payment_details' => json_encode($details),
            'is_default' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the payment method is the default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the payment method is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
