<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A single signing event: the PNG a client drew, plus who signed, for which
 * visit, and against which Terms & Conditions version.
 *
 * Append-only — see the migration for why re-signing inserts rather than updates.
 */
class Signature extends Model
{
    use HasFactory;

    /** Where signature points can be captured. Keep in sync with the store validation. */
    public const CONTEXTS = ['drop-in', 'check-in'];

    /** The private disk signatures live on. Never `public` — this is personal data. */
    public const DISK = 'local';

    protected $fillable = [
        'user_id',
        'check_in_id',
        'terms_and_conditions_id',
        'path',
        'context',
    ];

    /** The pet owner who signed. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkIn(): BelongsTo
    {
        return $this->belongsTo(CheckIn::class);
    }

    /** The exact T&C text the client agreed to. */
    public function termsAndConditions(): BelongsTo
    {
        return $this->belongsTo(TermsAndConditions::class, 'terms_and_conditions_id');
    }

    /** Signatures captured at drop-in (the only context wired up today). */
    public function scopeForDropIn(Builder $query): Builder
    {
        return $query->where('context', 'drop-in');
    }

    /**
     * The authenticated URL the image is served from.
     *
     * Built on read — the absolute URL is never stored (see the migration).
     */
    public function url(): string
    {
        return route('signatures.show', $this);
    }

    /** Raw PNG bytes, or null if the file has gone missing from the disk. */
    public function contents(): ?string
    {
        $disk = Storage::disk(self::DISK);

        return $disk->exists($this->path) ? $disk->get($this->path) : null;
    }

    /**
     * A `data:` URI of the image, for embedding in dompdf output.
     *
     * dompdf cannot fetch the authenticated `signatures.show` route, so the
     * bytes have to be inlined.
     */
    public function dataUri(): ?string
    {
        $contents = $this->contents();

        return $contents === null ? null : 'data:image/png;base64,'.base64_encode($contents);
    }
}
