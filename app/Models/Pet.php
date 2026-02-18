<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    use HasFactory;
    protected $fillable = [
        'birth_date',
        'race',
        'color',
        'health_conditions',
        'warnings',
        'gender_id',
        'kind_of_pet_id',
        'castrated_id',
        'user_id',
        'name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    public function kindOfPet()
    {
        return $this->belongsTo(KindOfPet::class);
    }

    public function castrated()
    {
        return $this->belongsTo(Castrated::class);
    }

    public function checkIn()
    {
        return $this->hasOne(CheckIn::class);
    }

    public function emergencyContacts()
    {
        return $this->belongsToMany(EmergencyContact::class, 'emergency_contact_pet');
    }
}
