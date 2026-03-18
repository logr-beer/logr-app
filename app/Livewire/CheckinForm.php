<?php

namespace App\Livewire;

use App\Events\CheckinCreated;
use App\Models\Beer;
use App\Models\Checkin;
use App\Models\CheckinPhoto;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class CheckinForm extends Component
{
    use WithFileUploads;

    // Edit mode
    public ?int $checkinId = null;

    // Beer search
    public string $beerQuery = '';
    public ?int $selectedBeerId = null;
    public string $selectedBeerName = '';

    // Checkin fields
    public ?float $rating = null;
    public string $serving_type = '';
    public string $venueQuery = '';
    public ?int $selectedVenueId = null;
    public string $selectedVenueName = '';
    public string $notes = '';
    public $photos = [];
    public array $existingPhotos = [];
    public array $photosToDelete = [];
    public bool $shareCheckinToDiscord = false;

    // Date override (for editing)
    public string $checkin_date = '';

    public function mount(?int $beer = null, ?int $checkin = null): void
    {
        if ($checkin) {
            $this->loadCheckin($checkin);
        } elseif ($beer) {
            $b = Beer::find($beer);
            if ($b) {
                $this->selectedBeerId = $b->id;
                $this->selectedBeerName = $b->name . ($b->brewery ? ' — ' . $b->brewery->name : '');
            }
        }

        $user = auth()->user();
        $webhooks = collect($user->getData('discord_webhooks') ?? []);
        $bots = collect($user->getData('discord_bots') ?? []);
        $this->shareCheckinToDiscord = $webhooks->contains(fn ($w) => !empty($w['publish_checkins']))
            || $bots->contains(fn ($b) => !empty($b['publish_checkins']));
    }

    protected function loadCheckin(int $id): void
    {
        $checkin = Checkin::with(['beer.brewery', 'venue', 'photos'])->findOrFail($id);

        if ($checkin->user_id !== auth()->id()) {
            abort(403);
        }

        $this->checkinId = $checkin->id;
        $this->selectedBeerId = $checkin->beer_id;
        $this->selectedBeerName = $checkin->beer->name . ($checkin->beer->brewery ? ' — ' . $checkin->beer->brewery->name : '');
        $this->rating = $checkin->rating;
        $this->serving_type = $checkin->serving_type ?? '';
        $this->notes = $checkin->notes ?? '';
        $this->checkin_date = $checkin->created_at->format('Y-m-d\TH:i');

        if ($checkin->venue) {
            $this->selectedVenueId = $checkin->venue_id;
            $this->selectedVenueName = $checkin->venue->name;
        } elseif ($checkin->location) {
            $this->venueQuery = $checkin->location;
        }

        $this->existingPhotos = $checkin->photos->map(fn ($p) => [
            'id' => $p->id,
            'path' => $p->photo_path,
        ])->toArray();
    }

    public function selectBeer(int $beerId): void
    {
        $beer = Beer::with('brewery')->find($beerId);
        if ($beer) {
            $this->selectedBeerId = $beer->id;
            $this->selectedBeerName = $beer->name . ($beer->brewery ? ' — ' . $beer->brewery->name : '');
            $this->beerQuery = '';
        }
    }

    public function clearBeer(): void
    {
        $this->selectedBeerId = null;
        $this->selectedBeerName = '';
        $this->beerQuery = '';
    }

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

    public function removePhoto(int $index): void
    {
        $photos = collect($this->photos)->values()->all();
        unset($photos[$index]);
        $this->photos = array_values($photos);
    }

    public function removeExistingPhoto(int $photoId): void
    {
        $this->photosToDelete[] = $photoId;
        $this->existingPhotos = array_values(array_filter(
            $this->existingPhotos,
            fn ($p) => $p['id'] !== $photoId
        ));
    }

    public function submitCheckin(): void
    {
        $this->validate([
            'selectedBeerId' => 'required|exists:beers,id',
            'rating' => 'nullable|numeric|min:0|max:5',
            'serving_type' => 'nullable|string|in:draft,bottle,can,crowler,growler,cask',
            'venueQuery' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'photos.*' => 'nullable|image|max:10240',
            'checkin_date' => 'nullable|date',
        ], [
            'selectedBeerId.required' => 'Please select a beer.',
        ]);

        // Resolve venue
        $venueId = $this->selectedVenueId;
        $locationText = null;

        if (! $venueId && trim($this->venueQuery)) {
            $venue = Venue::firstOrCreate(['name' => trim($this->venueQuery)]);
            $venueId = $venue->id;
            $locationText = $venue->name;
        } elseif ($venueId) {
            $locationText = $this->selectedVenueName;
        }

        $data = [
            'user_id' => auth()->id(),
            'beer_id' => $this->selectedBeerId,
            'rating' => $this->rating,
            'serving_type' => $this->serving_type ?: null,
            'location' => $locationText,
            'venue_id' => $venueId,
            'notes' => $this->notes ?: null,
        ];

        $checkin = DB::transaction(function () use ($data) {
            if ($this->checkinId) {
                $checkin = Checkin::where('id', $this->checkinId)->where('user_id', auth()->id())->firstOrFail();
                $checkin->update($data);

                if ($this->checkin_date) {
                    $checkin->update([
                        'created_at' => $this->checkin_date,
                        'updated_at' => now(),
                    ]);
                }

                // Delete removed photos
                foreach ($this->photosToDelete as $photoId) {
                    $photo = CheckinPhoto::where('id', $photoId)->where('checkin_id', $checkin->id)->first();
                    if ($photo) {
                        Storage::disk('public')->delete($photo->photo_path);
                        $photo->delete();
                    }
                }
            } else {
                $checkin = Checkin::create($data);
            }

            // Save new photos
            if ($this->photos) {
                foreach ($this->photos as $photo) {
                    $path = $photo->store('checkin-photos', 'public');
                    CheckinPhoto::create([
                        'checkin_id' => $checkin->id,
                        'photo_path' => $path,
                    ]);
                }
            }

            return $checkin;
        });

        if ($this->checkinId) {
            $message = 'Check-in updated!';
        } else {
            if ($this->shareCheckinToDiscord) {
                CheckinCreated::dispatch($checkin, auth()->user());
            }

            $message = 'Check-in recorded!';
        }

        session()->flash('message', $message);
        $this->redirect(route('checkins.index'), navigate: true);
    }

    public function deleteCheckin(): void
    {
        if (! $this->checkinId) return;

        $checkin = Checkin::where('id', $this->checkinId)->where('user_id', auth()->id())->firstOrFail();
        $checkin->delete();

        session()->flash('message', 'Check-in deleted.');
        $this->redirect(route('checkins.index'), navigate: true);
    }

    public function render()
    {
        $beerSuggestions = [];
        if (strlen($this->beerQuery) >= 2 && ! $this->selectedBeerId) {
            $beerSuggestions = Beer::with('brewery')
                ->where('name', 'like', '%' . $this->beerQuery . '%')
                ->orWhereHas('brewery', fn ($q) => $q->where('name', 'like', '%' . $this->beerQuery . '%'))
                ->orderBy('name')
                ->limit(10)
                ->get();
        }

        $venueSuggestions = [];
        if (strlen($this->venueQuery) >= 2 && ! $this->selectedVenueId) {
            $venueSuggestions = Venue::where('name', 'like', '%' . $this->venueQuery . '%')
                ->orderBy('name')
                ->limit(8)
                ->get();
        }

        return view('livewire.checkin-form', [
            'beerSuggestions' => $beerSuggestions,
            'venueSuggestions' => $venueSuggestions,
        ])->title($this->checkinId ? 'Edit Check-in | Check-ins' : 'New Check-in | Check-ins');
    }
}
