<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckIn extends Model
{
    use HasFactory;

    protected $fillable = ['check_in', 'check_out', 'pet_id', 'user_id', 'status_id', 'document_path', 'confirmation_sent_at'];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'confirmation_sent_at' => 'datetime',
    ];

    /** Relationships the printed summary and the transformer need. */
    public const SUMMARY_RELATIONS = [
        'pet.kindOfPet',
        'pet.gender',
        'pet.castrated',
        'user.emergencyContacts',
        'foods.momentOfDay',
        'medicines.momentOfDay',
        'items',
        'extraServices',
    ];

    /**
     * Statuses of a stay that is over. A row in one of these is history, never
     * part of the visit the client is looking at or editing.
     */
    public const CLOSED_STATUSES = ['CHECKED_OUT', 'CANCELLED'];

    /**
     * Rows still in progress. `CANCELLED` is excluded explicitly: cancelling
     * sets the status but leaves `check_out` null.
     */
    public function scopeOpen($query)
    {
        return $query->whereNull('check_out')
            ->whereHas('status', fn ($status) => $status->whereNotIn('name', self::CLOSED_STATUSES));
    }

    /**
     * The owner's current visit as the client sees it: one row per pet,
     * oldest first, so the first row's id is the booking's id.
     */
    public function scopeOpenBookingOf($query, int $userId)
    {
        return $query->where('user_id', $userId)->open()->orderBy('id');
    }

    /**
     * Every check-in row of the booking this one belongs to.
     *
     * The wizard writes one row per pet, so "one check-in with two pets" is two
     * rows. Drop-in signs and prints the booking as a whole, so it needs the
     * set, not the row: the owner's still-present pets, oldest first, with this
     * row's own id first in line as the anchor.
     *
     * Scoped to open rows — a pet already collected, or a cancelled one (cancel
     * leaves `check_out` null), is not part of the booking being dropped in.
     */
    public function bookingSiblings()
    {
        return static::query()
            ->where('user_id', $this->user_id)
            ->where(function ($query) {
                $query->where(fn ($open) => $open->open())->orWhere('id', $this->id);
            })
            ->orderBy('id');
    }

    /** Signing events for this row. Drop-in records one on every row of the booking. */
    public function signatures()
    {
        return $this->hasMany(Signature::class);
    }

    /**
     * A signed check-in is legal evidence of consent: only pet staff (or above)
     * may delete it. `signatures_exists` is used when loaded via withExists().
     */
    public function isSigned(): bool
    {
        return (bool) ($this->signatures_exists ?? $this->signatures()->exists());
    }

    /** Whether $user may delete this row (null = guest). */
    public function canBeDeletedBy(?User $user): bool
    {
        return ! $this->isSigned() || ($user?->isPetStaff() ?? false);
    }

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function extraServices()
    {
        return $this->belongsToMany(ExtraService::class, 'check_in_extra_service')
            ->withPivot('grooming_appointment_day')
            ->withTimestamps();
    }

    public function foods()
    {
        return $this->hasMany(Food::class);
    }

    public function medicines()
    {
        return $this->hasMany(Medicine::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
