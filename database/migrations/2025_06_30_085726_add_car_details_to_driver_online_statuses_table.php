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
        Schema::table('driver_online_statuses', function (Blueprint $table) {
            $table->string('car_details')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('driver_online_statuses', function (Blueprint $table) {
            $table->dropColumn('car_details');
        });
    }
};
