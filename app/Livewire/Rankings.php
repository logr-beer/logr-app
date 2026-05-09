<?php

namespace App\Livewire;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Stats')]
class Rankings extends Component
{
    public int $topRatedLimit = 5;

    public int $mostCheckedInLimit = 5;

    public int $topBreweriesLimit = 5;

    public int $highestAbvLimit = 5;

    public function expandSection(string $section, int $limit): void
    {
        $this->{$section.'Limit'} = $limit;
    }

    public function render()
    {
        $userId = auth()->id();

        // Top rated beers (min 1 check-in)
        $topRated = Beer::select('beers.*', DB::raw('AVG(checkins.rating) as avg_rating'), DB::raw('COUNT(checkins.id) as checkin_count'))
            ->join('checkins', 'beers.id', '=', 'checkins.beer_id')
            ->where('checkins.user_id', $userId)
            ->whereNotNull('checkins.rating')
            ->groupBy('beers.id')
            ->havingRaw('COUNT(checkins.id) >= 1')
            ->orderByDesc('avg_rating')
            ->limit($this->topRatedLimit)
            ->with('brewery')
            ->get();

        // Most checked-in beers
        $mostCheckedIn = Beer::select('beers.*', DB::raw('COUNT(checkins.id) as checkin_count'), DB::raw('AVG(checkins.rating) as avg_rating'))
            ->join('checkins', 'beers.id', '=', 'checkins.beer_id')
            ->where('checkins.user_id', $userId)
            ->groupBy('beers.id')
            ->orderByDesc('checkin_count')
            ->limit($this->mostCheckedInLimit)
            ->with('brewery')
            ->get();

        // Top breweries (by avg rating across all beers, min 3 check-ins)
        $topBreweries = Brewery::select(
            'breweries.*',
            DB::raw('AVG(checkins.rating) as avg_rating'),
            DB::raw('COUNT(DISTINCT beers.id) as beer_count'),
            DB::raw('COUNT(checkins.id) as checkin_count')
        )
            ->join('beers', 'breweries.id', '=', 'beers.brewery_id')
            ->join('checkins', 'beers.id', '=', 'checkins.beer_id')
            ->where('checkins.user_id', $userId)
            ->whereNotNull('checkins.rating')
            ->groupBy('breweries.id')
            ->havingRaw('COUNT(checkins.id) >= 3')
            ->orderByDesc('avg_rating')
            ->limit($this->topBreweriesLimit)
            ->get();

        // Highest ABV
        $highestAbv = Beer::whereNotNull('abv')
            ->where('abv', '>', 0)
            ->whereHas('checkins', fn ($q) => $q->where('user_id', $userId))
            ->orderByDesc('abv')
            ->limit($this->highestAbvLimit)
            ->with('brewery')
            ->get();

        // Style breakdown — top styles by beer count
        $styleBreakdown = Beer::whereHas('checkins', fn ($q) => $q->where('user_id', $userId))
            ->whereNotNull('style')
            ->pluck('style')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(10);

        // Serving type stats
        $servingTypes = Checkin::where('user_id', $userId)
            ->whereNotNull('serving_type')
            ->select('serving_type', DB::raw('COUNT(*) as count'))
            ->groupBy('serving_type')
            ->orderByDesc('count')
            ->get();

        // Check-in activity by month (last 12 months)
        $monthlyActivity = Checkin::where('user_id', $userId)
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(DB::raw("strftime('%Y-%m', created_at) as month"), DB::raw('COUNT(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        // Top venues
        $topVenues = Venue::select('venues.*', DB::raw('COUNT(checkins.id) as checkin_count'))
            ->join('checkins', 'venues.id', '=', 'checkins.venue_id')
            ->where('checkins.user_id', $userId)
            ->groupBy('venues.id')
            ->orderByDesc('checkin_count')
            ->limit(5)
            ->get();

        // Rating distribution
        $ratingDistribution = Checkin::where('user_id', $userId)
            ->whereNotNull('rating')
            ->select(DB::raw('ROUND(rating) as rating_bucket'), DB::raw('COUNT(*) as count'))
            ->groupBy('rating_bucket')
            ->orderBy('rating_bucket')
            ->pluck('count', 'rating_bucket');

        // Country breakdown
        $countryBreakdown = Brewery::select('country', DB::raw('COUNT(*) as count'))
            ->whereHas('beers.checkins', fn ($q) => $q->where('user_id', $userId))
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'country');

        return view('livewire.rankings', [
            'topRated' => $topRated,
            'mostCheckedIn' => $mostCheckedIn,
            'topBreweries' => $topBreweries,
            'highestAbv' => $highestAbv,
            'styleBreakdown' => $styleBreakdown,
            'servingTypes' => $servingTypes,
            'monthlyActivity' => $monthlyActivity,
            'topVenues' => $topVenues,
            'ratingDistribution' => $ratingDistribution,
            'countryBreakdown' => $countryBreakdown,
        ]);
    }
}
