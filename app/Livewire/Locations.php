<?php

namespace App\Livewire;

use App\Models\Brewery;
use App\Models\Venue;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Locations')]
class Locations extends Component
{
    public string $tab = 'breweries'; // breweries, checkins

    public function render()
    {
        $mapBreweries = collect();
        $mapVenues = collect();
        $stats = [];

        if ($this->tab === 'breweries') {
            $mapBreweries = Brewery::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->withCount('beers')
                ->with(['beers' => fn ($q) => $q->withCount('checkins')])
                ->get()
                ->map(function ($brewery) {
                    return [
                        'id' => $brewery->id,
                        'name' => $brewery->name,
                        'lat' => (float) $brewery->latitude,
                        'lng' => (float) $brewery->longitude,
                        'location' => collect([$brewery->city, $brewery->state, $brewery->country])->filter()->implode(', '),
                        'beers' => $brewery->beers_count,
                        'checkins' => $brewery->beers->sum('checkins_count'),
                        'beerList' => $brewery->beers->take(5)->map(fn ($b) => [
                            'id' => $b->id,
                            'name' => $b->name,
                        ])->values()->all(),
                        'hasMore' => $brewery->beers_count > 5,
                    ];
                });

            $totalBreweries = Brewery::count();
            $stats = [
                'mapped' => $mapBreweries->count(),
                'unmapped' => $totalBreweries - $mapBreweries->count(),
            ];
        } else {
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

            $totalVenues = Venue::count();
            $stats = [
                'mapped' => $mapVenues->count(),
                'unmapped' => $totalVenues - $mapVenues->count(),
            ];
        }

        return view('livewire.locations', [
            'mapBreweries' => $mapBreweries,
            'mapVenues' => $mapVenues,
            'stats' => $stats,
        ]);
    }
}
