<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CatalogBeer
{
    protected string $baseUrl = 'https://api.catalog.beer';

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

    public function searchBrewers(string $query, int $count = 5, ?string $apiKey = null): array
    {
        $apiKey = $apiKey ?: config('services.catalog_beer.key');

        if (! $apiKey) {
            return [];
        }

        $response = Http::withBasicAuth($apiKey, '')
            ->accept('application/json')
            ->get("{$this->baseUrl}/brewer/search", [
                'q' => $query,
                'count' => $count,
            ]);

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
}
