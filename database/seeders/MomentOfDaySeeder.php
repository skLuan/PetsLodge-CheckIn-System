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
        MomentOfDay::firstOrCreate(['name' => 'Morning']);
        MomentOfDay::firstOrCreate(['name' => 'Noon']);
        MomentOfDay::firstOrCreate(['name' => 'Evening']);
        MomentOfDay::firstOrCreate(['name' => 'Night']);
        MomentOfDay::firstOrCreate(['name' => 'Afternoon']);
    }
}
