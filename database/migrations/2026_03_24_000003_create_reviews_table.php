<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_id');
            $table->unsignedBigInteger('reviewer_id');
            $table->unsignedBigInteger('reviewee_id');
            $table->integer('rating')->nullable();
            $table->text('comment')->nullable();
            $table->json('categories')->nullable();
            $table->json('photos')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('ride_id')->references('id')->on('rides')->onDelete('cascade');
            $table->foreign('reviewer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewee_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('ride_id');
            $table->index('reviewer_id');
            $table->index('reviewee_id');
        });

        // Add CHECK constraint for rating field
        DB::statement('ALTER TABLE reviews ADD CONSTRAINT rating_check CHECK (rating >= 1 AND rating <= 5 OR rating IS NULL)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
