<?php

namespace App\Livewire;

use App\Models\Beer;
use App\Models\Checkin;
use App\Models\Collection;
use App\Models\Inventory;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Home')]
class Dashboard extends Component
{
    public function toggleFavorite(int $beerId): void
    {
        $beer = Beer::findOrFail($beerId);
        $beer->update(['is_favorite' => ! $beer->is_favorite]);
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.dashboard', [
            'stats' => [
                'total_checkins' => Checkin::count(),
                'library_count' => Beer::count(),
                'in_fridge' => Inventory::where('quantity', '>', 0)->sum('quantity'),
                'avg_rating' => Checkin::whereNotNull('rating')->avg('rating'),
            ],
            'recentBeers' => Beer::with('brewery')->latest()->take(12)->get(),
            'recentCheckins' => Beer::with('brewery')
                ->whereHas('checkins')
                ->orderByDesc(
                    Checkin::select('created_at')
                        ->whereColumn('beer_id', 'beers.id')
                        ->latest()
                        ->limit(1)
                )
                ->take(12)
                ->get(),
            'favorites' => Beer::with('brewery')
                ->where('is_favorite', true)
                ->latest()
                ->take(12)
                ->get(),
            'collections' => Collection::withCount('beers')
                ->where('user_id', $user->id)
                ->latest()
                ->get(),
        ]);
    }
}
