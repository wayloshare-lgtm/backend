<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('dl_number', 255)->unique()->nullable();
            $table->date('dl_expiry_date')->nullable();
            $table->string('dl_front_image', 255)->nullable();
            $table->string('dl_back_image', 255)->nullable();
            $table->string('rc_number', 255)->unique()->nullable();
            $table->string('rc_front_image', 255)->nullable();
            $table->string('rc_back_image', 255)->nullable();
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('user_id');
            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_verifications');
    }
};
