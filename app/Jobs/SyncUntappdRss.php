<?php

namespace App\Jobs;

use App\Concerns\ManagesJobStatus;
use App\Models\User;
use App\Services\UntappdRss;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncUntappdRss implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, ManagesJobStatus, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public int $maxExceptions = 3;

    public function __construct(public User $user) {}

    protected function statusCacheKey(): string
    {
        return "rss_status_{$this->user->id}";
    }

    public function handle(UntappdRss $rss): void
    {
        try {
            $this->setStatusRunning('Syncing RSS feeds...');

            $result = $rss->syncAll($this->user);

            if ($result['error']) {
                $this->setStatusError('Error: '.$result['error']);
            } else {
                $this->setStatusDone("Imported {$result['imported']} check-in(s), skipped {$result['skipped']} already imported.");
            }
        } catch (\Throwable $e) {
            Log::error('SyncUntappdRss job failed: '.$e->getMessage(), ['exception' => $e]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncUntappdRss job permanently failed: '.$exception->getMessage(), [
            'exception' => $exception,
            'user_id' => $this->user->id,
        ]);

        $this->setStatusError('RSS sync failed: '.$exception->getMessage());
    }
}
