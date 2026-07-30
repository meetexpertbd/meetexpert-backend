<?php

namespace Database\Seeders;

use App\Enums\RegistrationFrom;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $sources = [
            RegistrationFrom::AdminPanel,
            RegistrationFrom::Web,
            RegistrationFrom::App,
        ];
        $password = Hash::make('password');

        for ($i = 1; $i <= 100; $i++) {
            $from = $sources[($i - 1) % count($sources)];

            User::query()->updateOrCreate(
                ['email' => "user{$i}@meetexpert.test"],
                [
                    'name' => $faker->name(),
                    'password' => $password,
                    'user_type' => User::USER_TYPE_USER,
                    'registration_from' => $from,
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command?->info('Seeded 100 users (user1@meetexpert.test … user100@meetexpert.test / password).');
        $this->command?->info('registration_from rotates: admin_panel, web, app.');
    }
}
