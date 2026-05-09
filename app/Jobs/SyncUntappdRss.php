<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\UntappdRss;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncUntappdRss implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public int $maxExceptions = 3;

    public function __construct(public User $user) {}

    public function handle(UntappdRss $rss): void
    {
        $cacheKey = "rss_status_{$this->user->id}";

        try {
            Cache::put($cacheKey, ['status' => 'running', 'message' => 'Syncing RSS feeds...'], now()->addMinutes(10));

            $result = $rss->syncAll($this->user);

            if ($result['error']) {
                Cache::put($cacheKey, ['status' => 'error', 'message' => 'Error: '.$result['error']], now()->addMinutes(10));
            } else {
                Cache::put($cacheKey, [
                    'status' => 'done',
                    'message' => "Imported {$result['imported']} check-in(s), skipped {$result['skipped']} already imported.",
                ], now()->addMinutes(10));
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

        Cache::put("rss_status_{$this->user->id}", [
            'status' => 'error',
            'message' => 'RSS sync failed: '.$exception->getMessage(),
        ], now()->addMinutes(10));
    }
}
