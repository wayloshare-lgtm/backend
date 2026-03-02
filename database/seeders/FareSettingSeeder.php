<?php

namespace Database\Seeders;

use App\Models\FareSetting;
use Illuminate\Database\Seeder;

class FareSettingSeeder extends Seeder
{
    public function run(): void
    {
        FareSetting::create([
            'base_fare' => 50,
            'per_km_rate' => 15,
            'per_minute_rate' => 2,
            'fuel_surcharge_per_km' => 1.5,
            'platform_fee_percentage' => 10,
            'toll_enabled' => true,
            'night_multiplier' => 1.5,
            'surge_multiplier' => 1.2,
            'city' => null,
            'is_active' => true,
        ]);
    }
}
