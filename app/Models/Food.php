<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    use HasFactory;

    protected $table = 'foods';

    protected $fillable = ['name', 'description', 'pet_id', 'check_in_id', 'moment_of_day_id'];

    public function checkIn()
    {
        return $this->belongsTo(CheckIn::class);
    }

    public function momentOfDay()
    {
        return $this->belongsTo(MomentOfDay::class);
    }

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }
}
