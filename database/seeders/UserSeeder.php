<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // admin
        $admin = User::create([
            'name' => 'Admin Trah',
            'email' => 'admin@family.test',
            'password' => Hash::make('password123'),
        ]);
        $admin->assignRole('admin');

        // moderator
        $moderator = User::create([
            'name' => 'Moderator Cabang',
            'email' => 'moderator@family.test',
            'password' => Hash::make('password123'),
        ]);
        $moderator->assignRole('moderator');

        // regular user
        $user = User::create([
            'name' => 'Anggota Keluarga',
            'email' => 'user@family.test',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('user');
    }
}
