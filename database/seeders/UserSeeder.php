<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the default application user.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('SEED_USER_EMAIL', 'admin@example.com')],
            [
                'name' => env('SEED_USER_NAME', 'Admin Zoom'),
                'password' => Hash::make(env('SEED_USER_PASSWORD', 'password')),
                'email_verified_at' => now(),
            ],
        );
    }
}
