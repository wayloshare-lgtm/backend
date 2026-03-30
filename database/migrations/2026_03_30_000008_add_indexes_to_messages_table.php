<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds composite indexes to the messages table for improved query performance:
     * - (chat_id, created_at): Used for efficient sorting within chats
     * - (is_read, created_at): Used for finding recent unread messages
     * 
     * Note: Single indexes on chat_id, sender_id, and is_read already exist
     * from the initial messages table creation migration.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Composite index on (chat_id, created_at) for efficient sorting within chats
            // This improves queries like: SELECT * FROM messages WHERE chat_id = ? ORDER BY created_at DESC
            $table->index(['chat_id', 'created_at']);
            
            // Composite index on (is_read, created_at) for finding recent unread messages
            // This improves queries like: SELECT * FROM messages WHERE is_read = false ORDER BY created_at DESC
            $table->index(['is_read', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['chat_id', 'created_at']);
            $table->dropIndex(['is_read', 'created_at']);
        });
    }
};
