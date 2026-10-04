<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = [
            ['name' => 'Admin', 'email' => 'admin@example.com'],
            ['name' => 'QA Tester', 'email' => 'qa@example.com'],
            ['name' => 'Dev Developer', 'email' => 'dev@example.com'],
            ['name' => 'Product Owner', 'email' => 'po@example.com'],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => 'password',
                ]
            );
        }
    }
}
