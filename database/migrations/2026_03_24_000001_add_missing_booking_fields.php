<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('bookings', 'passenger_name')) {
                $table->string('passenger_name', 255)->nullable()->after('seats_booked');
            }
            if (!Schema::hasColumn('bookings', 'passenger_phone')) {
                $table->string('passenger_phone', 20)->nullable()->after('passenger_name');
            }
            if (!Schema::hasColumn('bookings', 'special_instructions')) {
                $table->text('special_instructions')->nullable()->after('passenger_phone');
            }
            if (!Schema::hasColumn('bookings', 'luggage_info')) {
                $table->text('luggage_info')->nullable()->after('special_instructions');
            }
            if (!Schema::hasColumn('bookings', 'accessibility_requirements')) {
                $table->text('accessibility_requirements')->nullable()->after('luggage_info');
            }
            if (!Schema::hasColumn('bookings', 'booking_status')) {
                $table->enum('booking_status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending')->after('accessibility_requirements');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'passenger_name',
                'passenger_phone',
                'special_instructions',
                'luggage_info',
                'accessibility_requirements',
                'booking_status',
            ]);
        });
    }
};
