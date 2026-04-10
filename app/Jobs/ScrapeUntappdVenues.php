<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\UntappdScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ScrapeUntappdVenues implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(public User $user) {}

    public function handle(UntappdScraper $scraper): void
    {
        $cacheKey = "venue_scrape_status_{$this->user->id}";

        Cache::put($cacheKey, ['status' => 'running', 'message' => 'Scraping venues...'], now()->addMinutes(10));

        $result = $scraper->scrapeUserVenues($this->user);

        if ($result['error']) {
            Cache::put($cacheKey, ['status' => 'error', 'message' => 'Error: '.$result['error']], now()->addMinutes(10));
        } else {
            Cache::put($cacheKey, [
                'status' => 'done',
                'message' => "Imported {$result['imported']} venue(s), skipped {$result['skipped']} already imported. Geocoding...",
            ], now()->addMinutes(10));

            // Geocode any venues that have addresses but no coordinates
            GeocodeVenues::dispatch();
        }
    }
}
