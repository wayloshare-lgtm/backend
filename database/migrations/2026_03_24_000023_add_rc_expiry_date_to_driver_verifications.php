<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_verifications', function (Blueprint $table) {
            $table->date('rc_expiry_date')->nullable()->after('rc_number');
        });
    }

    public function down(): void
    {
        Schema::table('driver_verifications', function (Blueprint $table) {
            $table->dropColumn('rc_expiry_date');
        });
    }
};
