<?php

namespace Database\Seeders;

use App\Models\User;
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
        // Admin user
        User::firstOrCreate(
            ['email' => 'admin@company.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        // Normal user
        User::firstOrCreate(
            ['email' => 'user@company.com'],
            [
                'name' => 'User',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );

        $this->call(OcrFieldMappingSeeder::class);
    }
}
