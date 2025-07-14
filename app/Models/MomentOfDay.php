<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MomentOfDay extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function foods()
    {
        return $this->hasMany(Food::class);
    }

    public function medicines()
    {
        return $this->hasMany(Medicine::class);
    }
}
