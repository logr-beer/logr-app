<?php

namespace App\Livewire;

use App\Models\Venue;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Venues')]
class VenueIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortBy = 'checkins';
    public string $view = 'list';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Venue::query()
            ->withCount('checkins');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('city', 'like', '%' . $this->search . '%')
                    ->orWhere('state', 'like', '%' . $this->search . '%');
            });
        }

        $query = match ($this->sortBy) {
            'name' => $query->orderBy('name'),
            'recent' => $query->orderByDesc('updated_at'),
            default => $query->orderByDesc('checkins_count'),
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

        return view('livewire.venue-index', [
            'venues' => $query->paginate(24),
            'mapVenues' => $mapVenues,
        ]);
    }
}
