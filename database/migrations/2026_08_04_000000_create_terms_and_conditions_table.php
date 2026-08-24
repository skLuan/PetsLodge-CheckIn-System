<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Versioned Terms & Conditions.
     *
     * Rows are append-only: publishing new text inserts a new version and flips the
     * old one inactive. Signatures (Plan 03) will point at the version the client
     * actually accepted, so old rows must never be mutated or deleted.
     */
    public function up(): void
    {
        Schema::create('terms_and_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('Terms & Conditions');
            $table->longText('content');                    // sanitized HTML
            $table->unsignedInteger('version');             // 1, 2, 3...
            $table->boolean('is_active')->default(false);   // only one row is active
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('version');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms_and_conditions');
    }
};
