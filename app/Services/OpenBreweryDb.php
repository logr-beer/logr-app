<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenBreweryDb
{
    protected string $baseUrl = 'https://api.openbrewerydb.org/v1';

    public function search(string $query, int $perPage = 10): array
    {
        $response = Http::get("{$this->baseUrl}/breweries/search", [
            'query' => $query,
            'per_page' => $perPage,
        ]);

        if ($response->failed()) {
            return [];
        }

        return collect($response->json())->map(fn (array $brewery) => [
            'id' => $brewery['id'],
            'name' => $brewery['name'],
            'city' => $brewery['city'] ?? null,
            'state' => $brewery['state_province'] ?? $brewery['state'] ?? null,
            'country' => $brewery['country'] ?? null,
            'website' => $brewery['website_url'] ?? null,
            'brewery_type' => $brewery['brewery_type'] ?? null,
        ])->toArray();
    }
}
