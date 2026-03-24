<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'display_name')) {
                $table->string('display_name', 255)->nullable();
            }
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable();
            }
            if (!Schema::hasColumn('users', 'gender')) {
                $table->enum('gender', ['male', 'female', 'other'])->nullable();
            }
            if (!Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable();
            }
            if (!Schema::hasColumn('users', 'profile_photo_url')) {
                $table->string('profile_photo_url', 255)->nullable();
            }
            if (!Schema::hasColumn('users', 'user_preference')) {
                $table->enum('user_preference', ['driver', 'passenger', 'both'])->default('passenger');
            }
            if (!Schema::hasColumn('users', 'onboarding_completed')) {
                $table->boolean('onboarding_completed')->default(false);
            }
            if (!Schema::hasColumn('users', 'profile_completed')) {
                $table->boolean('profile_completed')->default(false);
            }
            if (!Schema::hasColumn('users', 'profile_visibility')) {
                $table->enum('profile_visibility', ['public', 'private', 'friends_only'])->default('public');
            }
            if (!Schema::hasColumn('users', 'show_phone')) {
                $table->boolean('show_phone')->default(true);
            }
            if (!Schema::hasColumn('users', 'show_email')) {
                $table->boolean('show_email')->default(false);
            }
            if (!Schema::hasColumn('users', 'allow_messages')) {
                $table->boolean('allow_messages')->default(true);
            }
            if (!Schema::hasColumn('users', 'language')) {
                $table->enum('language', ['english', 'hindi', 'regional'])->default('english');
            }
            if (!Schema::hasColumn('users', 'theme')) {
                $table->enum('theme', ['light', 'dark', 'auto'])->default('auto');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'date_of_birth',
                'gender',
                'bio',
                'profile_photo_url',
                'user_preference',
                'onboarding_completed',
                'profile_completed',
                'profile_visibility',
                'show_phone',
                'show_email',
                'allow_messages',
                'language',
                'theme',
            ]);
        });
    }
};
