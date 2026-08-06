<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stamps the moment the owner was emailed their check-in confirmation.
     *
     * A submission with several pets creates one check-in row per pet, so this
     * column is what lets the queued listener coalesce the whole batch into a
     * single email: rows still NULL are "not announced yet".
     */
    public function up(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            $table->timestamp('confirmation_sent_at')->nullable()->after('document_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            $table->dropColumn('confirmation_sent_at');
        });
    }
};
