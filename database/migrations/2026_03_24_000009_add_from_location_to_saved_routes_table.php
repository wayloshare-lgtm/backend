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
            // Add from_location column if it doesn't exist
            if (!Schema::hasColumn('saved_routes', 'from_location')) {
                $table->string('from_location', 255)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saved_routes', function (Blueprint $table) {
            if (Schema::hasColumn('saved_routes', 'from_location')) {
                $table->dropColumn('from_location');
            }
        });
    }
};
