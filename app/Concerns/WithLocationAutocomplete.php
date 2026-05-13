<?php

namespace App\Concerns;

use App\Jobs\GeocodeLocation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Shared autocomplete logic for location models (Venue, Store, Brewery).
 *
 * Components using this trait should declare properties with a prefix:
 *   public string $venueQuery = '';
 *   public ?int $selectedVenueId = null;
 *   public string $selectedVenueName = '';
 *
 * Then call trait methods with that prefix:
 *   $this->getLocationSuggestions('venue', Venue::class)
 *   $this->selectLocation('venue', $id, Venue::class)
 */
trait WithLocationAutocomplete
{
    public function getLocationSuggestions(string $prefix, string $model, int $limit = 5): array
    {
        $query = $this->{$prefix.'Query'} ?? '';
        $selectedId = $this->{'selected'.ucfirst($prefix).'Id'} ?? null;

        if (strlen($query) < 2 || $selectedId) {
            return [];
        }

        return $model::where('name', 'like', '%'.$query.'%')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getLocationApiResults(string $prefix): array
    {
        $query = $this->{$prefix.'Query'} ?? '';
        $selectedId = $this->{'selected'.ucfirst($prefix).'Id'} ?? null;

        if (strlen($query) < 4 || $selectedId) {
            return [];
        }

        return $this->searchNominatim($query);
    }

    public function selectLocation(string $prefix, int $id, string $model): void
    {
        $location = $model::find($id);
        if ($location) {
            $this->{'selected'.ucfirst($prefix).'Id'} = $location->id;
            $this->{'selected'.ucfirst($prefix).'Name'} = $location->name;
            $this->{$prefix.'Query'} = '';
        }
    }

    public function clearLocation(string $prefix): void
    {
        $this->{'selected'.ucfirst($prefix).'Id'} = null;
        $this->{'selected'.ucfirst($prefix).'Name'} = '';
        $this->{$prefix.'Query'} = '';
    }

    public function importAndSelectLocation(string $prefix, string $cacheKey, string $model): void
    {
        $data = Cache::get("location_nominatim_{$cacheKey}");
        if (! $data) {
            return;
        }

        $matchFields = ['name' => $data['name']];
        if (! empty($data['address'])) {
            $matchFields['address'] = $data['address'];
        }

        $location = $model::firstOrCreate($matchFields, [
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'latitude' => $data['lat'] ?? null,
            'longitude' => $data['lon'] ?? null,
        ]);

        $this->{'selected'.ucfirst($prefix).'Id'} = $location->id;
        $this->{'selected'.ucfirst($prefix).'Name'} = $location->name;
        $this->{$prefix.'Query'} = '';
    }

    /**
     * Resolve a location on form submit: use selected ID, or create from query text.
     */
    public function resolveLocationId(string $prefix, string $model): ?int
    {
        $ucPrefix = ucfirst($prefix);
        $selectedId = $this->{'selected'.$ucPrefix.'Id'} ?? null;

        if ($selectedId) {
            return $selectedId;
        }

        $query = trim($this->{$prefix.'Query'} ?? '');
        if (! $query) {
            return null;
        }

        $location = $model::firstOrCreate(['name' => $query]);

        if ($location->wasRecentlyCreated && auth()->user()->getData('geocoding_enabled')) {
            GeocodeLocation::dispatch($location);
        }

        return $location->id;
    }

    protected function searchNominatim(string $query): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => config('logr.user_agent'),
            ])
                ->timeout(5)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 5,
                ]);

            if (! $response->successful()) {
                return [];
            }

            $results = [];
            foreach ($response->json() as $place) {
                $addr = $place['address'] ?? [];
                $name = $addr['shop'] ?? $addr['amenity'] ?? $addr['building'] ?? $place['name'] ?? null;
                $road = $addr['road'] ?? $addr['pedestrian'] ?? null;
                $number = $addr['house_number'] ?? null;
                $address = $number && $road ? "{$number} {$road}" : ($road ?? null);

                $data = [
                    'name' => $name ?: explode(',', $place['display_name'] ?? '')[0],
                    'address' => $address,
                    'city' => $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? null,
                    'state' => $addr['state'] ?? null,
                    'country' => $addr['country'] ?? null,
                    'lat' => $place['lat'],
                    'lon' => $place['lon'],
                    'display' => $place['display_name'] ?? '',
                ];

                $key = md5($place['place_id'] ?? $place['display_name']);
                Cache::put("location_nominatim_{$key}", $data, now()->addMinutes(10));
                $data['_key'] = $key;
                $results[] = $data;
            }

            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }
}
