<?php

namespace App\Livewire;

use App\Models\Beer;
use App\Models\Checkin;
use App\Models\Collection;
use App\Models\Inventory;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Beers')]
class BeerIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $style = '';

    public string $sortBy = 'newest';

    public string $sortDirection = 'desc';

    public array $selected = [];

    // Modal state
    public bool $showCollectionModal = false;

    public bool $showInventoryModal = false;

    public string $inventoryLocation = 'Fridge';

    public int $inventoryQuantity = 1;

    protected $queryString = [
        'search' => ['except' => ''],
        'style' => ['except' => ''],
        'sortBy' => ['except' => 'newest'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStyle(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    public function updatingSortDirection(): void
    {
        $this->resetPage();
    }

    public function toggleSelected(int $beerId): void
    {
        if (in_array($beerId, $this->selected)) {
            $this->selected = array_values(array_diff($this->selected, [$beerId]));
        } else {
            $this->selected[] = $beerId;
        }
    }

    public function selectAll(): void
    {
        $this->selected = $this->getCurrentQuery()->pluck('id')->all();
    }

    public function deselectAll(): void
    {
        $this->selected = [];
    }

    public function deleteSelected(): void
    {
        if (config('app.demo_mode')) {
            return;
        }

        Beer::whereIn('id', $this->selected)->delete();
        Checkin::whereIn('beer_id', $this->selected)->delete();
        $this->selected = [];
    }

    public function openCollectionModal(): void
    {
        $this->showCollectionModal = true;
    }

    public function addSelectedToCollection(int $collectionId): void
    {
        $collection = Collection::where('user_id', auth()->id())->findOrFail($collectionId);
        $existing = $collection->beers()->pluck('beers.id')->all();
        $maxSort = $collection->beers()->max('sort_order') ?? 0;

        foreach ($this->selected as $beerId) {
            if (! in_array($beerId, $existing)) {
                $maxSort++;
                $collection->beers()->attach($beerId, ['sort_order' => $maxSort]);
            }
        }

        $this->showCollectionModal = false;
        $this->selected = [];
    }

    public function openInventoryModal(): void
    {
        $this->inventoryLocation = 'Fridge';
        $this->inventoryQuantity = 1;
        $this->showInventoryModal = true;
    }

    public function addSelectedToInventory(): void
    {
        $userId = auth()->id();

        foreach ($this->selected as $beerId) {
            $existing = Inventory::where('user_id', $userId)
                ->where('beer_id', $beerId)
                ->where('storage_location', $this->inventoryLocation)
                ->first();

            if ($existing) {
                $existing->increment('quantity', $this->inventoryQuantity);
            } else {
                Inventory::create([
                    'beer_id' => $beerId,
                    'user_id' => $userId,
                    'quantity' => $this->inventoryQuantity,
                    'storage_location' => $this->inventoryLocation,
                    'date_acquired' => now()->toDateString(),
                ]);
            }
        }

        $this->showInventoryModal = false;
        $this->selected = [];
    }

    public function toggleFavorite(int $beerId): void
    {
        $beer = Beer::findOrFail($beerId);
        $beer->update(['is_favorite' => ! $beer->is_favorite]);
    }

    public function render()
    {
        $collections = Collection::where('user_id', auth()->id())
            ->where('is_dynamic', false)
            ->orderBy('name')
            ->get();

        return view('livewire.beer-index', [
            'beers' => $this->getCurrentQuery()->paginate(24),
            'styles' => $this->getStyles(),
            'collections' => $collections,
        ]);
    }

    protected function getCurrentQuery()
    {
        $query = Beer::with('brewery');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhereHas('brewery', fn ($b) => $b->where('name', 'like', '%'.$this->search.'%'));
            });
        }

        if ($this->style) {
            $query->whereJsonContains('style', $this->style);
        }

        $avgRatingSub = Checkin::selectRaw('AVG(rating)')
            ->whereColumn('beer_id', 'beers.id')
            ->whereNotNull('rating');

        $dir = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        return match ($this->sortBy) {
            'name' => $query->orderBy('name', $dir),
            'abv' => $query->orderBy('abv', $dir),
            'rating' => $dir === 'desc' ? $query->orderByDesc($avgRatingSub) : $query->orderBy($avgRatingSub),
            default => $dir === 'desc' ? $query->latest() : $query->oldest(),
        };
    }

    private function getStyles(): array
    {
        return [
            'IPA', 'Double IPA', 'Hazy IPA', 'Session IPA',
            'Pale Ale', 'American Pale Ale',
            'Lager', 'Pilsner', 'Helles',
            'Stout', 'Imperial Stout', 'Milk Stout', 'Pastry Stout',
            'Porter', 'Brown Ale', 'Amber Ale', 'Red Ale',
            'Wheat Beer', 'Hefeweizen', 'Witbier',
            'Belgian Blonde', 'Belgian Dubbel', 'Belgian Tripel', 'Belgian Quad',
            'Saison', 'Farmhouse Ale',
            'Sour', 'Gose', 'Berliner Weisse', 'Lambic', 'Gueuze',
            'Fruit Beer', 'Barleywine', 'Scotch Ale',
            'ESB', 'Bitter', 'Mild', 'Kölsch', 'Altbier',
            'Bock', 'Doppelbock', 'Märzen', 'Dunkel', 'Schwarzbier', 'Rauchbier',
            'Cream Ale', 'Blonde Ale', 'Golden Ale',
            'Cider', 'Mead', 'Seltzer', 'Other',
        ];
    }
}
