<?php

namespace App\Livewire;

use App\Jobs\GeocodeLocation;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\Inventory;
use App\Models\Store;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class LocationShow extends Component
{
    public Model $location;

    public string $type; // 'venue', 'brewery', 'store'

    public bool $editing = false;

    public string $name = '';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $country = '';

    public string $website = '';

    public ?string $latitude = null;

    public ?string $longitude = null;

    public string $geocodeError = '';

    public array $nameSearchResults = [];

    public int $checkinLimit = 6;

    public int $inventoryLimit = 6;

    public function loadMoreCheckins(): void
    {
        $this->checkinLimit += 6;
    }

    public function loadMoreInventory(): void
    {
        $this->inventoryLimit += 6;
    }

    public function updatedName(): void
    {
        if (strlen($this->name) < 4) {
            $this->nameSearchResults = [];

            return;
        }

        $this->nameSearchResults = $this->searchNominatimForName($this->name);
    }

    public function selectNameResult(int $index): void
    {
        $result = $this->nameSearchResults[$index] ?? null;
        if (! $result) {
            return;
        }

        $this->name = $result['name'];
        $this->address = $result['address'] ?? '';
        $this->city = $result['city'] ?? '';
        $this->state = $result['state'] ?? '';
        $this->country = $result['country'] ?? '';
        $this->latitude = $result['lat'];
        $this->longitude = $result['lon'];
        $this->nameSearchResults = [];
    }

    protected function searchNominatimForName(string $query): array
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

                $results[] = [
                    'name' => $name ?: explode(',', $place['display_name'] ?? '')[0],
                    'address' => $address,
                    'city' => $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? null,
                    'state' => $addr['state'] ?? null,
                    'country' => $addr['country'] ?? null,
                    'lat' => $place['lat'],
                    'lon' => $place['lon'],
                    'display' => $place['display_name'] ?? '',
                ];
            }

            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function mount(Model $location): void
    {
        $this->location = $location;
        $this->type = match (true) {
            $location instanceof Venue => 'venue',
            $location instanceof Brewery => 'brewery',
            $location instanceof Store => 'store',
        };
        $this->fillForm();

        if (request()->query('edit')) {
            $this->editing = true;
        }
    }

    public function fillForm(): void
    {
        $this->name = $this->location->name ?? '';
        $this->address = $this->location->address ?? '';
        $this->city = $this->location->city ?? '';
        $this->state = $this->location->state ?? '';
        $this->country = $this->location->country ?? '';
        $this->website = $this->location->website ?? '';
        $this->latitude = $this->location->latitude;
        $this->longitude = $this->location->longitude;
    }

    public function refreshLocation(): void
    {
        // Clear coordinates directly on DB so the job will re-run,
        // but keep the Livewire model's values so the map stays visible until refresh
        $this->location->newQuery()->where('id', $this->location->id)->update([
            'latitude' => null,
            'longitude' => null,
        ]);

        // Refresh the model so the job sees null coords
        $fresh = $this->location->fresh();
        GeocodeLocation::dispatchSync($fresh);

        // Reload with new data
        $this->location->refresh();
        $this->latitude = $this->location->latitude;
        $this->longitude = $this->location->longitude;
        $this->city = $this->location->city ?? '';
        $this->state = $this->location->state ?? '';
        $this->country = $this->location->country ?? '';

        if ($this->location->latitude) {
            $this->dispatch('toast', message: 'Location updated!');
        } else {
            $this->dispatch('toast', message: 'Could not find coordinates for this location.', type: 'error');
        }
    }

    public function edit(): void
    {
        $this->editing = true;
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->fillForm();
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ];

        $rules['address'] = 'nullable|string|max:255';
        $rules['website'] = 'nullable|string|max:255|url';

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'city' => $this->city ?: null,
            'state' => $this->state ?: null,
            'country' => $this->country ?: null,
            'latitude' => $this->latitude ?: null,
            'longitude' => $this->longitude ?: null,
        ];

        $data['address'] = $this->address ?: null;
        $data['website'] = $this->website ?: null;

        $this->location->update($data);
        $this->location->refresh();
        $this->editing = false;
    }

    public function lookupCoordinates(): void
    {
        $this->geocodeError = '';

        $queries = [];

        $structured = array_filter([
            'street' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
        ]);
        if (! empty($structured)) {
            $queries[] = $structured;
        }

        $allParts = collect([$this->address, $this->city, $this->state, $this->country])->filter()->implode(', ');
        if ($allParts) {
            $queries[] = ['q' => $allParts];
        }

        $cityState = collect([$this->city, $this->state, $this->country])->filter()->implode(', ');
        if ($cityState && $cityState !== $allParts) {
            $queries[] = ['q' => $cityState];
        }

        if ($this->name) {
            $suffix = $this->type === 'brewery' ? ' brewery' : '';
            $queries[] = ['q' => $this->name.$suffix];
        }

        if (empty($queries)) {
            $this->geocodeError = 'Enter an address or name first.';

            return;
        }

        try {
            foreach ($queries as $i => $params) {
                $params['format'] = 'json';
                $params['addressdetails'] = 1;
                $params['limit'] = 1;

                $response = Http::withHeaders([
                    'User-Agent' => config('logr.user_agent'),
                ])
                    ->timeout(10)
                    ->get('https://nominatim.openstreetmap.org/search', $params);

                $results = $response->successful() ? $response->json() : [];

                if (! empty($results)) {
                    $this->latitude = $results[0]['lat'];
                    $this->longitude = $results[0]['lon'];

                    // Backfill city/state/country from result
                    $addr = $results[0]['address'] ?? [];
                    if (! $this->city) {
                        $this->city = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? '';
                    }
                    if (! $this->state) {
                        $this->state = $addr['state'] ?? '';
                    }
                    if (! $this->country) {
                        $this->country = $addr['country'] ?? '';
                    }

                    if ($i >= 2) {
                        $matched = $results[0]['display_name'] ?? '';
                        $this->geocodeError = "Exact address not found. Matched: {$matched}";
                    }

                    return;
                }

                if ($i < count($queries) - 1) {
                    usleep(500000);
                }
            }

            $this->geocodeError = 'No results found. Try adjusting the address or enter coordinates manually.';
        } catch (\Exception $e) {
            $this->geocodeError = 'Geocoding failed: '.$e->getMessage();
        }
    }

    public function delete(): void
    {
        if (config('app.demo_mode')) {
            return;
        }

        // Venue: only allow deletion if no other users have checkins
        if ($this->type === 'venue' && $this->location->checkins()->where('user_id', '!=', auth()->id())->exists()) {
            abort(403);
        }

        $this->location->delete();

        $this->redirect(match ($this->type) {
            'venue' => route('locations.venues'),
            'brewery' => route('locations.breweries'),
            'store' => route('locations.stores'),
        }, navigate: true);
    }

    protected function config(): array
    {
        return match ($this->type) {
            'venue' => [
                'icon' => 'map-pin',
                'backRoute' => route('locations.venues'),
                'backLabel' => 'Back to Venues',
            ],
            'brewery' => [
                'icon' => 'building',
                'backRoute' => route('locations.breweries'),
                'backLabel' => 'Back to Breweries',
            ],
            'store' => [
                'icon' => 'building',
                'backRoute' => route('locations.stores'),
                'backLabel' => 'Back to Stores',
            ],
        };
    }

    public function render()
    {
        $config = $this->config();

        $checkins = collect();
        $inventoryItems = collect();
        $totalCheckins = 0;
        $totalInventory = 0;

        if ($this->type === 'venue') {
            $checkinQuery = $this->location->checkins()->with(['beer.brewery', 'photos'])->orderByDesc('created_at');
            $totalCheckins = $checkinQuery->count();
            $checkins = $checkinQuery->limit($this->checkinLimit)->get();
        } elseif ($this->type === 'brewery') {
            $checkinQuery = Checkin::whereHas('beer', fn ($q) => $q->where('brewery_id', $this->location->id))
                ->with(['beer', 'venue', 'photos'])
                ->orderByDesc('created_at');
            $totalCheckins = $checkinQuery->count();
            $checkins = $checkinQuery->limit($this->checkinLimit)->get();

            $inventoryQuery = Inventory::whereHas('beer', fn ($q) => $q->where('brewery_id', $this->location->id))
                ->with(['beer', 'store'])
                ->where('quantity', '>', 0)
                ->orderByDesc('created_at');
            $totalInventory = $inventoryQuery->count();
            $inventoryItems = $inventoryQuery->limit($this->inventoryLimit)->get();
        } elseif ($this->type === 'store') {
            $inventoryQuery = $this->location->inventory()->with(['beer.brewery'])->orderByDesc('created_at');
            $totalInventory = $inventoryQuery->count();
            $inventoryItems = $inventoryQuery->limit($this->inventoryLimit)->get();
        }

        return view('livewire.location-show', [
            'config' => $config,
            'checkins' => $checkins,
            'inventoryItems' => $inventoryItems,
            'totalCheckins' => $totalCheckins,
            'totalInventory' => $totalInventory,
        ])->title($this->location->name.' | '.$config['backLabel']);
    }
}
