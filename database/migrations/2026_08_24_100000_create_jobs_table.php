<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The `jobs` table backs the `database` queue driver.
 *
 * Production runs on Hostinger shared hosting, where there is no Redis and no
 * long-running worker process — so `QUEUE_CONNECTION=database` plus a cron job
 * is the only workable setup. Without this table every queued transactional
 * email fails with "Base table or view not found: 'jobs'", which means no
 * client ever receives a confirmation, drop-in or drop-out message.
 *
 * Local Docker still uses Redis; the table is harmless there.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
