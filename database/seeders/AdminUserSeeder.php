<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@meetexpertbd.com'],
            [
                'name' => 'admin',
                'password' => '12345678',
                'user_type' => User::USER_TYPE_ADMIN,
                'email_verified_at' => now(),
            ]
        );
    }
}
