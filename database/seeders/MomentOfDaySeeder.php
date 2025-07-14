<?php

namespace Database\Seeders;

use App\Models\MomentOfDay;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MomentOfDaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        MomentOfDay::create(['name' => 'Morning']);
        MomentOfDay::create(['name' => 'Noon']);
        MomentOfDay::create(['name' => 'Evening']);
        MomentOfDay::create(['name' => 'Night']);
    }
}
