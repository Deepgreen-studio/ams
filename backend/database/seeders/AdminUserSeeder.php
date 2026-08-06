<?php

namespace Database\Seeders;

use App\Domains\Users\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'admin@ams.test'],
            [
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'full_name' => 'System Administrator',
                'name' => 'System Administrator',
                'password' => Hash::make('Password@123'),
                'email_verified_at' => now(),
                'status' => UserStatus::Active,
                'is_active' => true,
                'timezone' => 'UTC',
                'language' => 'en',
            ]
        );

        $user->assignRole('super-admin');
    }
}
