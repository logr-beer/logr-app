<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the public demo account (demo / password) with sample data.
 * Only for DEMO_MODE instances, local development and tests.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('app.demo_mode') && ! app()->environment('local', 'testing')) {
            $this->command?->error('The demo account is only created in DEMO_MODE or local/testing environments.');

            return;
        }

        $user = User::firstOrCreate(
            ['username' => 'demo'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
            ]
        );

        $this->call(DemoDataSeeder::class, false, ['user' => $user]);
    }
}
