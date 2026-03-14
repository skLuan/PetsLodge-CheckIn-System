<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Status::create(['name' => 'CHECKED_IN']);
        Status::create(['name' => 'PRINTED']);
        Status::create(['name' => 'DROPPED_IN']);
        Status::create(['name' => 'CHECKED_OUT']);
        Status::create(['name' => 'CANCELLED']);
    }
}
