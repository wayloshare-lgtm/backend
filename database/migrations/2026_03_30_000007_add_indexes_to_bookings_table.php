<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds indexes to the bookings table for improved query performance:
     * - ride_id: Used for lookups of bookings by ride
     * - passenger_id: Used for user-specific queries
     * - booking_status: Used for filtering bookings by status
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Add indexes if they don't already exist
            // These indexes improve performance for common queries:
            // 1. Finding bookings by ride (ride_id)
            // 2. Finding bookings by passenger (passenger_id)
            // 3. Filtering bookings by status (booking_status)
            
            // Check if indexes already exist before adding them
            $indexNames = collect(\DB::select("SHOW INDEXES FROM bookings"))
                ->pluck('Key_name')
                ->toArray();
            
            if (!in_array('bookings_ride_id_index', $indexNames)) {
                $table->index('ride_id');
            }
            
            if (!in_array('bookings_passenger_id_index', $indexNames)) {
                $table->index('passenger_id');
            }
            
            if (!in_array('bookings_booking_status_index', $indexNames)) {
                $table->index('booking_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['ride_id']);
            $table->dropIndex(['passenger_id']);
            $table->dropIndex(['booking_status']);
        });
    }
};
