<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add indexes to rides table
        Schema::table('rides', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });

        // Create bookings table if needed (for future use)
        if (!Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ride_id');
                $table->unsignedBigInteger('passenger_id');
                $table->integer('seats_booked')->default(1);
                $table->decimal('total_price', 10, 2);
                $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
                $table->timestamps();

                $table->foreign('ride_id')->references('id')->on('rides')->onDelete('cascade');
                $table->foreign('passenger_id')->references('id')->on('users')->onDelete('cascade');

                $table->index('ride_id');
                $table->index(['ride_id', 'status']);
            });
        }

        // Create messages table if needed (for future use)
        if (!Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('chat_id');
                $table->unsignedBigInteger('sender_id');
                $table->text('message');
                $table->timestamps();

                $table->index('chat_id');
                $table->index(['chat_id', 'created_at']);
            });
        }

        // Create fcm_tokens table if needed (for future use)
        if (!Schema::hasTable('fcm_tokens')) {
            Schema::create('fcm_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('token')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index('user_id');
                $table->index(['user_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });
    }
};
