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
        Schema::table('messages', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('messages', 'message')) {
                $table->text('message')->nullable()->after('sender_id');
            }
            if (!Schema::hasColumn('messages', 'message_type')) {
                $table->enum('message_type', ['text', 'image', 'location'])->default('text')->after('message');
            }
            if (!Schema::hasColumn('messages', 'attachment')) {
                $table->string('attachment', 255)->nullable()->after('message_type');
            }
            if (!Schema::hasColumn('messages', 'metadata')) {
                $table->json('metadata')->nullable()->after('attachment');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['message', 'message_type', 'attachment', 'metadata']);
        });
    }
};
