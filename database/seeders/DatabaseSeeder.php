<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $testUserEmail = 'test@example.com';
        if (!User::where('email', $testUserEmail)->exists()) {
            User::factory()->create([
                'full_name' => 'Test User',
                'username' => $testUserEmail,
                'email' => $testUserEmail,
            ]);
        }
    }
}
