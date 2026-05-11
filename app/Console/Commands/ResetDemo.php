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

        Artisan::call('down', ['--secret' => 'logr-reset']);
        $this->info('App in maintenance mode.');

        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->info(Artisan::output());

        Artisan::call('db:seed', ['--force' => true]);
        $this->info(Artisan::output());

        Artisan::call('db:seed', ['--force' => true, '--class' => 'Database\\Seeders\\DemoSeeder']);
        $this->info(Artisan::output());

        Artisan::call('up');
        $this->info('App is live.');

        $this->info('Demo reset complete.');

        return self::SUCCESS;
    }
}
