<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Client signatures — one row per signing event.
     *
     * Deliberately NOT a `signature_url` column on `users` (which is what the
     * Notion card asked for): a signature is per-visit consent tied to the exact
     * Terms & Conditions version that was on screen, so a single user column
     * would be overwritten every visit and destroy the legal audit trail.
     *
     * Rows are append-only. Re-signing inserts a new row; the newest one for a
     * given check-in is the effective signature.
     *
     * `path` is a RELATIVE path on the private `local` disk
     * (storage/app/signatures/...). URLs are built on read via
     * `route('signatures.show', $signature)` — never persisted, so the app can
     * move domains or disks without rewriting the table.
     */
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();

            // The pet OWNER who signed — not the staff member holding the tablet.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Nullable so a future signature point can exist before a check-in row does.
            $table->foreignId('check_in_id')->nullable()->constrained()->cascadeOnDelete();

            // The T&C version actually shown. Never cascade-delete: if a terms row
            // ever disappears the signature must survive as evidence.
            $table->foreignId('terms_and_conditions_id')->nullable()
                ->constrained('terms_and_conditions')->nullOnDelete();

            $table->string('path');
            $table->string('context')->default('drop-in'); // 'drop-in' | 'check-in'
            $table->timestamps();

            // The drop-in gate asks "is there a signature for this check-in?" on
            // every print, and the summary/PDF wants the newest one.
            $table->index(['check_in_id', 'context']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
