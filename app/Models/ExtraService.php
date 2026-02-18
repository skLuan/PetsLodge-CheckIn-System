<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraService extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function checkIns()
    {
        return $this->belongsToMany(CheckIn::class, 'check_in_extra_service')
            ->withPivot('grooming_appointment_day')
            ->withTimestamps();
    }
}
