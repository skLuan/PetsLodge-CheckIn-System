<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\KindOfPet; // Assuming you have a KindOfPet model

class KindOfPetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        KindOfPet::create(['name' => 'Dog']);
        KindOfPet::create(['name' => 'Cat']);
        KindOfPet::create(['name' => 'Bird']);
        KindOfPet::create(['name' => 'Mini pig']);
        KindOfPet::create(['name' => 'Reptile']);
    }
}
