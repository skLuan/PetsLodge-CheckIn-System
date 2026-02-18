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
        Schema::table('check_in_extra_service', function (Blueprint $table) {
            $table->string('grooming_appointment_day')->nullable()->after('extra_service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('check_in_extra_service', function (Blueprint $table) {
            $table->dropColumn('grooming_appointment_day');
        });
    }
};
