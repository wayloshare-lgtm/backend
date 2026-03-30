<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds composite indexes to the fcm_tokens table for improved query performance:
     * - (user_id, created_at): Used for finding recent FCM tokens for a user
     * - (is_active, created_at): Used for finding active tokens by creation date
     * 
     * Note: Single indexes on user_id, fcm_token (unique), and is_active already exist
     * from the initial fcm_tokens table creation migration.
     */
    public function up(): void
    {
        Schema::table('fcm_tokens', function (Blueprint $table) {
            // Composite index on (user_id, created_at) for efficient sorting of user's tokens
            // This improves queries like: SELECT * FROM fcm_tokens WHERE user_id = ? ORDER BY created_at DESC
            $table->index(['user_id', 'created_at']);
            
            // Composite index on (is_active, created_at) for finding active tokens by creation date
            // This improves queries like: SELECT * FROM fcm_tokens WHERE is_active = true ORDER BY created_at DESC
            $table->index(['is_active', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fcm_tokens', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['is_active', 'created_at']);
        });
    }
};
