<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Castrated; // Assuming you have a Castrated model

class CastratedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Castrated::create(['status' => 'yes']);
        Castrated::create(['status' => 'no']);
    }
}
