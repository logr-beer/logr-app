<?php

namespace App\Livewire;

use App\Models\Checkin;
use App\Models\Collection;
use App\Models\Inventory;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Collections')]
class CollectionIndex extends Component
{
    public string $search = '';

    public string $collectionFilter = 'all'; // all, curated, dynamic

    public string $sortBy = 'newest'; // newest, name, count

    public string $sortDirection = 'desc';

    public bool $showCreateModal = false;

    public string $createTab = 'collection'; // collection, dynamic

    public string $name = '';

    public string $description = '';

    // Dynamic collection form
    public string $dynamicType = '';

    public string $dynamicStyle = '';

    public ?float $dynamicMinRating = null;

    public ?int $dynamicYear = null;

    public string $dynamicBrewery = '';

    public ?float $dynamicMinAbv = null;

    public ?float $dynamicMaxAbv = null;

    public string $dynamicServingType = '';

    public string $dynamicVenue = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function createCollection(): void
    {
        $this->validate();

        auth()->user()->collections()->create([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        $this->reset('name', 'description');
        $this->showCreateModal = false;
    }

    public function createDynamicCollection(): void
    {
        if (! $this->dynamicType) {
            return;
        }

        $rules = [];
        $name = '';
        $description = '';

        switch ($this->dynamicType) {
            case 'year':
                if (! $this->dynamicYear) {
                    return;
                }
                $rules = ['year' => (int) $this->dynamicYear];
                $name = $this->dynamicYear.' Check-ins';
                $description = "Beers checked in during {$this->dynamicYear}.";
                break;
            case 'style':
                if (! $this->dynamicStyle) {
                    return;
                }
                $rules = ['style' => $this->dynamicStyle];
                $name = $this->dynamicStyle.' Collection';
                $description = "All {$this->dynamicStyle} beers.";
                break;
            case 'rating':
                $min = $this->dynamicMinRating ?? 4.0;
                $rules = ['min_rating' => $min];
                $name = $min.'+ Stars';
                $description = "Beers rated {$min} or higher.";
                break;
            case 'abv':
                $rules = [];
                $parts = [];
                if ($this->dynamicMinAbv) {
                    $rules['min_abv'] = $this->dynamicMinAbv;
                    $parts[] = "{$this->dynamicMinAbv}%+";
                }
                if ($this->dynamicMaxAbv) {
                    $rules['max_abv'] = $this->dynamicMaxAbv;
                    $parts[] = "up to {$this->dynamicMaxAbv}%";
                }
                if (empty($rules)) {
                    return;
                }
                $name = 'ABV '.implode(' ', $parts);
                $description = 'Beers filtered by ABV range.';
                break;
            case 'brewery':
                if (! $this->dynamicBrewery) {
                    return;
                }
                $rules = ['brewery' => $this->dynamicBrewery];
                $name = $this->dynamicBrewery;
                $description = "All beers from {$this->dynamicBrewery}.";
                break;
            case 'serving_type':
                if (! $this->dynamicServingType) {
                    return;
                }
                $rules = ['serving_type' => $this->dynamicServingType];
                $name = ucfirst($this->dynamicServingType).' Beers';
                $description = "Beers served on {$this->dynamicServingType}.";
                break;
            case 'venue':
                if (! $this->dynamicVenue) {
                    return;
                }
                $rules = ['venue' => $this->dynamicVenue];
                $name = $this->dynamicVenue;
                $description = "Beers checked in at {$this->dynamicVenue}.";
                break;
            case 'favorites':
                $rules = ['favorites' => true];
                $name = 'Favorites';
                $description = 'All your favorite beers.';
                break;
            case 'oldest_in_stock':
                $rules = ['oldest_in_stock' => true];
                $name = 'Oldest in Stock';
                $description = 'Beers in your inventory, oldest first.';
                break;
        }

        auth()->user()->collections()->create([
            'name' => $name,
            'description' => $this->description ?: $description,
            'is_dynamic' => true,
            'rules' => $rules,
        ]);

        $this->reset('dynamicType', 'dynamicStyle', 'dynamicMinRating', 'dynamicYear', 'dynamicBrewery', 'dynamicMinAbv', 'dynamicMaxAbv', 'dynamicServingType', 'dynamicVenue', 'description');
        $this->showCreateModal = false;
    }

    protected function ensureBuiltInCollections(): void
    {
        $userId = auth()->id();

        $years = Checkin::where('user_id', $userId)
            ->selectRaw('DISTINCT strftime("%Y", created_at) as year')
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->toArray();

        foreach ($years as $year) {
            Collection::firstOrCreate(
                [
                    'user_id' => $userId,
                    'is_dynamic' => true,
                    'rules->year' => (int) $year,
                ],
                [
                    'name' => $year.' Check-ins',
                    'description' => "Beers checked in during {$year}.",
                    'rules' => ['year' => (int) $year],
                ]
            );
        }

        // Auto-create collections for each unique inventory storage location
        $locations = Inventory::where('user_id', $userId)
            ->where('quantity', '>', 0)
            ->whereNotNull('storage_location')
            ->where('storage_location', '!=', '')
            ->distinct()
            ->pluck('storage_location');

        foreach ($locations as $location) {
            Collection::firstOrCreate(
                [
                    'user_id' => $userId,
                    'is_dynamic' => true,
                    'rules->storage_location' => $location,
                ],
                [
                    'name' => $location,
                    'description' => "Beers currently stored in {$location}.",
                    'rules' => ['storage_location' => $location],
                ]
            );
        }

        // Clean up storage location collections where no beers remain in that location
        Collection::where('user_id', $userId)
            ->where('is_dynamic', true)
            ->whereNotNull('rules->storage_location')
            ->get()
            ->each(function ($collection) {
                if ($collection->resolveBeersCount() === 0) {
                    $collection->delete();
                }
            });
    }

    public function render()
    {
        $this->ensureBuiltInCollections();

        $query = Collection::where('user_id', auth()->id());

        if ($this->search) {
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        $collections = (clone $query)->where('is_dynamic', false)
            ->withCount('beers')->get();

        $dynamicCollections = (clone $query)->where('is_dynamic', true)
            ->get()
            ->each(function ($collection) {
                $collection->dynamic_count = $collection->resolveBeersCount();
            });

        // Apply sorting
        $asc = $this->sortDirection === 'asc';
        $sortCollection = function ($items) use ($asc) {
            $sorted = match ($this->sortBy) {
                'name' => $asc
                    ? $items->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                    : $items->sortByDesc('name', SORT_NATURAL | SORT_FLAG_CASE),
                'count' => $asc
                    ? $items->sortBy(fn ($c) => $c->dynamic_count ?? $c->beers_count)
                    : $items->sortByDesc(fn ($c) => $c->dynamic_count ?? $c->beers_count),
                default => $asc
                    ? $items->sortBy('created_at')
                    : $items->sortByDesc('created_at'),
            };

            return $sorted->values();
        };

        $collections = $sortCollection($collections);
        $dynamicCollections = $sortCollection($dynamicCollections);

        return view('livewire.collection-index', [
            'collections' => $collections,
            'dynamicCollections' => $dynamicCollections,
        ]);
    }
}
