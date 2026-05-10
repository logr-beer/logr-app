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

class ScrapeUntappdProfile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, ManagesJobStatus, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 3;

    public int $maxExceptions = 3;

    public function __construct(public User $user) {}

    protected function statusCacheKey(): string
    {
        return "scrape_status_{$this->user->id}";
    }

    public function handle(UntappdScraper $scraper): void
    {
        try {
            $this->setStatusRunning('Scraping...');

            $result = $scraper->importViaHtml($this->user);

            if ($result['error']) {
                $this->setStatusError('Error: '.$result['error']);
            } else {
                $this->setStatusDone("Imported {$result['imported']} new beer(s), updated {$result['skipped']} existing.");
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

        $this->setStatusError('Scraping failed: '.$exception->getMessage());
    }
}
