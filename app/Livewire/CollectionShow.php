<?php

namespace App\Livewire;

use App\Models\Beer;
use App\Models\Collection;
use Livewire\Component;

class CollectionShow extends Component
{
    public Collection $collection;

    public string $addBeerSearch = '';

    public array $addBeerResults = [];

    // Edit form
    public bool $editing = false;

    public string $editName = '';

    public string $editDescription = '';

    public function mount(Collection $collection): void
    {
        $this->collection = $collection;
    }

    public function searchBeers(): void
    {
        if (strlen($this->addBeerSearch) < 2) {
            $this->addBeerResults = [];

            return;
        }

        $this->addBeerResults = Beer::where('name', 'like', "%{$this->addBeerSearch}%")
            ->whereNotIn('id', $this->collection->beers()->pluck('beers.id'))
            ->with('brewery')
            ->limit(10)
            ->get()
            ->map(fn (Beer $beer) => [
                'id' => $beer->id,
                'name' => $beer->name,
                'brewery' => $beer->brewery?->name ?? 'Unknown Brewery',
            ])
            ->toArray();
    }

    public function addBeer(int $beerId): void
    {
        $maxSort = $this->collection->beers()->max('sort_order') ?? 0;
        $this->collection->beers()->attach($beerId, ['sort_order' => $maxSort + 1]);

        $this->addBeerSearch = '';
        $this->addBeerResults = [];
    }

    public function removeBeer(int $beerId): void
    {
        $this->collection->beers()->detach($beerId);
    }

    public function startEditing(): void
    {
        $this->editName = $this->collection->name;
        $this->editDescription = $this->collection->description ?? '';
        $this->editing = true;
    }

    public function cancelEditing(): void
    {
        $this->editing = false;
    }

    public function updateCollection(): void
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editDescription' => 'nullable|string|max:1000',
        ]);

        $this->collection->update([
            'name' => $this->editName,
            'description' => $this->editDescription ?: null,
        ]);

        $this->collection->refresh();
        $this->editing = false;
    }

    public function deleteCollection(): void
    {
        if (config('app.demo_mode')) {
            return;
        }

        $this->collection->beers()->detach();
        $this->collection->delete();

        $this->redirect(route('collections.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.collection-show', [
            'beers' => $this->collection->resolveBeers(),
            'isDynamic' => $this->collection->is_dynamic,
        ])->title($this->collection->name.' | Collections');
    }
}
