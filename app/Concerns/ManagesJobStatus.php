<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Cache;

trait ManagesJobStatus
{
    abstract protected function statusCacheKey(): string;

    protected function setStatusRunning(string $message): void
    {
        Cache::put($this->statusCacheKey(), ['status' => 'running', 'message' => $message], now()->addMinutes(10));
    }

    protected function setStatusDone(string $message): void
    {
        Cache::put($this->statusCacheKey(), ['status' => 'done', 'message' => $message], now()->addMinutes(10));
    }

    protected function setStatusError(string $message): void
    {
        Cache::put($this->statusCacheKey(), ['status' => 'error', 'message' => $message], now()->addMinutes(10));
    }
}
