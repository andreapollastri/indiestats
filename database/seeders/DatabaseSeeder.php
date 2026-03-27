<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@users.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => UserRole::Admin,
        ]);

        $this->call(FakeDataSeeder::class);
    }
}
