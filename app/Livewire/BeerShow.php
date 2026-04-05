<?php

namespace App\Livewire;

use App\Models\Beer;
use App\Models\Checkin;
use App\Models\CheckinPhoto;
use App\Models\Collection;
use App\Models\Inventory;
use App\Models\Venue;
use App\Services\Discord;
use App\Services\Hub;
use Livewire\Component;
use Livewire\WithFileUploads;

class BeerShow extends Component
{
    use WithFileUploads;
    public Beer $beer;

    // Fridge form properties
    public string $storageLocation = 'Fridge';
    public string $purchaseLocation = '';
    public string $purchaseDate = '';
    public int $addQuantity = 1;
    public bool $isGift = false;

    // Collection form
    public ?int $selectedCollectionId = null;

    // Checkin form properties
    public ?float $rating = null;
    public string $serving_type = '';
    public string $venueQuery = '';
    public ?int $selectedVenueId = null;
    public string $selectedVenueName = '';
    public string $notes = '';
    public $checkinPhotos = [];
    public bool $shareCheckinToDiscord = false;
    public bool $sharePurchaseToDiscord = false;

    public function mount(Beer $beer): void
    {
        $this->beer = $beer;
        $user = auth()->user();
        $webhooks = collect($user->getData('discord_webhooks') ?? []);
        $this->shareCheckinToDiscord = $webhooks->contains(fn ($w) => !empty($w['publish_checkins']))
            || \App\Services\Hub::hasPublishing($user, 'publish_checkins');
        $this->sharePurchaseToDiscord = $webhooks->contains(fn ($w) => !empty($w['publish_purchases']))
            || \App\Services\Hub::hasPublishing($user, 'publish_purchases');
    }

    public function toggleFavorite(): void
    {
        $this->beer->update(['is_favorite' => !$this->beer->is_favorite]);
        $this->beer->refresh();
    }

    // -- Fridge / Inventory --

