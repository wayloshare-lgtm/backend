<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds composite indexes to the ride_locations table for improved query performance:
     * - (ride_id, timestamp): Used for efficient retrieval of location history for a ride
     * - (ride_id, created_at): Used for pagination and sorting of location records
     * 
     * Note: Single indexes on ride_id and timestamp already exist from the initial
     * ride_locations table creation migration. These composite indexes complement
     * those single indexes for common query patterns.
     * 
     * Common query patterns optimized:
     * 1. Get location history for a ride: SELECT * FROM ride_locations WHERE ride_id = ? ORDER BY timestamp DESC
     * 2. Get recent locations for a ride: SELECT * FROM ride_locations WHERE ride_id = ? AND timestamp > ? ORDER BY timestamp DESC
     * 3. Paginate location history: SELECT * FROM ride_locations WHERE ride_id = ? ORDER BY created_at DESC LIMIT 50
     */
    public function up(): void
    {
        Schema::table('ride_locations', function (Blueprint $table) {
            // Composite index on (ride_id, timestamp) for efficient location history queries
            // This improves queries like: SELECT * FROM ride_locations WHERE ride_id = ? ORDER BY timestamp DESC
            $table->index(['ride_id', 'timestamp']);
            
            // Composite index on (ride_id, created_at) for pagination and sorting
            // This improves queries like: SELECT * FROM ride_locations WHERE ride_id = ? ORDER BY created_at DESC LIMIT 50
            $table->index(['ride_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ride_locations', function (Blueprint $table) {
            $table->dropIndex(['ride_id', 'timestamp']);
            $table->dropIndex(['ride_id', 'created_at']);
        });
    }
};
