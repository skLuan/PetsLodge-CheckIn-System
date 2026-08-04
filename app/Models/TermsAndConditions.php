<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * A single published version of the Terms & Conditions.
 *
 * Only one row is active at a time; the rest are kept as an audit trail so a
 * signed agreement can always be traced back to the exact text that was shown.
 */
class TermsAndConditions extends Model
{
    use HasFactory;

    protected $table = 'terms_and_conditions';

    protected $fillable = [
        'title',
        'content',
        'version',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'version' => 'integer',
    ];

    /** The staff member who published this version (nullable for the seeded v1). */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * The version currently shown to clients.
     *
     * Usage: `TermsAndConditions::active()->first()`
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->latest('version');
    }

    /**
     * Publish new text as version N+1 and retire the current version.
     *
     * Old rows are never mutated beyond flipping `is_active` off — that is the
     * audit trail signatures depend on.
     *
     * @param  string  $content  Already-sanitized HTML.
     * @param  User|null  $user  Author, stored on the new row.
     * @param  string|null  $title  Defaults to the current active title.
     */
    public static function publishNewVersion(string $content, ?User $user = null, ?string $title = null): self
    {
        return DB::transaction(function () use ($content, $user, $title) {
            // Lock the table rows we are about to renumber so two staff members
            // saving at the same time can't both claim the same version number.
            $current = static::query()->lockForUpdate()->orderByDesc('version')->first();

            static::query()->where('is_active', true)->update(['is_active' => false]);

            return static::create([
                'title' => $title ?? $current?->title ?? 'Terms & Conditions',
                'content' => $content,
                'version' => ($current->version ?? 0) + 1,
                'is_active' => true,
                'updated_by' => $user?->id,
            ]);
        });
    }
}
