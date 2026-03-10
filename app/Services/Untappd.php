<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class Untappd
{
    protected string $baseUrl = 'https://api.untappd.com/v4';

    protected ?string $clientId;
    protected ?string $clientSecret;

    public function __construct(?string $clientId = null, ?string $clientSecret = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    protected function request(string $endpoint, array $params = []): ?array
    {
        if (! $this->clientId || ! $this->clientSecret) {
            return null;
        }

        $params = array_merge($params, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        $response = Http::accept('application/json')
            ->withHeaders(['User-Agent' => 'Logr/1.0'])
            ->get("{$this->baseUrl}{$endpoint}", $params);

        if ($response->failed()) {
            return null;
        }

        return $response->json('response');
    }

    // -- Beer --

    public function searchBeers(string $query, int $limit = 25, int $offset = 0): array
    {
        $response = $this->request('/search/beer', [
            'q' => $query,
            'limit' => min($limit, 50),
            'offset' => $offset,
        ]);

        if (! $response) {
            return [];
        }

        $items = $response['beers']['items'] ?? [];

        return collect($items)->map(fn (array $item) => [
            'bid' => $item['beer']['bid'],
            'name' => $item['beer']['beer_name'],
            'style' => $item['beer']['beer_style'] ?? null,
            'abv' => $item['beer']['beer_abv'] ?? null,
            'ibu' => $item['beer']['beer_ibu'] ?? null,
            'description' => $item['beer']['beer_description'] ?? null,
            'label' => $item['beer']['beer_label'] ?? null,
            'rating' => $item['beer']['rating_score'] ?? null,
            'rating_count' => $item['beer']['rating_count'] ?? null,
            'brewery' => [
                'id' => $item['brewery']['brewery_id'] ?? null,
                'name' => $item['brewery']['brewery_name'] ?? null,
                'label' => $item['brewery']['brewery_label'] ?? null,
                'city' => $item['brewery']['city'] ?? $item['brewery']['location']['brewery_city'] ?? null,
                'state' => $item['brewery']['state'] ?? $item['brewery']['location']['brewery_state'] ?? null,
                'country' => $item['brewery']['country_name'] ?? null,
                'url' => $item['brewery']['contact']['url'] ?? null,
            ],
        ])->toArray();
    }

    public function getBeer(int $bid): ?array
    {
        $response = $this->request("/beer/info/{$bid}");

        if (! $response || ! isset($response['beer'])) {
            return null;
        }

        $beer = $response['beer'];

        return [
            'bid' => $beer['bid'],
            'name' => $beer['beer_name'],
            'style' => $beer['beer_style'] ?? null,
            'abv' => $beer['beer_abv'] ?? null,
            'ibu' => $beer['beer_ibu'] ?? null,
            'description' => $beer['beer_description'] ?? null,
            'label' => $beer['beer_label'] ?? null,
            'rating' => $beer['rating_score'] ?? null,
            'rating_count' => $beer['rating_count'] ?? null,
            'brewery' => [
                'id' => $beer['brewery']['brewery_id'] ?? null,
                'name' => $beer['brewery']['brewery_name'] ?? null,
                'label' => $beer['brewery']['brewery_label'] ?? null,
                'city' => $beer['brewery']['location']['brewery_city'] ?? null,
                'state' => $beer['brewery']['location']['brewery_state'] ?? null,
                'country' => $beer['brewery']['country_name'] ?? null,
                'url' => $beer['brewery']['contact']['url'] ?? null,
            ],
        ];
    }

    // -- Brewery --

    public function searchBreweries(string $query, int $limit = 25, int $offset = 0): array
    {
        $response = $this->request('/search/brewery', [
            'q' => $query,
            'limit' => min($limit, 50),
            'offset' => $offset,
        ]);

        if (! $response) {
            return [];
        }

        $items = $response['brewery']['items'] ?? [];

        return collect($items)->map(fn (array $item) => [
            'id' => $item['brewery']['brewery_id'],
            'name' => $item['brewery']['brewery_name'],
            'label' => $item['brewery']['brewery_label'] ?? null,
            'city' => $item['brewery']['location']['brewery_city'] ?? null,
            'state' => $item['brewery']['location']['brewery_state'] ?? null,
            'country' => $item['brewery']['country_name'] ?? null,
            'url' => $item['brewery']['contact']['url'] ?? null,
        ])->toArray();
    }

    public function getBrewery(int $breweryId): ?array
    {
        $response = $this->request("/brewery/info/{$breweryId}");

        if (! $response || ! isset($response['brewery'])) {
            return null;
        }

        $brewery = $response['brewery'];

        return [
            'id' => $brewery['brewery_id'],
            'name' => $brewery['brewery_name'],
            'description' => $brewery['brewery_description'] ?? null,
            'label' => $brewery['brewery_label'] ?? null,
            'city' => $brewery['location']['brewery_city'] ?? null,
            'state' => $brewery['location']['brewery_state'] ?? null,
            'country' => $brewery['country_name'] ?? null,
            'url' => $brewery['contact']['url'] ?? null,
            'beer_count' => $brewery['beer_count'] ?? null,
            'total_count' => $brewery['stats']['total_count'] ?? null,
        ];
    }

    // -- User Beers --

    public function getUserBeers(string $username, int $offset = 0, int $limit = 50): ?array
    {
        $response = $this->request("/user/beers/{$username}", [
            'offset' => $offset,
            'limit' => min($limit, 50),
            'sort' => 'date',
        ]);

        if (! $response) {
            return null;
        }

        $items = $response['beers']['items'] ?? [];
        $totalCount = $response['total_count'] ?? 0;

        return [
            'total_count' => $totalCount,
            'beers' => collect($items)->map(fn (array $item) => [
                'bid' => $item['beer']['bid'],
                'name' => $item['beer']['beer_name'],
                'style' => $item['beer']['beer_style'] ?? null,
                'abv' => $item['beer']['beer_abv'] ?? null,
                'ibu' => $item['beer']['beer_ibu'] ?? null,
                'description' => $item['beer']['beer_description'] ?? null,
                'label' => $item['beer']['beer_label'] ?? null,
                'rating' => $item['user_rating_score'] ?? null,
                'first_checkin_date' => $item['first_created_at'] ?? null,
                'recent_checkin_date' => $item['recent_created_at'] ?? null,
                'total_checkins' => $item['count'] ?? 1,
                'brewery' => [
                    'id' => $item['brewery']['brewery_id'] ?? null,
                    'name' => $item['brewery']['brewery_name'] ?? null,
                    'city' => $item['brewery']['location']['brewery_city'] ?? null,
                    'state' => $item['brewery']['location']['brewery_state'] ?? null,
                    'country' => $item['brewery']['country_name'] ?? null,
                ],
            ])->toArray(),
        ];
    }

    // -- Trending --

    public function trending(): array
    {
        $response = $this->request('/beer/trending');

        if (! $response) {
            return [];
        }

        $items = array_merge(
            $response['micro']['items'] ?? [],
            $response['macro']['items'] ?? [],
        );

        return collect($items)->map(fn (array $item) => [
            'bid' => $item['beer']['bid'],
            'name' => $item['beer']['beer_name'],
            'style' => $item['beer']['beer_style'] ?? null,
            'abv' => $item['beer']['beer_abv'] ?? null,
            'label' => $item['beer']['beer_label'] ?? null,
            'brewery' => [
                'name' => $item['brewery']['brewery_name'] ?? null,
            ],
        ])->toArray();
    }
}
