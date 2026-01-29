<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@knowledge-hub.test',
                'role' => User::ROLE_ADMIN,
            ],
            [
                'name' => 'Moderator User',
                'email' => 'moderator@knowledge-hub.test',
                'role' => User::ROLE_MODERATOR,
            ],
            [
                'name' => 'Član User',
                'email' => 'member@knowledge-hub.test',
                'role' => User::ROLE_MEMBER,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
