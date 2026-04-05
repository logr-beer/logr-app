<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PurgeSettings extends Command
{
    protected $signature = 'logr:purge-settings {--force : Skip confirmation}';

    protected $description = 'Purge all user settings (API keys, integrations) while keeping data intact';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will clear all user settings (API keys, integrations, webhooks). Beer data will be preserved. Continue?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $this->info('Purging all user settings...');

        User::query()->update(['data' => null]);

        $this->info('All user settings have been cleared. Data preserved.');

        return self::SUCCESS;
    }
}
