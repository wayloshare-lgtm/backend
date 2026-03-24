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
        Schema::create('ride_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_id');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 10, 2)->nullable();
            $table->decimal('speed', 10, 2)->nullable();
            $table->decimal('heading', 10, 2)->nullable();
            $table->decimal('altitude', 10, 2)->nullable();
            $table->timestamp('timestamp');
            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreign('ride_id')->references('id')->on('rides')->onDelete('cascade');

            // Indexes
            $table->index('ride_id');
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ride_locations');
    }
};