    public function addToFridge(): void
    {
        $location = trim($this->storageLocation) ?: 'Fridge';

        Collection::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'is_dynamic' => true,
                'rules->storage_location' => $location,
            ],
            [
                'name' => $location,
                'rules' => ['storage_location' => $location],
            ]
        );

        $inventory = Inventory::firstOrCreate(
            [
                'beer_id' => $this->beer->id,
                'user_id' => auth()->id(),
                'storage_location' => $location,
            ],
            ['quantity' => 0]
        );

        $inventory->increment('quantity', $this->addQuantity);

        $updates = [];
        if ($this->purchaseLocation) {
            $updates['purchase_location'] = $this->purchaseLocation;
        }
        if ($this->purchaseDate) {
            $updates['date_acquired'] = $this->purchaseDate;
        }
        $updates['is_gift'] = $this->isGift;
        if ($updates) {
            $inventory->update($updates);
        }

        if ($this->sharePurchaseToDiscord) {
            $freshInventory = $inventory->fresh();
            $currentUser = auth()->user();
            Discord::sendPurchase($freshInventory, $currentUser);
            Hub::sendPurchase($freshInventory, $currentUser);
        }

        $this->reset(['purchaseLocation', 'purchaseDate', 'isGift']);
        $this->addQuantity = 1;
    }

    public function removeFromFridge(int $inventoryId): void
    {
        $inventory = Inventory::where('id', $inventoryId)
            ->where('user_id', auth()->id())
            ->first();

        if ($inventory && $inventory->quantity > 1) {
            $inventory->decrement('quantity');
        } elseif ($inventory) {
            $inventory->delete();
        }
    }

    // -- Collections --

    public function addToCollection(): void
    {
        if (! $this->selectedCollectionId) {
            return;
        }

        $collection = Collection::where('id', $this->selectedCollectionId)
            ->where('user_id', auth()->id())
            ->first();

        if ($collection && ! $collection->beers()->where('beer_id', $this->beer->id)->exists()) {
            $maxSort = $collection->beers()->max('sort_order') ?? 0;
            $collection->beers()->attach($this->beer->id, ['sort_order' => $maxSort + 1]);
        }

        $this->selectedCollectionId = null;
    }

    public function removeFromCollection(int $collectionId): void
    {
        $collection = Collection::where('id', $collectionId)
            ->where('user_id', auth()->id())
            ->first();

        if ($collection) {
            $collection->beers()->detach($this->beer->id);
        }
    }

    // -- Delete Beer --

    public function deleteBeer(): void
    {
        $this->beer->checkins()->delete();
        $this->beer->inventory()->delete();
        $this->beer->collections()->detach();
        $this->beer->tags()->detach();

        if ($this->beer->photo_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($this->beer->photo_path);
        }

        $this->beer->delete();

        session()->flash('message', 'Beer deleted successfully.');
        $this->redirect(route('beers.index'), navigate: true);
    }

    // -- Check-in / Venue --

    public function selectVenue(int $venueId): void
    {
        $venue = Venue::find($venueId);
        if ($venue) {
            $this->selectedVenueId = $venue->id;
            $this->selectedVenueName = $venue->name;
            $this->venueQuery = '';
        }
    }

    public function clearVenue(): void
    {
        $this->selectedVenueId = null;
        $this->selectedVenueName = '';
        $this->venueQuery = '';
    }

    public function removeCheckinPhoto(int $index): void
    {
        $photos = collect($this->checkinPhotos)->values()->all();
        unset($photos[$index]);
        $this->checkinPhotos = array_values($photos);
    }

    public function submitCheckin(): void
    {
        $validated = $this->validate([
            'rating' => 'nullable|numeric|min:0|max:5',
            'serving_type' => 'nullable|string|in:draft,bottle,can,crowler,growler,cask',
            'venueQuery' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'checkinPhotos.*' => 'nullable|image|max:10240',
        ]);

        // Resolve venue: use selected, or auto-create from typed query
        $venueId = $this->selectedVenueId;
        $locationText = null;

        if (! $venueId && trim($this->venueQuery)) {
            $venue = Venue::firstOrCreate(['name' => trim($this->venueQuery)]);
            $venueId = $venue->id;
            $locationText = $venue->name;
        } elseif ($venueId) {
            $locationText = $this->selectedVenueName;
        }

        $checkin = Checkin::create([
            'user_id' => auth()->id(),
            'beer_id' => $this->beer->id,
            'rating' => $validated['rating'],
            'serving_type' => $validated['serving_type'] ?: null,
            'location' => $locationText,
            'venue_id' => $venueId,
            'notes' => $validated['notes'] ?: null,
        ]);

        // Save photos
        if ($this->checkinPhotos) {
            foreach ($this->checkinPhotos as $photo) {
                $path = $photo->store('checkin-photos', 'public');
                CheckinPhoto::create([
                    'checkin_id' => $checkin->id,
                    'photo_path' => $path,
                ]);
            }
        }

        if ($this->shareCheckinToDiscord) {
            $currentUser = auth()->user();
            Discord::sendCheckin($checkin, $currentUser);
            Hub::sendCheckin($checkin, $currentUser);
        }

        $this->reset(['rating', 'serving_type', 'venueQuery', 'selectedVenueId', 'selectedVenueName', 'notes', 'checkinPhotos']);

        session()->flash('message', 'Check-in recorded!');
    }

    public function render()
    {
        $inventoryItems = Inventory::where('beer_id', $this->beer->id)
            ->where('user_id', auth()->id())
            ->where('quantity', '>', 0)
            ->orderBy('storage_location')
            ->get();

        $totalQty = $inventoryItems->sum('quantity');

        $beerCollections = $this->beer->collections()
            ->where('user_id', auth()->id())
            ->get();

        $storageLocations = Collection::where('user_id', auth()->id())
            ->where('is_dynamic', true)
            ->whereNotNull('rules->storage_location')
            ->orderBy('name')
            ->pluck('name');

        $availableCollections = Collection::where('user_id', auth()->id())
            ->whereNotIn('id', $beerCollections->pluck('id'))
            ->orderBy('name')
            ->get();

        $venueSuggestions = [];
        if (strlen($this->venueQuery) >= 2 && ! $this->selectedVenueId) {
            $venueSuggestions = Venue::where('name', 'like', '%' . $this->venueQuery . '%')
                ->orderBy('name')
                ->limit(8)
                ->get();
        }

        return view('livewire.beer-show', [
            'checkins' => $this->beer->checkins()->with(['user', 'venue'])->latest()->get(),
            'venueSuggestions' => $venueSuggestions,
            'averageRating' => $this->beer->averageRating(),
            'totalCheckins' => $this->beer->checkins()->count(),
            'inventoryItems' => $inventoryItems,
            'totalQty' => $totalQty,
            'beerCollections' => $beerCollections,
            'availableCollections' => $availableCollections,
            'storageLocations' => $storageLocations,
        ])->title($this->beer->name . ' | Beers');
    }
}
