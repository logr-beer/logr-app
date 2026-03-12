<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (User::count() === 0) {
            User::create([
                'name' => 'Admin',
                'username' => 'admin',
                'password' => bcrypt('password'),
            ]);
        }

        Venue::firstOrCreate(
            ['name' => 'Home'],
        );
    }
}
