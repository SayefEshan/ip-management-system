<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@ad-group.com.au',
            'password' => Hash::make('admin123'),
            'is_super_admin' => true,
        ]);

        // Create regular user
        User::create([
            'name' => 'John Doe',
            'email' => 'john@ad-group.com.au',
            'password' => Hash::make('password123'),
            'is_super_admin' => false,
        ]);

        // Create another regular user
        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@ad-group.com.au',
            'password' => Hash::make('password123'),
            'is_super_admin' => false,
        ]);
    }
}
