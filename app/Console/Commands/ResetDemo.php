<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ResetDemo extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Wipe and re-seed the database with demo data';

    public function handle(): int
    {
        $this->info('Resetting demo database...');

        Artisan::call('migrate:fresh', ['--force' => true, '--seed' => true, '--seeder' => 'Database\\Seeders\\DemoSeeder']);
        $this->info(Artisan::output());

        $this->info('Demo reset complete.');

        return self::SUCCESS;
    }
}
