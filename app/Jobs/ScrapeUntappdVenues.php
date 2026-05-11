<?php

namespace App\Jobs;

use App\Concerns\ManagesJobStatus;
use App\Models\User;
use App\Services\UntappdScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeUntappdVenues implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, ManagesJobStatus, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public int $maxExceptions = 3;

    public function __construct(public User $user) {}

    protected function statusCacheKey(): string
    {
        return "venue_scrape_status_{$this->user->id}";
    }

    public function handle(UntappdScraper $scraper): void
    {
        try {
            $this->setStatusRunning('Scraping venues...');

            $result = $scraper->scrapeUserVenues($this->user);

            if ($result['error']) {
                $this->setStatusError('Error: '.$result['error']);
            } else {
                $this->setStatusDone("Imported {$result['imported']} venue(s), skipped {$result['skipped']} already imported. Geocoding...");

                // Geocode any venues that have addresses but no coordinates
                GeocodeVenues::dispatch();
            }
        } catch (\Throwable $e) {
            Log::error('ScrapeUntappdVenues job failed: '.$e->getMessage(), ['exception' => $e]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ScrapeUntappdVenues job permanently failed: '.$exception->getMessage(), [
            'exception' => $exception,
            'user_id' => $this->user->id,
        ]);

        $this->setStatusError('Venue scraping failed: '.$exception->getMessage());
    }
}
