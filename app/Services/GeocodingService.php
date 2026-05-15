<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    /**
     * Geocode a location string (city, state, country) using OpenStreetMap Nominatim.
     *
     * @return array{lat: float, lng: float, city: ?string, state: ?string, country: ?string}|null
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
                    'User-Agent' => config('logr.user_agent'),
                ])->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 1,
                ]);

                if ($response->successful() && $response->json()) {
                    $result = $response->json()[0];
                    $addr = $result['address'] ?? [];

                    return [
                        'lat' => (float) $result['lat'],
                        'lng' => (float) $result['lon'],
                        'city' => $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? null,
                        'state' => $addr['state'] ?? null,
                        'country' => $addr['country'] ?? null,
                    ];
                }
            } catch (\Throwable $e) {
                // Silently fail — geocoding is best-effort
            }

            return null;
        });
    }
}
