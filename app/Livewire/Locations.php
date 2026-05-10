<?php

namespace App\Livewire;

use App\Jobs\GeocodeBreweries;
use App\Models\Brewery;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Locations')]
class Locations extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortBy = 'checkins';

    public string $sortDirection = 'desc';

    public string $locationFilter = 'all';

    public bool $geocoding = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLocationFilter(): void
    {
        $this->resetPage();
    }

    public function geocodeBreweries(): void
    {
        if (! auth()->user()->getData('geocoding_enabled')) {
            return;
        }

        $this->geocoding = true;
        Cache::forget('geocoding_breweries_dispatched');
        GeocodeBreweries::dispatch();
    }

    public function mount(): void
    {
        $this->autoGeocode();
    }

    protected function autoGeocode(): void
    {
        if (! auth()->user()->getData('geocoding_enabled')) {
            return;
        }

        $ungeocodedCount = Brewery::geocodable()->count();

        if ($ungeocodedCount > 0 && ! Cache::has('geocoding_breweries_dispatched')) {
            Cache::put('geocoding_breweries_dispatched', true, now()->addMinutes(10));
            GeocodeBreweries::dispatch();
        }
    }

    public function render()
    {
        $mapPoints = Brewery::withCoordinates()
            ->withCount('beers')
            ->withCount(['beers as checkins_total' => function ($q) {
                $q->join('checkins', 'beers.id', '=', 'checkins.beer_id');
            }])
            ->with(['beers' => fn ($q) => $q->select('id', 'name', 'brewery_id')->limit(5)])
            ->get()
            ->map(fn ($brewery) => [
                'id' => $brewery->id,
                'name' => $brewery->name,
                'lat' => (float) $brewery->latitude,
                'lng' => (float) $brewery->longitude,
                'location' => collect([$brewery->city, $brewery->state, $brewery->country])->filter()->implode(', '),
                'beers' => $brewery->beers_count,
                'checkins' => $brewery->checkins_total,
                'beerList' => $brewery->beers->map(fn ($b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                ])->values()->all(),
                'hasMore' => $brewery->beers_count > 5,
            ]);

        $listQuery = Brewery::query()->withCount('beers');

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
            default => $listQuery->orderBy('beers_count', $dir),
        };

        $ungeocodedCount = Brewery::geocodable()->count();

        return view('livewire.locations', [
            'mapPoints' => $mapPoints,
            'listItems' => $listQuery->paginate(24),
            'ungeocodedCount' => $ungeocodedCount,
            'geocodingEnabled' => (bool) auth()->user()->getData('geocoding_enabled'),
        ]);
    }
}
