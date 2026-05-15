<?php

namespace App\Livewire;

use App\Jobs\GeocodeBreweries;
use App\Jobs\GeocodeVenues;
use App\Models\Brewery;
use App\Models\Store;
use App\Models\Venue;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class LocationIndex extends Component
{
    use WithPagination;

    public string $type; // 'brewery', 'venue', 'store'

    public string $search = '';

    public string $sortBy = 'count';

    public string $sortDirection = 'desc';

    public string $locationFilter = 'all';

    public bool $geocoding = false;

    public function mount(string $type): void
    {
        $this->type = $type;

        if ($this->type === 'brewery') {
            $this->autoGeocode();
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLocationFilter(): void
    {
        $this->resetPage();
    }

    public function createNew(): void
    {
        $model = $this->modelClass();
        $location = $model::create(['name' => 'New '.ucfirst($this->type)]);

        $route = match ($this->type) {
            'brewery' => route('breweries.show', $location),
            'venue' => route('venues.show', $location),
            'store' => route('stores.show', $location),
        };

        $this->redirect($route.'?edit=1', navigate: true);
    }

    public function geocodeBatch(): void
    {
        if (! auth()->user()->getData('geocoding_enabled')) {
            return;
        }

        $this->geocoding = true;

        if ($this->type === 'brewery') {
            Cache::forget('geocoding_breweries_dispatched');
            GeocodeBreweries::dispatch();
        } elseif ($this->type === 'venue') {
            Cache::forget('geocoding_venues_dispatched');
            GeocodeVenues::dispatch();
        }
    }

    protected function autoGeocode(): void
    {
        if (! auth()->user()->getData('geocoding_enabled')) {
            return;
        }

        $model = $this->modelClass();
        $ungeocodedCount = $model::geocodable()->count();

        if ($ungeocodedCount > 0 && ! Cache::has('geocoding_breweries_dispatched')) {
            Cache::put('geocoding_breweries_dispatched', true, now()->addMinutes(10));
            GeocodeBreweries::dispatch();
        }
    }

    protected function modelClass(): string
    {
        return match ($this->type) {
            'brewery' => Brewery::class,
            'venue' => Venue::class,
            'store' => Store::class,
        };
    }

    protected function config(): array
    {
        return match ($this->type) {
            'brewery' => [
                'label' => 'Breweries',
                'singular' => 'brewery',
                'icon' => 'building',
                'countRelation' => 'beers',
                'countLabel' => 'beer',
                'sortOptions' => ['count' => 'Beers', 'name' => 'Name', 'recent' => 'Recent'],
                'mapId' => 'location-map',
                'showRoute' => 'breweries.show',
                'emptyTitle' => 'No breweries found.',
                'emptyMessage' => 'Breweries will appear here when you add beers.',
                'canGeocode' => true,
            ],
            'venue' => [
                'label' => 'Venues',
                'singular' => 'venue',
                'icon' => 'map-pin',
                'countRelation' => 'checkins',
                'countLabel' => 'check-in',
                'sortOptions' => ['count' => 'Check-ins', 'name' => 'Name', 'recent' => 'Recent'],
                'mapId' => 'location-map',
                'showRoute' => 'venues.show',
                'emptyTitle' => 'No venues yet',
                'emptyMessage' => 'Venues will appear here when you check in at locations.',
                'canGeocode' => true,
            ],
            'store' => [
                'label' => 'Stores',
                'singular' => 'store',
                'icon' => 'building',
                'countRelation' => 'inventory',
                'countLabel' => 'purchase',
                'sortOptions' => ['count' => 'Purchases', 'name' => 'Name', 'recent' => 'Recent'],
                'mapId' => 'location-map',
                'showRoute' => 'stores.show',
                'emptyTitle' => 'No stores yet',
                'emptyMessage' => 'Stores will appear here when you add beers to your inventory with a store.',
                'canGeocode' => false,
            ],
        };
    }

    public function render()
    {
        $model = $this->modelClass();
        $config = $this->config();

        $countRelation = $config['countRelation'];

        // Map points
        $mapPoints = $model::withCoordinates()
            ->withCount($countRelation)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'lat' => (float) $item->latitude,
                'lng' => (float) $item->longitude,
                'location' => $item->displayLocation(),
                'count' => $item->{$countRelation.'_count'},
            ]);

        // List query
        $listQuery = $model::query()->withCount($countRelation);

        if ($this->locationFilter === 'missing') {
            $listQuery->withoutCoordinates();
        } elseif ($this->locationFilter === 'located') {
            $listQuery->withCoordinates();
        }

        if ($this->search) {
            $listQuery->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('city', 'like', '%'.$this->search.'%')
                    ->orWhere('state', 'like', '%'.$this->search.'%')
                    ->orWhere('country', 'like', '%'.$this->search.'%');
            });
        }

        $dir = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        match ($this->sortBy) {
            'name' => $listQuery->orderBy('name', $dir),
            'recent' => $listQuery->orderBy('updated_at', $dir),
            default => $listQuery->orderBy($countRelation.'_count', $dir),
        };

        $ungeocodedCount = $model::geocodable()->count();
        $geocodingEnabled = (bool) auth()->user()->getData('geocoding_enabled');

        return view('livewire.location-index', [
            'config' => $config,
            'mapPoints' => $mapPoints,
            'listItems' => $listQuery->paginate(24),
            'ungeocodedCount' => $ungeocodedCount,
            'geocodingEnabled' => $geocodingEnabled,
        ])->title($config['label']);
    }
}
