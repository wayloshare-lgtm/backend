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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_id');
            $table->unsignedBigInteger('passenger_id');
            $table->integer('seats_booked');
            $table->string('passenger_name', 255);
            $table->string('passenger_phone', 20);
            $table->text('special_instructions')->nullable();
            $table->text('luggage_info')->nullable();
            $table->text('accessibility_requirements')->nullable();
            $table->enum('booking_status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('ride_id')->references('id')->on('rides')->onDelete('cascade');
            $table->foreign('passenger_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('ride_id');
            $table->index('passenger_id');
            $table->index('booking_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
