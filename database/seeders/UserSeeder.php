<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'pin' => Hash::make('1234'),
            'is_admin' => true,
            'is_first_admin' => true,
            'is_active' => true,
            'level' => null,
        ]);

        User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'pin' => Hash::make('1234'),
            'is_admin' => false,
            'is_active' => true,
            'level' => 'retailer',
        ]);
    }
}
