<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds a composite index to the saved_routes table for improved query performance:
     * - (user_id, is_pinned): Used for efficiently filtering pinned routes by user
     * 
     * Note: Single indexes on user_id and is_pinned already exist from the initial
     * saved_routes table creation migration. This composite index complements those
     * single indexes for common query patterns.
     * 
     * Common query patterns optimized:
     * 1. Get all pinned routes for a user: SELECT * FROM saved_routes WHERE user_id = ? AND is_pinned = true
     * 2. Get all routes for a user: SELECT * FROM saved_routes WHERE user_id = ? ORDER BY is_pinned DESC
     * 3. Filter routes by user and pin status: SELECT * FROM saved_routes WHERE user_id = ? AND is_pinned = false
     */
    public function up(): void
    {
        Schema::table('saved_routes', function (Blueprint $table) {
            // Composite index on (user_id, is_pinned) for efficient filtering of pinned routes by user
            // This improves queries like: SELECT * FROM saved_routes WHERE user_id = ? AND is_pinned = true
            $table->index(['user_id', 'is_pinned']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saved_routes', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_pinned']);
        });
    }
};
