<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PubBeerDb
{
    protected string $baseUrl;

    protected string $token;

    public function __construct(string $token = '')
    {
        $this->baseUrl = rtrim(config('services.logr.pub_url', ''), '/');
        $this->token = $token;
    }

    public static function forInstance(): ?self
    {
        $pubUrl = config('services.logr.pub_url');
        if (! $pubUrl) {
            return null;
        }

        $token = Setting::get('pub_api_key');
        if (! $token) {
            return null;
        }

        return new self($token);
    }

    public static function provisionKey(): ?string
    {
        $pubUrl = rtrim(config('services.logr.pub_url', ''), '/');
        if (! $pubUrl) {
            Log::debug('PubBeerDb: no pub URL configured');

            return null;
        }

        $endpoint = $pubUrl.'/api/instances';
        $payload = [
            'name' => config('app.name', 'Logr'),
            'url' => config('app.url'),
        ];

        Log::debug('PubBeerDb: provisioning key', ['endpoint' => $endpoint, 'payload' => $payload]);

        try {
            $http = Http::accept('application/json')->timeout(10);
            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }
            $response = $http->post($endpoint, $payload);

            Log::debug('PubBeerDb: provision response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $token = $response->json('api_key') ?? $response->json('token');
                if ($token) {
                    Setting::set('pub_api_key', $token);
                    Log::info('PubBeerDb: key provisioned successfully');

                    return $token;
                }

                Log::warning('PubBeerDb: response successful but no token in body');
            }

            Log::warning('PubBeerDb: key provisioning failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('PubBeerDb: key provisioning error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public function searchBeers(string $query, int $perPage = 10): array
    {
        $cacheKey = 'pub_beers_'.md5($query.'_'.$perPage);

        return Cache::remember($cacheKey, now()->addHour(), function () use ($query, $perPage) {
            $response = $this->request('/api/beers', [
                'search' => $query,
                'per_page' => $perPage,
            ]);

            if (! $response) {
                return [];
            }

            $beers = $response['data'] ?? $response;

            return collect($beers)->map(fn (array $beer) => [
                'id' => $beer['id'],
                'name' => $beer['name'],
                'style' => is_array($beer['styles'] ?? null) ? implode(', ', $beer['styles']) : ($beer['style'] ?? null),
                'abv' => $beer['abv'] ?? null,
                'ibu' => $beer['ibu'] ?? null,
                'description' => $beer['description'] ?? null,
                'brewery_name' => $beer['brewery']['name'] ?? $beer['brewery_name'] ?? null,
                'brewery_id' => $beer['brewery']['id'] ?? $beer['brewery_id'] ?? null,
                'brewery_city' => $beer['brewery']['city'] ?? $beer['brewery_city'] ?? null,
                'brewery_state' => $beer['brewery']['state'] ?? $beer['brewery_state'] ?? null,
                'brewery_country' => $beer['brewery']['country'] ?? $beer['brewery_country'] ?? null,
                'brewery_website' => $beer['brewery']['website'] ?? $beer['brewery_website'] ?? null,
            ])->toArray();
        });
    }

    public function searchBreweries(string $query, int $perPage = 5): array
    {
        $cacheKey = 'pub_breweries_'.md5($query.'_'.$perPage);

        return Cache::remember($cacheKey, now()->addDay(), function () use ($query, $perPage) {
            $response = $this->request('/api/breweries', [
                'search' => $query,
                'per_page' => $perPage,
            ]);

            if (! $response) {
                return [];
            }

            $breweries = $response['data'] ?? $response;

            return collect($breweries)->map(fn (array $brewery) => [
                'id' => $brewery['id'],
                'name' => $brewery['name'],
                'city' => $brewery['city'] ?? null,
                'state' => $brewery['state'] ?? null,
                'country' => $brewery['country'] ?? null,
                'website' => $brewery['website'] ?? null,
            ])->toArray();
        });
    }

    /**
     * Handle a 401 response on a Sanctum-authenticated request.
     * Clears the stored secret key so the user can re-link.
     */
    public static function handleSecretKeyRevoked(?int $userId = null): void
    {
        $userId ??= auth()->id();
        if (! $userId) {
            return;
        }

        $user = \App\Models\User::find($userId);
        if ($user) {
            $user->setData('pub_secret_key', null);
            $user->save();
        }

        Log::info('PubBeerDb: secret key revoked/expired, cleared for user', ['user_id' => $userId]);
    }

    /**
     * Unified search. Returns ['beers' => [...], 'breweries' => [...]].
     */
    public function search(string $query): array
    {
        $cacheKey = 'pub_search_'.md5($query);

        return Cache::remember($cacheKey, now()->addHour(), function () use ($query) {
            $response = $this->request('/api/search', ['q' => $query]);

            if (! $response) {
                return ['beers' => [], 'breweries' => []];
            }

            return [
                'beers' => $response['beers'] ?? [],
                'breweries' => $response['breweries'] ?? [],
            ];
        });
    }

    /**
     * Submit a beer for review. Requires user's secret key (write access).
     *
     * @return array|null Response on success, null on failure. Returns ['status' => 401] on auth failure.
     */
    public function submitBeer(string $userToken, array $data): ?array
    {
        if (! $this->baseUrl) {
            return null;
        }

        $url = "{$this->baseUrl}/api/submissions";
        $payload = [
            'type' => 'beer',
            'data' => $data,
        ];

        try {
            $http = Http::withToken($userToken)->accept('application/json')->timeout(10);
            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }
            $response = $http->post($url, $payload);
        } catch (\Exception $e) {
            Log::error('PubBeerDb: submitBeer exception', ['error' => $e->getMessage()]);

            return null;
        }

        if ($response->status() === 401) {
            return ['status' => 401];
        }

        if ($response->failed()) {
            Log::warning('PubBeerDb: submitBeer failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    public function getBeer(string $uuid): ?array
    {
        return $this->request("/api/beers/{$uuid}");
    }

    public function getBrewery(string $uuid): ?array
    {
        return $this->request("/api/breweries/{$uuid}");
    }

    protected function request(string $path, array $query = []): ?array
    {
        if (! $this->baseUrl || ! $this->token) {
            return null;
        }

        $url = "{$this->baseUrl}{$path}";

        try {
            $http = Http::withToken($this->token)->accept('application/json')->timeout(10);
            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }
            $response = $http->get($url, $query);
        } catch (\Exception $e) {
            \Log::error('PubBeerDb: request exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if ($response->failed()) {
            \Log::warning('PubBeerDb: request failed', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }
}
