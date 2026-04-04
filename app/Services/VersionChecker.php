<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VersionChecker
{
    protected string $repo = 'logr-beer/logr-app';

    public function check(): ?array
    {
        return Cache::remember('logr_latest_version', now()->addHours(4), function () {
            try {
                $response = Http::withHeaders([
                    'Accept' => 'application/vnd.github.v3+json',
                ])->get("https://api.github.com/repos/{$this->repo}/releases/latest");

                if ($response->failed()) {
                    return null;
                }

                $data = $response->json();
                $latestVersion = ltrim($data['tag_name'] ?? '', 'v');

                return [
                    'version' => $latestVersion,
                    'url' => $data['html_url'] ?? "https://github.com/{$this->repo}/releases",
                ];
            } catch (\Throwable $e) {
                Log::debug('Version check failed: '.$e->getMessage());

                return null;
            }
        });
    }

    public function isUpdateAvailable(): bool
    {
        $latest = $this->check();

        if (! $latest || empty($latest['version'])) {
            return false;
        }

        return version_compare($latest['version'], config('logr.version'), '>');
    }

    public function forceCheck(): ?array
    {
        Cache::forget('logr_latest_version');

        return $this->check();
    }

    public function getLatest(): ?array
    {
        return $this->check();
    }
}
