<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Operator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->delete();

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@codecartel.com',
            'password' => 'admin123', // will be hashed by cast
            'pin' => '1234',
            'is_admin' => true,
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Regular User',
            'email' => 'user@codecartel.com',
            'password' => 'user12345', // will be hashed by cast
            'pin' => '0000',
            'is_admin' => false,
            'is_active' => true,
        ]);

        Operator::query()->delete();

        Operator::create([
            'name' => 'Grameenphone',
            'short_code' => 'GP',
            'logo_text' => 'GP',
            'description' => 'Prepaid & Postpaid',
            'badge_text' => 'GP',
            'circle_bg_color' => '#0078C8',
            'sort_order' => 1,
        ]);

        Operator::create([
            'name' => 'Banglalink',
            'short_code' => 'BL',
            'logo_text' => 'BL',
            'description' => 'Prepaid & Postpaid',
            'badge_text' => 'Banglalink',
            'circle_bg_color' => '#E61E25',
            'sort_order' => 2,
        ]);

        Operator::create([
            'name' => 'Robi',
            'short_code' => 'R',
            'logo_text' => 'R',
            'description' => 'Prepaid & Postpaid',
            'badge_text' => 'Robi',
            'circle_bg_color' => '#E60000',
            'sort_order' => 3,
        ]);

        Operator::create([
            'name' => 'Airtel',
            'short_code' => 'A',
            'logo_text' => 'A',
            'description' => 'Prepaid & Postpaid',
            'badge_text' => 'Airtel',
            'circle_bg_color' => '#E60000',
            'sort_order' => 4,
        ]);

        Operator::create([
            'name' => 'Teletalk',
            'short_code' => 'T',
            'logo_text' => 'T',
            'description' => 'Prepaid & Postpaid',
            'badge_text' => 'Teletalk',
            'circle_bg_color' => '#0066B3',
            'sort_order' => 5,
        ]);

        Operator::create([
            'name' => 'Skitto',
            'short_code' => 'SK',
            'logo_text' => 'SK',
            'description' => 'Prepaid Only',
            'badge_text' => 'Skitto',
            'circle_bg_color' => '#FF6B00',
            'sort_order' => 6,
        ]);
    }
}
