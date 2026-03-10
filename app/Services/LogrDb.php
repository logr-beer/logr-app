<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class LogrDb
{
    protected string $baseUrl;
    protected string $token;

    public function __construct(string $token = '')
    {
        $this->baseUrl = rtrim(config('services.logr_db.url', ''), '/');
        $this->token = $token;
    }

    public static function forUser(?User $user = null): ?self
    {
        $user ??= auth()->user();
        $url = config('services.logr_db.url');
        $token = $user?->logr_db_token;

        if (!$url || !$token) {
            return null;
        }

        return new self($token);
    }

    public function searchBeers(string $query, int $perPage = 10): array
    {
        $response = $this->request('/api/search', [
            'q' => $query,
            'type' => 'beer',
            'per_page' => $perPage,
        ]);

        if (!$response) {
            return [];
        }

        return collect($response['beers'] ?? [])->map(fn (array $beer) => [
            'id' => $beer['id'],
            'name' => $beer['name'],
            'style' => implode(', ', $beer['styles'] ?? []),
            'abv' => $beer['abv'],
            'ibu' => $beer['ibu'],
            'description' => $beer['description'] ?? null,
            'brewery_name' => $beer['brewery']['name'] ?? null,
            'brewery_city' => $beer['brewery']['city'] ?? null,
            'brewery_state' => $beer['brewery']['state'] ?? null,
            'brewery_country' => $beer['brewery']['country'] ?? null,
            'brewery_website' => $beer['brewery']['website'] ?? null,
        ])->toArray();
    }

    public function searchBreweries(string $query, int $perPage = 5): array
    {
        $response = $this->request('/api/search', [
            'q' => $query,
            'type' => 'brewery',
            'per_page' => $perPage,
        ]);

        if (!$response) {
            return [];
        }

        return collect($response['breweries'] ?? [])->map(fn (array $brewery) => [
            'id' => $brewery['id'],
            'name' => $brewery['name'],
            'city' => $brewery['city'] ?? null,
            'state' => $brewery['state'] ?? null,
            'country' => $brewery['country'] ?? null,
            'website' => $brewery['website'] ?? null,
        ])->toArray();
    }

    public function getBeer(int $id): ?array
    {
        return $this->request("/api/beers/{$id}");
    }

    public function getBrewery(int $id): ?array
    {
        return $this->request("/api/breweries/{$id}");
    }

    protected function request(string $path, array $query = []): ?array
    {
        if (!$this->baseUrl || !$this->token) {
            \Log::warning('LogrDb: missing config', [
                'has_url' => (bool) $this->baseUrl,
                'has_token' => (bool) $this->token,
            ]);
            return null;
        }

        $url = "{$this->baseUrl}{$path}";

        try {
            $response = Http::withToken($this->token)
                ->accept('application/json')
                ->withoutVerifying()
                ->timeout(10)
                ->get($url, $query);
        } catch (\Exception $e) {
            \Log::error('LogrDb: request exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        if ($response->failed()) {
            \Log::warning('LogrDb: request failed', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        return $response->json();
    }
}
