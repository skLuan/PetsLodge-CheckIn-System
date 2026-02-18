<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'pet_id', 'check_in_id'];

    public function checkIn()
    {
        return $this->belongsTo(CheckIn::class);
    }

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }
}
