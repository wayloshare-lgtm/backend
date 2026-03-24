<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('vehicle_name')->nullable();
            $table->enum('vehicle_type', ['sedan', 'suv', 'hatchback', 'muv', 'compact_suv'])->nullable();
            $table->string('license_plate')->unique();
            $table->string('vehicle_color')->nullable();
            $table->integer('vehicle_year')->nullable();
            $table->integer('seating_capacity')->nullable();
            $table->string('vehicle_photo')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('user_id');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
