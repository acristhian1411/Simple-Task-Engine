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
            ['name' => 'Admin', 'email' => 'admin@example.com', 'is_admin' => true],
            ['name' => 'QA Tester', 'email' => 'qa@example.com', 'is_admin' => false],
            ['name' => 'Dev Developer', 'email' => 'dev@example.com', 'is_admin' => false],
            ['name' => 'Product Owner', 'email' => 'po@example.com', 'is_admin' => false],
        ];

        foreach ($users as $user) {
            $record = User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => 'password',
                ]
            );
            $record->is_admin = $user['is_admin'];
            $record->save();
        }
    }
}
