<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            // Change the enum to include 'offered' status
            $table->enum('status', [
                'requested',
                'offered',
                'accepted',
                'arrived',
                'started',
                'completed',
                'cancelled'
            ])->default('requested')->change();
        });
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->enum('status', [
                'requested',
                'accepted',
                'arrived',
                'started',
                'completed',
                'cancelled'
            ])->default('requested')->change();
        });
    }
};
