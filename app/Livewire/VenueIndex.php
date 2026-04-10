<?php

namespace App\Livewire;

use App\Jobs\GeocodeVenues;
use App\Models\Venue;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Venues')]
class VenueIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortBy = 'checkins';

    public string $sortDirection = 'desc';

    public bool $geocoding = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function geocodeVenues(): void
    {
        if (! auth()->user()->getData('geocoding_enabled')) {
            return;
        }

        $this->geocoding = true;
        Cache::forget('geocoding_venues_dispatched');
        GeocodeVenues::dispatch();
    }

    public function render()
    {
        $query = Venue::query()
            ->withCount('checkins');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('city', 'like', '%'.$this->search.'%')
                    ->orWhere('state', 'like', '%'.$this->search.'%');
            });
        }

        $dir = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        $query = match ($this->sortBy) {
            'name' => $query->orderBy('name', $dir),
            'recent' => $query->orderBy('updated_at', $dir),
            default => $query->orderBy('checkins_count', $dir),
        };

        // Get all venues with coordinates for the map (unfiltered)
        $mapVenues = Venue::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->withCount('checkins')
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'lat' => (float) $v->latitude,
                'lng' => (float) $v->longitude,
                'location' => $v->displayLocation(),
                'checkins' => $v->checkins_count,
            ]);

        $ungeocodedCount = Venue::whereNull('latitude')
            ->where(function ($q) {
                $q->whereNotNull('city')
                    ->orWhereNotNull('state')
                    ->orWhereNotNull('name');
            })
            ->count();

        return view('livewire.venue-index', [
            'venues' => $query->paginate(24),
            'mapVenues' => $mapVenues,
            'ungeocodedCount' => $ungeocodedCount,
            'geocodingEnabled' => (bool) auth()->user()->getData('geocoding_enabled'),
        ]);
    }
}
