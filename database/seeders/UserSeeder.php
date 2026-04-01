<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin account
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@petslodge.com',
            'password' => Hash::make('password123'),
            'phone' => '0000000000',
            'address' => 'Admin Address',
            'role' => 'SUPER_ADMIN',
        ]);

        // Create Pet Staff account
        User::create([
            'name' => 'Pet Staff',
            'email' => 'staff@petslodge.com',
            'password' => Hash::make('password123'),
            'phone' => '1111111111',
            'address' => 'Staff Address',
            'role' => 'PET_STAFF',
        ]);
    }
}
