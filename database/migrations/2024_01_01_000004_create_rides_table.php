<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rider_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('pickup_location');
            $table->decimal('pickup_lat', 10, 7);
            $table->decimal('pickup_lng', 10, 7);
            $table->string('dropoff_location');
            $table->decimal('dropoff_lat', 10, 7);
            $table->decimal('dropoff_lng', 10, 7);
            $table->decimal('estimated_distance_km', 10, 2)->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            $table->decimal('estimated_fare', 10, 2)->nullable();
            $table->decimal('actual_distance_km', 10, 2)->nullable();
            $table->integer('actual_duration_minutes')->nullable();
            $table->decimal('actual_fare', 10, 2)->nullable();
            $table->decimal('toll_amount', 10, 2)->default(0);
            $table->enum('status', [
                'requested',
                'accepted',
                'arrived',
                'started',
                'completed',
                'cancelled'
            ])->default('requested');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('rider_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('driver_id')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('rider_id');
            $table->index('driver_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rides');
    }
};
