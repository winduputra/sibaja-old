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
        // Admin
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin',
                'password' => Hash::make('123'),
                'role_id' => 1
            ]
        );

        // User
        User::firstOrCreate(
            ['username' => 'user'],
            [
                'name' => 'User',
                'password' => Hash::make('1234'),
                'role_id' => 2
            ]
        );
    }
}
