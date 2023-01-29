<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Admin\User;
use Illuminate\Support\Carbon;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $initUser = 'admin';

        if (!User::withTrashed()->where('email', $initUser)->exists()) {
            User::create([
               'email' => $initUser,
               'name' => $initUser,
               'password' => '$2y$10$Jsfm3aerQQZuOqilLwhKCeK6K/L8QxBIbeMjcmcwtXMXhp27ZNVau',
               'created_at' => Carbon::now(),
               'updated_at' => Carbon::now(),
           ]);
        }
    }
}
