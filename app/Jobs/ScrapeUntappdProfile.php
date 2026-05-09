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
use Illuminate\Support\Facades\Log;

class ScrapeUntappdProfile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 3;

    public int $maxExceptions = 3;

    public function __construct(public User $user) {}

    public function handle(UntappdScraper $scraper): void
    {
        $cacheKey = "scrape_status_{$this->user->id}";

        try {
            Cache::put($cacheKey, ['status' => 'running', 'message' => 'Scraping...'], now()->addMinutes(10));

            $result = $scraper->importViaHtml($this->user);

            if ($result['error']) {
                Cache::put($cacheKey, ['status' => 'error', 'message' => 'Error: '.$result['error']], now()->addMinutes(10));
            } else {
                Cache::put($cacheKey, [
                    'status' => 'done',
                    'message' => "Imported {$result['imported']} new beer(s), updated {$result['skipped']} existing.",
                ], now()->addMinutes(10));
            }
        } catch (\Throwable $e) {
            Log::error('ScrapeUntappdProfile job failed: '.$e->getMessage(), ['exception' => $e]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ScrapeUntappdProfile job permanently failed: '.$exception->getMessage(), [
            'exception' => $exception,
            'user_id' => $this->user->id,
        ]);

        Cache::put("scrape_status_{$this->user->id}", [
            'status' => 'error',
            'message' => 'Scraping failed: '.$exception->getMessage(),
        ], now()->addMinutes(10));
    }
}
