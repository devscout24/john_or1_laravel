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
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Super Admin',
                'username' => 'admin',
                'password' => Hash::make('12345678'),
                'status' => 'active',
            ]
        );
        $admin->assignRole('admin');

        // CUSTOMER USER
        $user = User::firstOrCreate(
            ['email' => 'user@user.com'],
            [
                'name' => 'Demo User',
                'username' => 'demo_user',
                'password' => Hash::make('12345678'),
                'status' => 'active',
            ]
        );
        $user->assignRole('user');
    }
}
