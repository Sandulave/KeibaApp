<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => '管理者',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'audience_role' => 'streamer',
            ]
        );

        User::updateOrCreate(
            ['name' => 'user'],
            [
                'display_name' => 'ユーザー',
                'password' => Hash::make('password'),
                'role' => 'user',
                'audience_role' => 'viewer',
            ]
        );
    }
}
