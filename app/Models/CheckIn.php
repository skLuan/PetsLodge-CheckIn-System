<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckIn extends Model
{
    use HasFactory;

    protected $fillable = ['check_in', 'check_out', 'pet_id', 'user_id'];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
