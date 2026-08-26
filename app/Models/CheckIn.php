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
     * Every check-in row of the booking this one belongs to.
     *
     * The wizard writes one row per pet, so "one check-in with two pets" is two
     * rows. Drop-in signs and prints the booking as a whole, so it needs the
     * set, not the row: the owner's still-present pets, oldest first, with this
     * row's own id first in line as the anchor.
     *
     * Scoped to `check_out IS NULL` — a pet already collected is not part of the
     * booking being dropped in.
     */
    public function bookingSiblings()
    {
        return static::query()
            ->where('user_id', $this->user_id)
            ->where(function ($query) {
                $query->whereNull('check_out')->orWhere('id', $this->id);
            })
            ->orderBy('id');
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
