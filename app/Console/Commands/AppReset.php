<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class AppReset extends Command
{
    protected $signature = 'app:reset {--force : Skip confirmation}';

    protected $description = 'Reset the app to a fresh install state (wipes all data, triggers setup wizard)';

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('This will wipe ALL data including user accounts. Continue?')) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        $this->info('Resetting database...');
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->line(Artisan::output());

        $this->info('Running seeders...');
        Artisan::call('db:seed', ['--force' => true]);
        $this->line(Artisan::output());

        $this->info('App reset complete. Visit the site to run the setup wizard.');

        return self::SUCCESS;
    }
}
