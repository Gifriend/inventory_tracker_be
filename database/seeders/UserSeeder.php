<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Aslab',
            'email' => 'aslab@lab.com',
            'password' => Hash::make('password123'),
            'role' => 'aslab',
        ]);

        User::create([
            'name' => 'Mahasiswa User',
            'email' => 'user@student.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);
    }
}