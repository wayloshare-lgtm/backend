<?php

namespace App\Services;

use App\Models\FareSetting;
use Carbon\Carbon;

class FareCalculatorService
{
    private FareSetting $fareSetting;

    public function __construct(?string $city = null)
    {
        $this->fareSetting = FareSetting::getActive($city);

        if (!$this->fareSetting) {
            throw new \Exception('No active fare settings found');
        }
    }

    /**
     * Calculate complete fare breakdown
     *
     * @param float $distanceKm Distance in kilometers
     * @param int $durationMinutes Duration in minutes
     * @param float $tollAmount Toll amount (optional)
     * @param bool $isNightTime Whether it's night time (optional)
     * @return array Fare breakdown
     */
    public function calculate(
        float $distanceKm,
        int $durationMinutes,
        float $tollAmount = 0,
        bool $isNightTime = false
    ): array
    {
        // Base fare
        $baseFare = (float) $this->fareSetting->base_fare;

        // Distance charge
        $distanceCharge = $distanceKm * (float) $this->fareSetting->per_km_rate;

        // Time charge
        $timeCharge = $durationMinutes * (float) $this->fareSetting->per_minute_rate;

        // Fuel surcharge
        $fuelCharge = $distanceKm * (float) $this->fareSetting->fuel_surcharge_per_km;

        // Toll
        $toll = $this->fareSetting->toll_enabled ? $tollAmount : 0;

        // Subtotal before multipliers
        $subtotal = $baseFare + $distanceCharge + $timeCharge + $fuelCharge + $toll;

        // Apply night multiplier
        $nightMultiplier = $isNightTime ? (float) $this->fareSetting->night_multiplier : 1;
        $subtotalWithNight = $subtotal * $nightMultiplier;

        // Apply surge multiplier
        $surgeMultiplier = (float) $this->fareSetting->surge_multiplier;
        $subtotalWithSurge = $subtotalWithNight * $surgeMultiplier;

        // Platform fee (calculated on subtotal with multipliers)
        $platformFeePercentage = (float) $this->fareSetting->platform_fee_percentage;
        $platformFee = ($subtotalWithSurge * $platformFeePercentage) / 100;

        // Total fare
        $totalFare = $subtotalWithSurge + $platformFee;

        return [
            'base_fare' => round($baseFare, 2),
            'distance_charge' => round($distanceCharge, 2),
            'time_charge' => round($timeCharge, 2),
            'fuel_charge' => round($fuelCharge, 2),
            'toll' => round($toll, 2),
            'subtotal' => round($subtotal, 2),
            'night_multiplier' => $nightMultiplier,
            'surge_multiplier' => $surgeMultiplier,
            'subtotal_with_multipliers' => round($subtotalWithSurge, 2),
            'platform_fee' => round($platformFee, 2),
            'platform_fee_percentage' => $platformFeePercentage,
            'total_fare' => round($totalFare, 2),
            'currency' => 'INR',
        ];
    }

    /**
     * Get current fare settings
     */
    public function getSettings(): array
    {
        return [
            'id' => $this->fareSetting->id,
            'base_fare' => (float) $this->fareSetting->base_fare,
            'per_km_rate' => (float) $this->fareSetting->per_km_rate,
            'per_minute_rate' => (float) $this->fareSetting->per_minute_rate,
            'fuel_surcharge_per_km' => (float) $this->fareSetting->fuel_surcharge_per_km,
            'platform_fee_percentage' => (float) $this->fareSetting->platform_fee_percentage,
            'toll_enabled' => $this->fareSetting->toll_enabled,
            'night_multiplier' => (float) $this->fareSetting->night_multiplier,
            'surge_multiplier' => (float) $this->fareSetting->surge_multiplier,
            'city' => $this->fareSetting->city,
            'is_active' => $this->fareSetting->is_active,
        ];
    }
}
