<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'check_in_id', 'moment_of_day_id', 'pet_id'];

    public function checkIn()
    {
        return $this->belongsTo(CheckIn::class);
    }

    public function momentOfDay()
    {
        return $this->belongsTo(MomentOfDay::class);
    }
}
