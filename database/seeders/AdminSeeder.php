<?php

namespace Database\Seeders;

use App\Models\Admin\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $initUser = 'admin';

        if (!User::withTrashed()->where('email', $initUser)->exists()) {
            User::create([
                'email' => $initUser,
                'name' => $initUser,
                'password' => '$2y$10$Jsfm3aerQQZuOqilLwhKCeK6K/L8QxBIbeMjcmcwtXMXhp27ZNVau',
                'created_at' => now(),
                'updated_at' => now(),
                'role' => 'admin',
            ]);
        }
    }
}
