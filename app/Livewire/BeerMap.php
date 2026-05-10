<?php

namespace App\Livewire;

use App\Models\Brewery;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Beer Map')]
class BeerMap extends Component
{
    public string $search = '';

    public string $colorBy = 'checkins'; // checkins, beers

    public function render()
    {
        // Get breweries with coordinates and their beer/checkin counts
        $mapBreweries = Brewery::withCoordinates()
            ->withCount('beers')
            ->with(['beers' => fn ($q) => $q->withCount('checkins')])
            ->get()
            ->map(function ($brewery) {
                $totalCheckins = $brewery->beers->sum('checkins_count');

                return [
                    'id' => $brewery->id,
                    'name' => $brewery->name,
                    'lat' => (float) $brewery->latitude,
                    'lng' => (float) $brewery->longitude,
                    'location' => collect([$brewery->city, $brewery->state, $brewery->country])->filter()->implode(', '),
                    'beers' => $brewery->beers_count,
                    'checkins' => $totalCheckins,
                ];
            });

        // Stats
        $totalBreweries = Brewery::count();
        $mappedBreweries = $mapBreweries->count();
        $unmappedBreweries = $totalBreweries - $mappedBreweries;

        return view('livewire.beer-map', [
            'mapBreweries' => $mapBreweries,
            'totalBreweries' => $totalBreweries,
            'mappedBreweries' => $mappedBreweries,
            'unmappedBreweries' => $unmappedBreweries,
        ]);
    }
}
