<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create an admin user
        User::updateOrCreate(
            ['email' => 'admin@admin.com'], // Prevent duplicate admin creation
            [
                'name' => 'Admin User',
                'email' => 'admin@admin.com',
                'password' => Hash::make('secret123'), // Change this to a secure password
                'role' => 'admin',
            ]
        );

        $this->command->info('Admin user created successfully!');
    }
}
