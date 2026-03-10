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
    public string $name = '';
    public string $description = '';

    // Dynamic collection form
    public string $dynamicType = '';
    public string $dynamicStyle = '';
    public ?float $dynamicMinRating = null;

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
            case 'style':
                if (! $this->dynamicStyle) {
                    return;
                }
                $rules = ['style' => $this->dynamicStyle];
                $name = $this->dynamicStyle . ' Collection';
                $description = "All {$this->dynamicStyle} beers.";
                break;
            case 'rating':
                $min = $this->dynamicMinRating ?? 4.0;
                $rules = ['min_rating' => $min];
                $name = $min . '+ Stars';
                $description = "Beers rated {$min} or higher.";
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
            'description' => $description,
            'is_dynamic' => true,
            'rules' => $rules,
        ]);

        $this->reset('dynamicType', 'dynamicStyle', 'dynamicMinRating');
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
                    'name' => $year . ' Check-ins',
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

        $collections = Collection::where('user_id', auth()->id())
            ->where('is_dynamic', false)
            ->withCount('beers')
            ->latest()
            ->get();

        $dynamicCollections = Collection::where('user_id', auth()->id())
            ->where('is_dynamic', true)
            ->latest()
            ->get()
            ->each(function ($collection) {
                $collection->dynamic_count = $collection->resolveBeersCount();
            });

        return view('livewire.collection-index', [
            'collections' => $collections,
            'dynamicCollections' => $dynamicCollections,
        ]);
    }
}
