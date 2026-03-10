<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UntappdRss;
use Illuminate\Console\Command;

class SyncUntappdRss extends Command
{
    protected $signature = 'untappd:sync {--user= : Sync a specific user by ID}';

    protected $description = 'Import check-ins from Untappd RSS feeds';

    public function handle(UntappdRss $rss): int
    {
        $query = User::query();

        if ($userId = $this->option('user')) {
            $query->where('id', $userId);
        }

        $users = $query->get()->filter(fn ($user) => ! empty($user->untappd_rss_feeds));

        if ($users->isEmpty()) {
            $this->info('No users with Untappd RSS feeds configured.');
            return self::SUCCESS;
        }

        foreach ($users as $user) {
            $feedCount = count($user->untappd_rss_feeds ?? []);
            $this->info("Syncing {$user->name} ({$feedCount} feed(s))...");

            $result = $rss->syncAll($user);

            if ($result['error']) {
                $this->error("  Error: {$result['error']}");
            }

            $this->info("  Imported: {$result['imported']}, Skipped (already imported): {$result['skipped']}");
        }

        return self::SUCCESS;
    }
}
