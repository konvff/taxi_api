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
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('pickup_latitude', 10, 7)->nullable()->after('pickuplocation');
            $table->decimal('pickup_longitude', 10, 7)->nullable()->after('pickup_latitude');
            $table->decimal('dropoff_latitude', 10, 7)->nullable()->after('destination');
            $table->decimal('dropoff_longitude', 10, 7)->nullable()->after('dropoff_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['pickup_latitude', 'pickup_longitude', 'dropoff_latitude', 'dropoff_longitude']);
        });
    }
};
