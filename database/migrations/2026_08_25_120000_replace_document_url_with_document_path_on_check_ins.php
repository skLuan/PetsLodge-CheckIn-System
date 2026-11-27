<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace `document_url` with `document_path` (Plan 07).
     *
     * `document_url` held an ABSOLUTE url built from APP_URL, e.g.
     * `http://localhost:8080/storage/pdfs/drop-in-1787641808.pdf`, which
     * `reprint` fed straight back to PrintNode — so every stored value broke the
     * moment the app changed domain. Printing now uploads the PDF bytes
     * (`contentType: pdf_base64`), so no URL is needed at all and the file lives
     * on the PRIVATE disk.
     *
     * ⚠️ DESTRUCTIVE, and deliberately so: the app is not live (confirmed
     * 2026-08-25), so the handful of dev values are dropped rather than
     * backfilled. `down()` restores the COLUMN, but the VALUES are gone for
     * good — this migration is structurally reversible, not data-reversible.
     */
    public function up(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            $table->string('document_path')->nullable()->after('status_id');
        });

        Schema::table('check_ins', function (Blueprint $table) {
            $table->dropColumn('document_url');
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            $table->string('document_url')->nullable()->after('status_id');
        });

        Schema::table('check_ins', function (Blueprint $table) {
            $table->dropColumn('document_path');
        });
    }
};
