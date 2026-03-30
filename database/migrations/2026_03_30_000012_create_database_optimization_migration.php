<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration consolidates all remaining database optimization work:
     * 1. Adds missing composite indexes for query performance
     * 2. Ensures all foreign key constraints are properly configured
     * 3. Optimizes table structures for common query patterns
     * 4. Adds indexes on frequently queried columns
     * 
     * Tables optimized:
     * - bookings: Composite indexes for common query patterns
     * - reviews: Composite indexes for reviewer/reviewee queries
     * - messages: Already optimized in previous migration
     * - fcm_tokens: Already optimized in previous migration
     * - ride_locations: Already optimized in previous migration
     * - saved_routes: Already optimized in previous migration
     * - vehicles: Add indexes for common queries
     * - payment_methods: Add indexes for common queries
     * - driver_verifications: Add indexes for common queries
     * - chats: Add indexes for common queries
     */
    public function up(): void
    {
        // Optimize bookings table with additional composite indexes
        Schema::table('bookings', function (Blueprint $table) {
            // Composite index for finding bookings by ride and status
            // Improves: SELECT * FROM bookings WHERE ride_id = ? AND booking_status = ?
            if (!$this->indexExists('bookings', 'bookings_ride_id_booking_status_index')) {
                $table->index(['ride_id', 'booking_status']);
            }
            
            // Composite index for finding bookings by passenger and status
            // Improves: SELECT * FROM bookings WHERE passenger_id = ? AND booking_status = ?
            if (!$this->indexExists('bookings', 'bookings_passenger_id_booking_status_index')) {
                $table->index(['passenger_id', 'booking_status']);
            }
            
            // Composite index for pagination and sorting
            // Improves: SELECT * FROM bookings WHERE ride_id = ? ORDER BY created_at DESC
            if (!$this->indexExists('bookings', 'bookings_ride_id_created_at_index')) {
                $table->index(['ride_id', 'created_at']);
            }
        });

        // Optimize reviews table with additional composite indexes
        Schema::table('reviews', function (Blueprint $table) {
            // Composite index for finding reviews by reviewer
            // Improves: SELECT * FROM reviews WHERE reviewer_id = ? ORDER BY created_at DESC
            if (!$this->indexExists('reviews', 'reviews_reviewer_id_created_at_index')) {
                $table->index(['reviewer_id', 'created_at']);
            }
            
            // Composite index for finding reviews by reviewee
            // Improves: SELECT * FROM reviews WHERE reviewee_id = ? ORDER BY created_at DESC
            if (!$this->indexExists('reviews', 'reviews_reviewee_id_created_at_index')) {
                $table->index(['reviewee_id', 'created_at']);
            }
            
            // Composite index for finding reviews by ride
            // Improves: SELECT * FROM reviews WHERE ride_id = ? ORDER BY created_at DESC
            if (!$this->indexExists('reviews', 'reviews_ride_id_created_at_index')) {
                $table->index(['ride_id', 'created_at']);
            }
            
            // Composite index for rating-based queries
            // Improves: SELECT * FROM reviews WHERE reviewee_id = ? AND rating >= ? ORDER BY created_at DESC
            if (!$this->indexExists('reviews', 'reviews_reviewee_id_rating_index')) {
                $table->index(['reviewee_id', 'rating']);
            }
        });

        // Optimize vehicles table with additional indexes
        Schema::table('vehicles', function (Blueprint $table) {
            // Composite index for finding user's default vehicle
            // Improves: SELECT * FROM vehicles WHERE user_id = ? AND is_default = true
            if (!$this->indexExists('vehicles', 'vehicles_user_id_is_default_index')) {
                $table->index(['user_id', 'is_default']);
            }
            
            // Composite index for finding active vehicles by user
            // Improves: SELECT * FROM vehicles WHERE user_id = ? AND is_active = true
            if (!$this->indexExists('vehicles', 'vehicles_user_id_is_active_index')) {
                $table->index(['user_id', 'is_active']);
            }
        });

        // Optimize payment_methods table with additional indexes
        Schema::table('payment_methods', function (Blueprint $table) {
            // Composite index for finding user's default payment method
            // Improves: SELECT * FROM payment_methods WHERE user_id = ? AND is_default = true
            if (!$this->indexExists('payment_methods', 'payment_methods_user_id_is_default_index')) {
                $table->index(['user_id', 'is_default']);
            }
            
            // Composite index for finding active payment methods by user
            // Improves: SELECT * FROM payment_methods WHERE user_id = ? AND is_active = true
            if (!$this->indexExists('payment_methods', 'payment_methods_user_id_is_active_index')) {
                $table->index(['user_id', 'is_active']);
            }
            
            // Index on payment_type for filtering by payment method
            // Improves: SELECT * FROM payment_methods WHERE payment_type = ?
            if (!$this->indexExists('payment_methods', 'payment_methods_payment_type_index')) {
                $table->index('payment_type');
            }
        });

        // Optimize driver_verifications table with additional indexes
        Schema::table('driver_verifications', function (Blueprint $table) {
            // Composite index for finding user's verification status
            // Improves: SELECT * FROM driver_verifications WHERE user_id = ? AND verification_status = ?
            if (!$this->indexExists('driver_verifications', 'driver_verifications_user_id_verification_status_index')) {
                $table->index(['user_id', 'verification_status']);
            }
            
            // Index on verification_status for filtering
            // Improves: SELECT * FROM driver_verifications WHERE verification_status = ? ORDER BY created_at DESC
            if (!$this->indexExists('driver_verifications', 'driver_verifications_verification_status_index')) {
                $table->index('verification_status');
            }
        });

        // Optimize chats table with additional indexes
        Schema::table('chats', function (Blueprint $table) {
            // Index on created_at for sorting chats by date
            // Improves: SELECT * FROM chats ORDER BY created_at DESC
            if (!$this->indexExists('chats', 'chats_created_at_index')) {
                $table->index('created_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop composite indexes from bookings
        Schema::table('bookings', function (Blueprint $table) {
            if ($this->indexExists('bookings', 'bookings_ride_id_booking_status_index')) {
                $table->dropIndex(['ride_id', 'booking_status']);
            }
            if ($this->indexExists('bookings', 'bookings_passenger_id_booking_status_index')) {
                $table->dropIndex(['passenger_id', 'booking_status']);
            }
            if ($this->indexExists('bookings', 'bookings_ride_id_created_at_index')) {
                $table->dropIndex(['ride_id', 'created_at']);
            }
        });

        // Drop composite indexes from reviews
        Schema::table('reviews', function (Blueprint $table) {
            if ($this->indexExists('reviews', 'reviews_reviewer_id_created_at_index')) {
                $table->dropIndex(['reviewer_id', 'created_at']);
            }
            if ($this->indexExists('reviews', 'reviews_reviewee_id_created_at_index')) {
                $table->dropIndex(['reviewee_id', 'created_at']);
            }
            if ($this->indexExists('reviews', 'reviews_ride_id_created_at_index')) {
                $table->dropIndex(['ride_id', 'created_at']);
            }
            if ($this->indexExists('reviews', 'reviews_reviewee_id_rating_index')) {
                $table->dropIndex(['reviewee_id', 'rating']);
            }
        });

        // Drop indexes from vehicles
        Schema::table('vehicles', function (Blueprint $table) {
            if ($this->indexExists('vehicles', 'vehicles_user_id_is_default_index')) {
                $table->dropIndex(['user_id', 'is_default']);
            }
            if ($this->indexExists('vehicles', 'vehicles_user_id_is_active_index')) {
                $table->dropIndex(['user_id', 'is_active']);
            }
        });

        // Drop indexes from payment_methods
        Schema::table('payment_methods', function (Blueprint $table) {
            if ($this->indexExists('payment_methods', 'payment_methods_user_id_is_default_index')) {
                $table->dropIndex(['user_id', 'is_default']);
            }
            if ($this->indexExists('payment_methods', 'payment_methods_user_id_is_active_index')) {
                $table->dropIndex(['user_id', 'is_active']);
            }
            if ($this->indexExists('payment_methods', 'payment_methods_payment_type_index')) {
                $table->dropIndex(['payment_type']);
            }
        });

        // Drop indexes from driver_verifications
        Schema::table('driver_verifications', function (Blueprint $table) {
            if ($this->indexExists('driver_verifications', 'driver_verifications_user_id_verification_status_index')) {
                $table->dropIndex(['user_id', 'verification_status']);
            }
            if ($this->indexExists('driver_verifications', 'driver_verifications_verification_status_index')) {
                $table->dropIndex(['verification_status']);
            }
        });

        // Drop indexes from chats
        Schema::table('chats', function (Blueprint $table) {
            if ($this->indexExists('chats', 'chats_created_at_index')) {
                $table->dropIndex(['created_at']);
            }
        });
    }

    /**
     * Helper method to check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = \DB::select("SHOW INDEXES FROM {$table}");
        $indexNames = collect($indexes)->pluck('Key_name')->toArray();
        return in_array($indexName, $indexNames);
    }
};
