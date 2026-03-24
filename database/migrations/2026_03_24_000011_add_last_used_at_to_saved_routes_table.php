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
        Schema::table('saved_routes', function (Blueprint $table) {
            // Add last_used_at column if it doesn't exist
            if (!Schema::hasColumn('saved_routes', 'last_used_at')) {
                $table->timestamp('last_used_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saved_routes', function (Blueprint $table) {
            if (Schema::hasColumn('saved_routes', 'last_used_at')) {
                $table->dropColumn('last_used_at');
            }
        });
    }
};
