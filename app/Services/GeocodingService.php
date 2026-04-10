<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    /**
     * Geocode a location string (city, state, country) using OpenStreetMap Nominatim.
     *
     * @return array{lat: float, lng: float}|null
     */
    public static function geocode(?string $city, ?string $state, ?string $country): ?array
    {
        $parts = collect([$city, $state, $country])->filter()->values();

        if ($parts->isEmpty()) {
            return null;
        }

        $query = $parts->implode(', ');
        $cacheKey = 'geocode:'.md5($query);

        return Cache::remember($cacheKey, now()->addMonth(), function () use ($query) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Logr-BeerTracker/1.0',
                ])->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                ]);

                if ($response->successful() && $response->json()) {
                    $result = $response->json()[0];

                    return [
                        'lat' => (float) $result['lat'],
                        'lng' => (float) $result['lon'],
                    ];
                }
            } catch (\Throwable $e) {
                // Silently fail — geocoding is best-effort
            }

            return null;
        });
    }
}
