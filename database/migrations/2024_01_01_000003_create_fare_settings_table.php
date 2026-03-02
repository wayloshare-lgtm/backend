<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fare_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('base_fare', 10, 2);
            $table->decimal('per_km_rate', 10, 2);
            $table->decimal('per_minute_rate', 10, 2);
            $table->decimal('fuel_surcharge_per_km', 10, 2);
            $table->decimal('platform_fee_percentage', 5, 2);
            $table->boolean('toll_enabled')->default(true);
            $table->decimal('night_multiplier', 5, 2)->default(1);
            $table->decimal('surge_multiplier', 5, 2)->default(1);
            $table->string('city')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('city');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fare_settings');
    }
};
