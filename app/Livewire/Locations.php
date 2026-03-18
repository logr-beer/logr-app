<?php

namespace App\Livewire;

use App\Models\Brewery;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Locations')]
class Locations extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'checkins'; // checkins, breweries

    public string $view = 'map';
    public string $search = '';
    public string $sortBy = 'checkins';

    public function updatedTab(): void
    {
        $this->resetPage();
        $this->search = '';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        if ($this->tab === 'breweries') {
            return $this->renderBreweries();
        }

        return $this->renderVenues();
    }

    protected function renderBreweries()
    {
        $mapPoints = Brewery::whereNotNull('latitude')
            ->whereNotNull('longitude')
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
        $this->applySearch($listQuery, ['name', 'city', 'state', 'country']);
        $this->applySort($listQuery, 'beers_count');

        $totalBreweries = Brewery::count();

        return view('livewire.locations', [
            'mapPoints' => $mapPoints,
            'listItems' => $listQuery->paginate(24),
            'stats' => [
                'mapped' => $mapPoints->count(),
                'unmapped' => $totalBreweries - $mapPoints->count(),
            ],
        ]);
    }

    protected function renderVenues()
    {
        $mapPoints = Venue::whereNotNull('latitude')
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

        $listQuery = Venue::query()->withCount('checkins');
        $this->applySearch($listQuery, ['name', 'city', 'state']);
        $this->applySort($listQuery, 'checkins_count');

        $totalVenues = Venue::count();

        return view('livewire.locations', [
            'mapPoints' => $mapPoints,
            'listItems' => $listQuery->paginate(24),
            'stats' => [
                'mapped' => $mapPoints->count(),
                'unmapped' => $totalVenues - $mapPoints->count(),
            ],
        ]);
    }

    protected function applySearch(Builder $query, array $columns): void
    {
        if (! $this->search) {
            return;
        }

        $query->where(function ($q) use ($columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', '%' . $this->search . '%');
            }
        });
    }

    protected function applySort(Builder $query, string $countColumn): void
    {
        match ($this->sortBy) {
            'name' => $query->orderBy('name'),
            'recent' => $query->orderByDesc('updated_at'),
            default => $query->orderByDesc($countColumn),
        };
    }
}
