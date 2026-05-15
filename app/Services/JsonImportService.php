<?php

namespace App\Services;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\Collection;
use App\Models\Companion;
use App\Models\Inventory;
use App\Models\Store;
use App\Models\Tag;
use App\Models\Venue;
use Carbon\Carbon;

class JsonImportService
{
    public array $results = [];

    public array $errors = [];

    private int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->results = [
            'tags' => ['created' => 0, 'existing' => 0],
            'companions' => ['created' => 0, 'existing' => 0],
            'breweries' => ['created' => 0, 'existing' => 0],
            'beers' => ['created' => 0, 'existing' => 0],
            'venues' => ['created' => 0, 'existing' => 0],
            'stores' => ['created' => 0, 'existing' => 0],
            'checkins' => ['created' => 0, 'skipped' => 0],
            'inventory' => ['created' => 0, 'merged' => 0],
            'collections' => ['created' => 0, 'existing' => 0],
        ];
    }

    public static function preview(array $data): array
    {
        return [
            'version' => $data['version'] ?? 'unknown',
            'exported_at' => $data['exported_at'] ?? null,
            'breweries' => count($data['breweries'] ?? []),
            'beers' => count($data['beers'] ?? []),
            'venues' => count($data['venues'] ?? []),
            'stores' => count($data['stores'] ?? []),
            'checkins' => count($data['checkins'] ?? []),
            'inventory' => count($data['inventory'] ?? []),
            'collections' => count($data['collections'] ?? []),
            'tags' => count($data['tags'] ?? []),
            'companions' => count($data['companions'] ?? []),
        ];
    }

    public function import(array $data): void
    {
        $tagMap = $this->importTags($data['tags'] ?? []);
        $companionMap = $this->importCompanions($data['companions'] ?? []);
        $breweryMap = $this->importBreweries($data['breweries'] ?? []);
        $beerMap = $this->importBeers($data['beers'] ?? [], $breweryMap, $tagMap);
        $venueMap = $this->importVenues($data['venues'] ?? []);
        $storeMap = $this->importStores($data['stores'] ?? []);
        $this->importCheckins($data['checkins'] ?? [], $beerMap, $venueMap, $tagMap, $companionMap);
        $this->importInventory($data['inventory'] ?? [], $beerMap, $storeMap);
        $this->importCollections($data['collections'] ?? [], $beerMap);
    }

    private function importTags(array $tags): array
    {
        $map = [];
        foreach ($tags as $tagData) {
            $name = $tagData['name'] ?? null;
            if (! $name) {
                continue;
            }

            $tag = Tag::where('name', $name)->first();
            if ($tag) {
                $this->results['tags']['existing']++;
            } else {
                $tag = Tag::create(['name' => $name, 'color' => $tagData['color'] ?? null]);
                $this->results['tags']['created']++;
            }
            $map[$name] = $tag->id;
        }

        return $map;
    }

    private function importCompanions(array $companions): array
    {
        $map = [];
        foreach ($companions as $data) {
            $name = $data['name'] ?? null;
            if (! $name) {
                continue;
            }

            $companion = Companion::where('name', $name)->first();
            if ($companion) {
                $this->results['companions']['existing']++;
            } else {
                $companion = Companion::create(['name' => $name]);
                $this->results['companions']['created']++;
            }
            $map[$name] = $companion->id;
        }

        return $map;
    }

    private function importBreweries(array $breweries): array
    {
        $map = [];
        foreach ($breweries as $data) {
            $name = $data['name'] ?? null;
            if (! $name) {
                continue;
            }

            $brewery = null;
            if (! empty($data['pub_uuid'])) {
                $brewery = Brewery::where('pub_uuid', $data['pub_uuid'])->first();
            }
            if (! $brewery) {
                $brewery = Brewery::where('name', $name)->first();
            }

            if ($brewery) {
                $this->results['breweries']['existing']++;
                $this->backfill($brewery, $data, ['pub_uuid', 'catalog_beer_brewer_id', 'address', 'city', 'state', 'country', 'latitude', 'longitude', 'website']);
            } else {
                $brewery = Brewery::create(array_filter([
                    'name' => $name,
                    'pub_uuid' => $data['pub_uuid'] ?? null,
                    'catalog_beer_brewer_id' => $data['catalog_beer_brewer_id'] ?? null,
                    'address' => $data['address'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'country' => $data['country'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'website' => $data['website'] ?? null,
                ], fn ($v) => $v !== null));
                $this->results['breweries']['created']++;
            }

            $map[$name] = $brewery->id;
            if (! empty($data['pub_uuid'])) {
                $map['uuid:'.$data['pub_uuid']] = $brewery->id;
            }
        }

        return $map;
    }

    private function importBeers(array $beers, array $breweryMap, array $tagMap): array
    {
        $map = [];
        foreach ($beers as $data) {
            $name = $data['name'] ?? null;
            if (! $name) {
                continue;
            }

            $breweryId = $this->resolveBreweryId($data, $breweryMap);

            $beer = null;
            if (! empty($data['pub_uuid'])) {
                $beer = Beer::where('pub_uuid', $data['pub_uuid'])->first();
            }
            if (! $beer) {
                $query = Beer::where('name', $name);
                if ($breweryId) {
                    $query->where('brewery_id', $breweryId);
                }
                $beer = $query->first();
            }

            if ($beer) {
                $this->results['beers']['existing']++;
                $this->backfillBeer($beer, $data, $breweryId);
            } else {
                $beer = Beer::create(array_filter([
                    'name' => $name,
                    'pub_uuid' => $data['pub_uuid'] ?? null,
                    'catalog_beer_id' => $data['catalog_beer_id'] ?? null,
                    'brewery_id' => $breweryId,
                    'style' => $data['style'] ?? null,
                    'abv' => $data['abv'] ?? null,
                    'ibu' => $data['ibu'] ?? null,
                    'release_year' => $data['release_year'] ?? null,
                    'brewer_master' => $data['brewer_master'] ?? null,
                    'description' => $data['description'] ?? null,
                    'is_favorite' => $data['is_favorite'] ?? false,
                ], fn ($v) => $v !== null));
                $this->results['beers']['created']++;
            }

            if (! empty($data['tags'])) {
                $tagIds = collect($data['tags'])
                    ->map(fn ($t) => $tagMap[$t] ?? Tag::firstOrCreate(['name' => $t])->id)
                    ->filter()
                    ->toArray();
                $beer->tags()->syncWithoutDetaching($tagIds);
            }

            $key = $name.'|'.($breweryId ?? '');
            $map[$key] = $beer->id;
            if (! empty($data['pub_uuid'])) {
                $map['uuid:'.$data['pub_uuid']] = $beer->id;
            }
        }

        return $map;
    }

    private function importVenues(array $venues): array
    {
        $map = [];
        foreach ($venues as $data) {
            $name = $data['name'] ?? null;
            if (! $name) {
                continue;
            }

            $venue = null;
            if (! empty($data['untappd_venue_id'])) {
                $venue = Venue::where('untappd_venue_id', $data['untappd_venue_id'])->first();
            }
            if (! $venue) {
                $venue = Venue::where('name', $name)->first();
            }

            if ($venue) {
                $this->results['venues']['existing']++;
                $this->backfill($venue, $data, ['untappd_venue_id', 'address', 'city', 'state', 'country', 'latitude', 'longitude', 'website']);
            } else {
                $venue = Venue::create(array_filter([
                    'name' => $name,
                    'untappd_venue_id' => $data['untappd_venue_id'] ?? null,
                    'address' => $data['address'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'country' => $data['country'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'website' => $data['website'] ?? null,
                ], fn ($v) => $v !== null));
                $this->results['venues']['created']++;
            }

            $map[$name] = $venue->id;
        }

        return $map;
    }

    private function importStores(array $stores): array
    {
        $map = [];
        foreach ($stores as $data) {
            $name = $data['name'] ?? null;
            if (! $name) {
                continue;
            }

            $store = Store::where('name', $name)->first();
            if ($store) {
                $this->results['stores']['existing']++;
                $this->backfill($store, $data, ['address', 'city', 'state', 'country', 'latitude', 'longitude', 'website']);
            } else {
                $store = Store::create(array_filter([
                    'name' => $name,
                    'address' => $data['address'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'country' => $data['country'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'website' => $data['website'] ?? null,
                ], fn ($v) => $v !== null));
                $this->results['stores']['created']++;
            }

            $map[$name] = $store->id;
        }

        return $map;
    }

    private function importCheckins(array $checkins, array $beerMap, array $venueMap, array $tagMap, array $companionMap): void
    {
        foreach ($checkins as $data) {
            try {
                $beerId = $this->resolveBeerIdFromData($data, $beerMap);
                if (! $beerId) {
                    $this->results['checkins']['skipped']++;

                    continue;
                }

                if (! empty($data['untappd_id']) && Checkin::where('untappd_id', $data['untappd_id'])->exists()) {
                    $this->results['checkins']['skipped']++;

                    continue;
                }

                $createdAt = ! empty($data['created_at']) ? Carbon::parse($data['created_at']) : now();
                if (Checkin::where('user_id', $this->userId)->where('beer_id', $beerId)->where('created_at', $createdAt)->exists()) {
                    $this->results['checkins']['skipped']++;

                    continue;
                }

                $venueId = null;
                if (! empty($data['venue_name'])) {
                    $venueId = $venueMap[$data['venue_name']]
                        ?? Venue::where('name', $data['venue_name'])->first()?->id
                        ?? Venue::create(['name' => $data['venue_name']])->id;
                }

                $checkin = Checkin::create([
                    'user_id' => $this->userId,
                    'beer_id' => $beerId,
                    'venue_id' => $venueId,
                    'rating' => $data['rating'] ?? null,
                    'serving_type' => $data['serving_type'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'location' => $data['location'] ?? $data['venue_name'] ?? null,
                    'untappd_id' => $data['untappd_id'] ?? null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                if (! empty($data['tags'])) {
                    $tagIds = collect($data['tags'])->map(fn ($t) => $tagMap[$t] ?? Tag::firstOrCreate(['name' => $t])->id)->toArray();
                    $checkin->tags()->syncWithoutDetaching($tagIds);
                }

                if (! empty($data['companions'])) {
                    $compIds = collect($data['companions'])->map(fn ($n) => $companionMap[$n] ?? Companion::firstOrCreate(['name' => $n])->id)->toArray();
                    $checkin->companions()->syncWithoutDetaching($compIds);
                }

                $this->results['checkins']['created']++;
            } catch (\Throwable $e) {
                $this->errors[] = 'Checkin for "'.($data['beer_name'] ?? '?').'": '.$e->getMessage();
            }
        }
    }

    private function importInventory(array $inventory, array $beerMap, array $storeMap): void
    {
        foreach ($inventory as $data) {
            try {
                $beerId = $this->resolveBeerIdFromData($data, $beerMap);
                if (! $beerId) {
                    continue;
                }

                $storeId = null;
                if (! empty($data['store_name'])) {
                    $storeId = $storeMap[$data['store_name']] ?? Store::firstOrCreate(['name' => $data['store_name']])->id;
                }

                $existing = Inventory::where('beer_id', $beerId)
                    ->where('user_id', $this->userId)
                    ->where('storage_location', $data['storage_location'] ?? null)
                    ->first();

                if ($existing) {
                    $existing->update(['quantity' => $existing->quantity + ($data['quantity'] ?? 1)]);
                    $this->results['inventory']['merged']++;
                } else {
                    $dateAcquired = null;
                    if (! empty($data['date_acquired'])) {
                        try {
                            $dateAcquired = Carbon::parse($data['date_acquired'])->toDateString();
                        } catch (\Throwable) {
                        }
                    }

                    Inventory::create([
                        'beer_id' => $beerId,
                        'user_id' => $this->userId,
                        'store_id' => $storeId,
                        'quantity' => $data['quantity'] ?? 1,
                        'storage_location' => $data['storage_location'] ?? null,
                        'is_gift' => $data['is_gift'] ?? false,
                        'date_acquired' => $dateAcquired,
                        'notes' => $data['notes'] ?? null,
                    ]);
                    $this->results['inventory']['created']++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = 'Inventory for "'.($data['beer_name'] ?? '?').'": '.$e->getMessage();
            }
        }
    }

    private function importCollections(array $collections, array $beerMap): void
    {
        foreach ($collections as $data) {
            try {
                $name = $data['name'] ?? null;
                if (! $name) {
                    continue;
                }

                if (Collection::where('user_id', $this->userId)->where('name', $name)->exists()) {
                    $this->results['collections']['existing']++;

                    continue;
                }

                $collection = Collection::create([
                    'user_id' => $this->userId,
                    'name' => $name,
                    'description' => $data['description'] ?? null,
                    'is_dynamic' => $data['is_dynamic'] ?? false,
                    'rules' => $data['rules'] ?? null,
                ]);

                if (! ($data['is_dynamic'] ?? false) && ! empty($data['beers'])) {
                    foreach ($data['beers'] as $beerData) {
                        $beerId = $this->resolveBeerIdFromData($beerData, $beerMap);
                        if ($beerId) {
                            $collection->beers()->attach($beerId, ['sort_order' => $beerData['sort_order'] ?? 0]);
                        }
                    }
                }

                $this->results['collections']['created']++;
            } catch (\Throwable $e) {
                $this->errors[] = 'Collection "'.($data['name'] ?? '?').'": '.$e->getMessage();
            }
        }
    }

    private function backfill($model, array $data, array $fields): void
    {
        $updates = [];
        foreach ($fields as $field) {
            if (empty($model->{$field}) && ! empty($data[$field])) {
                $updates[$field] = $data[$field];
            }
        }
        if ($updates) {
            $model->update($updates);
        }
    }

    private function backfillBeer(Beer $beer, array $data, ?int $breweryId): void
    {
        $updates = [];
        foreach (['pub_uuid', 'catalog_beer_id', 'release_year', 'brewer_master', 'description'] as $field) {
            if (empty($beer->{$field}) && ! empty($data[$field])) {
                $updates[$field] = $data[$field];
            }
        }
        if (! $beer->brewery_id && $breweryId) {
            $updates['brewery_id'] = $breweryId;
        }
        if (empty($beer->style) && ! empty($data['style'])) {
            $updates['style'] = $data['style'];
        }
        if (! $beer->abv && ! empty($data['abv'])) {
            $updates['abv'] = $data['abv'];
        }
        if (! $beer->ibu && ! empty($data['ibu'])) {
            $updates['ibu'] = $data['ibu'];
        }
        if (! empty($data['is_favorite']) && ! $beer->is_favorite) {
            $updates['is_favorite'] = true;
        }
        if ($updates) {
            $beer->update($updates);
        }
    }

    private function resolveBreweryId(array $data, array $breweryMap): ?int
    {
        $pubUuid = $data['brewery_pub_uuid'] ?? null;
        if ($pubUuid && isset($breweryMap['uuid:'.$pubUuid])) {
            return $breweryMap['uuid:'.$pubUuid];
        }

        $name = $data['brewery_name'] ?? null;
        if ($name && isset($breweryMap[$name])) {
            return $breweryMap[$name];
        }

        if ($pubUuid) {
            $brewery = Brewery::where('pub_uuid', $pubUuid)->first();
            if ($brewery) {
                return $brewery->id;
            }
        }
        if ($name) {
            return Brewery::where('name', $name)->first()?->id;
        }

        return null;
    }

    public function resolveBeerIdFromData(array $data, array $beerMap): ?int
    {
        $pubUuid = $data['beer_pub_uuid'] ?? $data['pub_uuid'] ?? null;
        if ($pubUuid && isset($beerMap['uuid:'.$pubUuid])) {
            return $beerMap['uuid:'.$pubUuid];
        }

        $beerName = $data['beer_name'] ?? $data['name'] ?? null;
        if (! $beerName) {
            return null;
        }

        $breweryName = $data['brewery_name'] ?? null;
        if ($breweryName) {
            $brewery = Brewery::where('name', $breweryName)->first();
            $key = $beerName.'|'.($brewery?->id ?? '');
            if (isset($beerMap[$key])) {
                return $beerMap[$key];
            }
        }

        if (isset($beerMap[$beerName.'|'])) {
            return $beerMap[$beerName.'|'];
        }

        if ($pubUuid) {
            $beer = Beer::where('pub_uuid', $pubUuid)->first();
            if ($beer) {
                return $beer->id;
            }
        }

        $query = Beer::where('name', $beerName);
        if ($breweryName) {
            $query->whereHas('brewery', fn ($q) => $q->where('name', $breweryName));
        }

        return $query->first()?->id;
    }
}
