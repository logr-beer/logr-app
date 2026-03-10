<?php

namespace App\Livewire;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Rankings')]
class Rankings extends Component
{
    public function render()
    {
        $userId = auth()->id();

        // Top rated beers (min 2 check-ins)
        $topRated = Beer::select('beers.*')
            ->join('checkins', 'beers.id', '=', 'checkins.beer_id')
            ->where('checkins.user_id', $userId)
            ->whereNotNull('checkins.rating')
            ->groupBy('beers.id')
            ->havingRaw('COUNT(checkins.id) >= 2')
            ->orderByRaw('AVG(checkins.rating) DESC')
            ->limit(10)
            ->with('brewery')
            ->get()
            ->each(function ($beer) use ($userId) {
                $beer->avg_rating = Checkin::where('beer_id', $beer->id)
                    ->where('user_id', $userId)
                    ->whereNotNull('rating')
                    ->avg('rating');
                $beer->checkin_count = Checkin::where('beer_id', $beer->id)
                    ->where('user_id', $userId)
                    ->count();
            });

        // Most checked-in beers
        $mostCheckedIn = Beer::select('beers.*', DB::raw('COUNT(checkins.id) as checkin_count'))
            ->join('checkins', 'beers.id', '=', 'checkins.beer_id')
            ->where('checkins.user_id', $userId)
            ->groupBy('beers.id')
            ->orderByDesc('checkin_count')
            ->limit(10)
            ->with('brewery')
            ->get()
            ->each(function ($beer) use ($userId) {
                $beer->avg_rating = Checkin::where('beer_id', $beer->id)
                    ->where('user_id', $userId)
                    ->whereNotNull('rating')
                    ->avg('rating');
            });

        // Top breweries (by avg rating across all beers, min 3 check-ins)
        $topBreweries = Brewery::select('breweries.*')
            ->join('beers', 'breweries.id', '=', 'beers.brewery_id')
            ->join('checkins', 'beers.id', '=', 'checkins.beer_id')
            ->where('checkins.user_id', $userId)
            ->whereNotNull('checkins.rating')
            ->groupBy('breweries.id')
            ->havingRaw('COUNT(checkins.id) >= 3')
            ->orderByRaw('AVG(checkins.rating) DESC')
            ->limit(10)
            ->get()
            ->each(function ($brewery) use ($userId) {
                $brewery->avg_rating = Checkin::join('beers', 'checkins.beer_id', '=', 'beers.id')
                    ->where('beers.brewery_id', $brewery->id)
                    ->where('checkins.user_id', $userId)
                    ->whereNotNull('checkins.rating')
                    ->avg('checkins.rating');
                $brewery->beer_count = Beer::where('brewery_id', $brewery->id)->count();
                $brewery->checkin_count = Checkin::join('beers', 'checkins.beer_id', '=', 'beers.id')
                    ->where('beers.brewery_id', $brewery->id)
                    ->where('checkins.user_id', $userId)
                    ->count();
            });

        // Highest ABV
        $highestAbv = Beer::whereNotNull('abv')
            ->where('abv', '>', 0)
            ->whereHas('checkins', fn ($q) => $q->where('user_id', $userId))
            ->orderByDesc('abv')
            ->limit(10)
            ->with('brewery')
            ->get();

        return view('livewire.rankings', [
            'topRated' => $topRated,
            'mostCheckedIn' => $mostCheckedIn,
            'topBreweries' => $topBreweries,
            'highestAbv' => $highestAbv,
        ]);
    }
}
