<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UntappdScraper;
use Illuminate\Console\Command;

class ScrapeUntappd extends Command
{
    protected $signature = 'untappd:scrape {--user= : Specific user ID to scrape}';

    protected $description = 'Scrape beer history from Untappd user profiles';

    public function handle(UntappdScraper $scraper): int
    {
        $query = User::query();

        if ($userId = $this->option('user')) {
            $query->where('id', $userId);
        }

        $users = $query->get()->filter(fn ($user) => ! empty($user->untappd_username));

        if ($users->isEmpty()) {
            $this->warn('No users with Untappd usernames found.');

            return self::SUCCESS;
        }

        foreach ($users as $user) {
            $this->info("Scraping {$user->untappd_username}...");

            $result = $scraper->scrapeUserBeers($user);

            if ($result['error']) {
                $this->error("  Error: {$result['error']}");
            } else {
                $this->info("  New beers: {$result['imported']}, Already had: {$result['skipped']}");
            }
        }

        return self::SUCCESS;
    }
}
