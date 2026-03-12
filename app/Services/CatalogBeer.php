<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class CatalogBeer
{
    protected string $baseUrl = 'https://api.catalog.beer';

    // ---------------------------------------------------------------
    // HTTP client
    // ---------------------------------------------------------------

    protected function client(?string $apiKey = null): PendingRequest
    {
        $apiKey = $apiKey ?: config('services.catalog_beer.key');

        return Http::withBasicAuth($apiKey, '')
            ->accept('application/json')
            ->contentType('application/json')
            ->baseUrl($this->baseUrl);
    }

    protected function resolveKey(?string $apiKey): string
    {
        return $apiKey ?: config('services.catalog_beer.key') ?: '';
    }

    // ---------------------------------------------------------------
    // Brewer endpoints
    // ---------------------------------------------------------------

    /**
     * List all brewers (paginated via cursor).
     */
    public function listBrewers(int $count = 500, ?string $cursor = null, ?string $apiKey = null): array
    {
        $params = ['count' => $count];
        if ($cursor) {
            $params['cursor'] = $cursor;
        }

        $response = $this->client($apiKey)->get('/brewer', $params);

        return $response->successful() ? $response->json() : [];
    }

    /**
     * Add a new brewer.
     */
    public function addBrewer(array $data, ?string $apiKey = null): ?array
    {
        $response = $this->client($apiKey)->post('/brewer', $data);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Retrieve a single brewer by ID.
     */
    public function getBrewer(string $brewerId, ?string $apiKey = null): ?array
    {
        $response = $this->client($apiKey)->get("/brewer/{$brewerId}");

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Full replacement update of a brewer.
     */
    public function updateBrewer(string $brewerId, array $data, ?string $apiKey = null): ?array
    {
        $response = $this->client($apiKey)->put("/brewer/{$brewerId}", $data);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Partial update of a brewer.
     */
    public function patchBrewer(string $brewerId, array $data, ?string $apiKey = null): ?array
    {
        $response = $this->client($apiKey)->patch("/brewer/{$brewerId}", $data);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Delete a brewer.
     */
    public function deleteBrewer(string $brewerId, ?string $apiKey = null): bool
    {
        $response = $this->client($apiKey)->delete("/brewer/{$brewerId}");

        return $response->status() === 204;
    }

    /**
     * Get brewer count.
     */
    public function brewerCount(?string $apiKey = null): ?int
    {
        $response = $this->client($apiKey)->get('/brewer/count');

        return $response->successful() ? $response->json('value') : null;
    }

    /**
     * Search brewers by query.
     */
    public function searchBrewers(string $query, int $count = 25, ?string $cursor = null, ?string $apiKey = null): array
    {
        $params = ['q' => $query, 'count' => $count];
        if ($cursor) {
            $params['cursor'] = $cursor;
        }

        $response = $this->client($apiKey)->get('/brewer/search', $params);

        if ($response->failed()) {
            return [];
        }

        $data = $response->json('data', []);

        return collect($data)->map(fn (array $brewer) => [
            'id' => $brewer['id'],
            'name' => $brewer['name'],
            'description' => $brewer['description'] ?? null,
            'short_description' => $brewer['short_description'] ?? null,
            'url' => $brewer['url'] ?? null,
        ])->toArray();
    }

    /**
     * List all beers by a specific brewer.
     */
    public function brewerBeers(string $brewerId, ?string $apiKey = null): array
    {
        $response = $this->client($apiKey)->get("/brewer/{$brewerId}/beer");

        return $response->successful() ? $response->json() : [];
    }

    /**
     * List all locations for a specific brewer.
     */
    public function brewerLocations(string $brewerId, ?string $apiKey = null): array
    {
        $response = $this->client($apiKey)->get("/brewer/{$brewerId}/locations");

        return $response->successful() ? $response->json() : [];
    }

    // ---------------------------------------------------------------
    // Beer endpoints
    // ---------------------------------------------------------------

    /**
     * List all beers (paginated via cursor).
     */
    public function listBeers(int $count = 500, ?string $cursor = null, ?string $apiKey = null): array
    {
        $params = ['count' => $count];
        if ($cursor) {
            $params['cursor'] = $cursor;
        }

        $response = $this->client($apiKey)->get('/beer', $params);

        return $response->successful() ? $response->json() : [];
    }

    /**
     * Add a new beer.
     */
    public function addBeer(array $data, ?string $apiKey = null): ?array
    {
        $response = $this->client($apiKey)->post('/beer', $data);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Retrieve a single beer by ID.
     */
    public function getBeer(string $beerId, ?string $apiKey = null): ?array
    {
        $response = $this->client($apiKey)->get("/beer/{$beerId}");

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Full replacement update of a beer.
     */
    public function updateBeer(string $beerId, array $data, ?string $apiKey = null): ?array
    {
        $response = $this->client($apiKey)->put("/beer/{$beerId}", $data);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Partial update of a beer.
     */
    public function patchBeer(string $beerId, array $data, ?string $apiKey = null): ?array
    {
        $response = $this->client($apiKey)->patch("/beer/{$beerId}", $data);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Delete a beer.
     */
    public function deleteBeer(string $beerId, ?string $apiKey = null): bool
    {
        $response = $this->client($apiKey)->delete("/beer/{$beerId}");

        return $response->status() === 204;
    }

    /**
     * Get beer count.
     */
    public function beerCount(?string $apiKey = null): ?int
    {
        $response = $this->client($apiKey)->get('/beer/count');

        return $response->successful() ? $response->json('value') : null;
    }

    /**
     * Search beers by query.
     */
    public function search(string $query, int $count = 10, ?string $apiKey = null): array
    {
        $apiKey = $apiKey ?: config('services.catalog_beer.key');

        if (! $apiKey) {
            return [];
        }

        $response = Http::withBasicAuth($apiKey, '')
            ->accept('application/json')
            ->get("{$this->baseUrl}/beer/search", [
                'q' => $query,
                'count' => $count,
            ]);

        if ($response->failed()) {
            return [];
        }

        $data = $response->json('data', []);

        return collect($data)->map(fn (array $beer) => [
            'id' => $beer['id'],
            'name' => $beer['name'],
            'style' => $beer['style'] ?? null,
            'abv' => $beer['abv'] ?? null,
            'ibu' => $beer['ibu'] ?? null,
            'description' => $beer['description'] ?? null,
            'cb_verified' => $beer['cb_verified'] ?? false,
            'brewer_verified' => $beer['brewer_verified'] ?? false,
            'brewer' => $beer['brewer'] ?? null,
        ])->toArray();
    }

    // ---------------------------------------------------------------
    // Location endpoints
    // ---------------------------------------------------------------

    /**
     * Retrieve a single location by ID.
     */
    public function getLocation(string $locationId, ?string $apiKey = null): ?array
    {
        $response = $this->client($apiKey)->get("/location/{$locationId}");

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Add a new location.
     */
    public function addLocation(array $data, ?string $apiKey = null): ?array
    {
        $response = $this->client($apiKey)->post('/location', $data);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Full replacement update of a location.
     */
    public function updateLocation(string $locationId, array $data, ?string $apiKey = null): ?array
    {
        $response = $this->client($apiKey)->put("/location/{$locationId}", $data);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Partial update of a location.
     */
    public function patchLocation(string $locationId, array $data, ?string $apiKey = null): ?array
    {
        $response = $this->client($apiKey)->patch("/location/{$locationId}", $data);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Delete a location.
     */
    public function deleteLocation(string $locationId, ?string $apiKey = null): bool
    {
        $response = $this->client($apiKey)->delete("/location/{$locationId}");

        return $response->status() === 204;
    }

    /**
     * Find nearby locations by coordinates.
     */
    public function nearbyLocations(float $latitude, float $longitude, int $searchRadius = 25, bool $metric = false, int $count = 100, ?string $cursor = null, ?string $apiKey = null): array
    {
        $params = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'search_radius' => $searchRadius,
            'metric' => $metric ? 'true' : 'false',
            'count' => $count,
        ];
        if ($cursor) {
            $params['cursor'] = $cursor;
        }

        $response = $this->client($apiKey)->get('/location/nearby', $params);

        return $response->successful() ? $response->json() : [];
    }

    /**
     * Find locations by ZIP code.
     */
    public function locationsByZip(string $zipCode, int $searchRadius = 25, bool $metric = false, int $count = 100, ?string $cursor = null, ?string $apiKey = null): array
    {
        $params = [
            'zip_code' => $zipCode,
            'search_radius' => $searchRadius,
            'metric' => $metric ? 'true' : 'false',
            'count' => $count,
        ];
        if ($cursor) {
            $params['cursor'] = $cursor;
        }

        $response = $this->client($apiKey)->get('/location/zip', $params);

        return $response->successful() ? $response->json() : [];
    }

    /**
     * Find locations by city.
     */
    public function locationsByCity(string $city, string $state, int $searchRadius = 25, bool $metric = false, int $count = 100, ?string $cursor = null, ?string $apiKey = null): array
    {
        $params = [
            'city' => $city,
            'state' => $state,
            'search_radius' => $searchRadius,
            'metric' => $metric ? 'true' : 'false',
            'count' => $count,
        ];
        if ($cursor) {
            $params['cursor'] = $cursor;
        }

        $response = $this->client($apiKey)->get('/location/city', $params);

        return $response->successful() ? $response->json() : [];
    }

    // ---------------------------------------------------------------
    // Address endpoints
    // ---------------------------------------------------------------

    /**
     * Add an address to a location.
     */
    public function addAddress(string $locationId, array $data, ?string $apiKey = null): ?array
    {
        $response = $this->client($apiKey)->post("/address/{$locationId}", $data);

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Replace the address for a location.
     */
    public function updateAddress(string $locationId, array $data, ?string $apiKey = null): ?array
    {
        $response = $this->client($apiKey)->put("/address/{$locationId}", $data);

        return $response->successful() ? $response->json() : null;
    }

    // ---------------------------------------------------------------
    // Convenience: submit a local beer/brewery to catalog.beer
    // ---------------------------------------------------------------

    /**
     * Submit a local brewery to catalog.beer if it doesn't already have a catalog_beer_brewer_id.
     * Returns the catalog.beer brewer ID on success, null on failure.
     */
    public function submitBrewery(\App\Models\Brewery $brewery, ?string $apiKey = null): ?string
    {
        if ($brewery->catalog_beer_brewer_id) {
            return $brewery->catalog_beer_brewer_id;
        }

        $result = $this->addBrewer([
            'name' => $brewery->name,
            'url' => $brewery->website,
        ], $apiKey);

        if ($result && ! empty($result['id'])) {
            $brewery->update(['catalog_beer_brewer_id' => $result['id']]);

            return $result['id'];
        }

        return null;
    }

    /**
     * Submit a local beer to catalog.beer. Will also submit the brewery if needed.
     * Returns the catalog.beer beer ID on success, null on failure.
     */
    public function submitBeer(\App\Models\Beer $beer, ?string $apiKey = null): ?string
    {
        if ($beer->catalog_beer_id) {
            return $beer->catalog_beer_id;
        }

        // Ensure brewery exists on catalog.beer
        $brewerId = null;
        if ($beer->brewery) {
            $brewerId = $this->submitBrewery($beer->brewery, $apiKey);
        }

        if (! $brewerId) {
            return null;
        }

        $result = $this->addBeer([
            'brewer_id' => $brewerId,
            'name' => $beer->name,
            'style' => is_array($beer->style) ? implode(', ', $beer->style) : ($beer->style ?? ''),
            'abv' => $beer->abv ?? 0,
            'ibu' => $beer->ibu,
            'description' => $beer->description,
        ], $apiKey);

        if ($result && ! empty($result['id'])) {
            $beer->update(['catalog_beer_id' => $result['id']]);

            return $result['id'];
        }

        return null;
    }
}
