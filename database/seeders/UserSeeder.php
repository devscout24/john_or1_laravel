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
            ['email' => 'admin@beachapp.com'],
            [
                'name' => 'Super Admin',
                'username' => 'admin',
                'password' => Hash::make('12345678'),
                'status' => 'active',
                'provider_status' => 'approved',
            ]
        );
        $admin->assignRole('admin');
        $admin_2 = User::firstOrCreate(
            ['email' => 'manjurulalammahi@gmail.com'],
            [
                'name' => 'Mahi Alam',
                'username' => 'mahi',
                'password' => Hash::make('12345678'),
                'status' => 'active',
                'provider_status' => 'approved',
            ]
        );
        $admin_2->assignRole('admin');


        // PROVIDER USER
        $provider = User::firstOrCreate(
            ['email' => 'provider@beachapp.com'],
            [
                'name'            => 'Provider User',
                'username'        => 'provider123',
                'password'        => Hash::make('12345678'),
                'status'          => 'active',
                'provider_status' => 'approved',
            ]
        );
        $provider->assignRole('provider');

        // CUSTOMER USER
        $customer = User::firstOrCreate(
            ['email' => 'customer@beachapp.com'],
            [
                'name' => 'Customer User',
                'username' => 'customer456',
                'password' => Hash::make('12345678'),
                'status' => 'active',
                'provider_status' => 'pending',
            ]
        );
        $customer->assignRole('customer');
    }
}
