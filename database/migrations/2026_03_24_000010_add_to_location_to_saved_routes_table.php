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
            // Add to_location column if it doesn't exist
            if (!Schema::hasColumn('saved_routes', 'to_location')) {
                $table->string('to_location', 255)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saved_routes', function (Blueprint $table) {
            if (Schema::hasColumn('saved_routes', 'to_location')) {
                $table->dropColumn('to_location');
            }
        });
    }
};
