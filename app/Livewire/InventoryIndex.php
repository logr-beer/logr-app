<?php

namespace App\Livewire;

use App\Models\Inventory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Inventory')]
class InventoryIndex extends Component
{
    public string $search = '';

    public string $location = '';

    public string $sortBy = 'recent';

    public string $sortDirection = 'desc';

    public function updatedSearch()
    {
        // Reset on search change
    }

    public function removeItem(int $inventoryId)
    {
        $item = Inventory::where('user_id', Auth::id())->findOrFail($inventoryId);

        if ($item->quantity > 1) {
            $item->decrement('quantity');
        } else {
            $item->delete();
        }
    }

    public function deleteItem(int $inventoryId)
    {
        if (config('app.demo_mode')) {
            return;
        }

        Inventory::where('user_id', Auth::id())->findOrFail($inventoryId)->delete();
    }

    public function render()
    {
        $userId = Auth::id();

        $locations = Inventory::where('user_id', $userId)
            ->whereNotNull('storage_location')
            ->distinct()
            ->pluck('storage_location')
            ->sort()
            ->values();

        $query = Inventory::where('user_id', $userId)
            ->with('beer.brewery')
            ->where('quantity', '>', 0);

        if ($this->search) {
            $query->whereHas('beer', function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhereHas('brewery', function ($bq) {
                        $bq->where('name', 'like', '%'.$this->search.'%');
                    });
            });
        }

        if ($this->location) {
            $query->where('storage_location', $this->location);
        }

        $dir = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        match ($this->sortBy) {
            'name' => $query->join('beers', 'inventory.beer_id', '=', 'beers.id')->orderBy('beers.name', $dir)->select('inventory.*'),
            'quantity' => $query->orderBy('quantity', $dir),
            'acquired' => $query->orderBy('date_acquired', $dir),
            default => $query->orderBy('updated_at', $dir),
        };

        $items = $query->get();

        $totalItems = Inventory::where('user_id', $userId)->where('quantity', '>', 0)->sum('quantity');
        $totalBeers = Inventory::where('user_id', $userId)->where('quantity', '>', 0)->distinct('beer_id')->count('beer_id');

        // Group by location for summary
        $locationSummary = Inventory::where('user_id', $userId)
            ->where('quantity', '>', 0)
            ->selectRaw("COALESCE(storage_location, 'Unassigned') as loc, SUM(quantity) as total, COUNT(DISTINCT beer_id) as unique_beers")
            ->groupBy('storage_location')
            ->get();

        return view('livewire.inventory-index', [
            'items' => $items,
            'locations' => $locations,
            'totalItems' => $totalItems,
            'totalBeers' => $totalBeers,
            'locationSummary' => $locationSummary,
        ]);
    }
}
