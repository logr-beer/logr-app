<?php

namespace App\Livewire;

use App\Events\CheckinCreated;
use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\CheckinPhoto;
use App\Models\Venue;
use App\Services\CatalogBeer;
use App\Services\LogrDb;
use App\Services\Untappd;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use App\Jobs\GeocodeVenue;
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

    public array $shareTargets = [];

    public bool $useBeerPhoto = true;

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
                $this->selectedBeerName = $b->name.($b->brewery ? ' — '.$b->brewery->name : '');
            }
        }

        $this->buildShareTargets();
    }

    protected function buildShareTargets(): void
    {
        $this->shareTargets = static::buildTargetsForType('publish_checkins');
    }

    public static function buildTargetsForType(string $publishKey): array
    {
        $user = auth()->user();
        $targets = [];

        // Discord webhooks
        foreach ($user->getData('discord_webhooks') ?? [] as $i => $webhook) {
            if (empty($webhook['url'])) {
                continue;
            }
            $targets[] = [
                'key' => "webhook_{$i}",
                'type' => 'discord_webhook',
                'label' => $webhook['label'] ?? 'Discord Webhook',
                'icon' => 'discord',
                'enabled' => ! empty($webhook[$publishKey]),
            ];
        }

        // Discord bots
        $bots = \App\Models\Setting::get('discord_bots', []);
        $prefs = $user->getData('discord_bot_prefs') ?? [];
        foreach ($bots as $i => $bot) {
            $guildId = $bot['guild_id'] ?? null;
            if (! $guildId) {
                continue;
            }
            $targets[] = [
                'key' => "bot_{$i}",
                'type' => 'discord_bot',
                'label' => $bot['guild_name'] ?? 'Discord Bot',
                'icon' => 'discord',
                'enabled' => ! empty($prefs[$guildId][$publishKey]),
            ];
        }

        return $targets;
    }

    protected function loadCheckin(int $id): void
    {
        $checkin = Checkin::with(['beer.brewery', 'venue', 'photos'])->findOrFail($id);

        if ($checkin->user_id !== auth()->id()) {
            abort(403);
        }

        $this->checkinId = $checkin->id;
        $this->selectedBeerId = $checkin->beer_id;
        $this->selectedBeerName = $checkin->beer->name.($checkin->beer->brewery ? ' — '.$checkin->beer->brewery->name : '');
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
            $this->selectedBeerName = $beer->name.($beer->brewery ? ' — '.$beer->brewery->name : '');
            $this->beerQuery = '';
        }
    }

    public function clearBeer(): void
    {
        $this->selectedBeerId = null;
        $this->selectedBeerName = '';
        $this->beerQuery = '';
    }

    public function importAndSelectBeer(string $cacheKey): void
    {
        $data = Cache::get("beer_api_{$cacheKey}");
        if (! $data) {
            return;
        }

        // Find or create brewery
        $breweryId = null;
        $breweryData = $data['brewery'] ?? $data['brewer'] ?? null;
        if (! empty($breweryData['name'])) {
            $brewery = Brewery::firstOrCreate(
                ['name' => $breweryData['name']],
                [
                    'city' => $breweryData['city'] ?? null,
                    'state' => $breweryData['state'] ?? null,
                    'country' => $breweryData['country'] ?? null,
                    'website' => $breweryData['url'] ?? $breweryData['website'] ?? null,
                ]
            );
            $breweryId = $brewery->id;
        }

        // Find or create beer
        $beer = Beer::firstOrCreate(
            ['name' => $data['name'], 'brewery_id' => $breweryId],
            [
                'style' => ! empty($data['style']) ? (is_array($data['style']) ? $data['style'] : [$data['style']]) : null,
                'abv' => ($data['abv'] ?? null) ? (float) $data['abv'] : null,
                'ibu' => ($data['ibu'] ?? null) ? (int) $data['ibu'] : null,
                'description' => $data['description'] ?? null,
            ]
        );

        $this->selectBeer($beer->id);
    }

    private function fetchApiBeerResults(): array
    {
        $user = auth()->user();

        try {
            $logrDb = LogrDb::forUser();
            if ($logrDb) {
                $results = $logrDb->searchBeers($this->beerQuery, 5);
                foreach ($results as &$result) {
                    $result['_source'] = 'logr_db';
                    Cache::put("beer_api_{$result['id']}", array_merge($result, [
                        'brewery' => [
                            'name' => $result['brewery_name'],
                            'city' => $result['brewery_city'],
                            'state' => $result['brewery_state'],
                            'country' => $result['brewery_country'] ?? null,
                            'website' => $result['brewery_website'] ?? null,
                        ],
                    ]), now()->addMinutes(5));
                }

                return $results;
            }

            $untappdKey = $user->untappd_client_id ?: config('services.untappd.api_key');
            $untappdSecret = $user->untappd_client_secret ?: config('services.untappd.api_secret');
            if ($untappdKey && $untappdSecret) {
                $untappd = new Untappd($untappdKey, $untappdSecret);
                $results = $untappd->searchBeers($this->beerQuery, 5);
                foreach ($results as &$result) {
                    $result['_source'] = 'untappd';
                    Cache::put("beer_api_{$result['bid']}", array_merge($result, ['_source' => 'untappd']), now()->addMinutes(5));
                }

                return $results;
            }

            $catalogKey = $user->catalog_beer_api_key ?: config('services.catalog_beer.key');
            if ($catalogKey) {
                $results = app(CatalogBeer::class)->search($this->beerQuery, 5, $catalogKey);
                foreach ($results as &$result) {
                    $result['_source'] = 'catalog';
                    Cache::put("beer_api_{$result['id']}", array_merge($result, ['_source' => 'catalog']), now()->addMinutes(5));
                }

                return $results;
            }

            return [];
        } catch (\Exception $e) {
            \Log::debug('CheckinForm: API beer search failed', ['error' => $e->getMessage()]);

            return [];
        }
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
            'serving_type' => 'nullable|string|in:'.implode(',', config('logr.serving_types')),
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

            if ($venue->wasRecentlyCreated && auth()->user()->getData('geocoding_enabled')) {
                GeocodeVenue::dispatch($venue);
            }
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

            // Use beer photo if selected and no other photos added
            if (! $this->checkinId && $this->useBeerPhoto && empty($this->photos)) {
                $beer = Beer::find($this->selectedBeerId);
                if ($beer?->photo_path && Storage::disk('public')->exists($beer->photo_path)) {
                    $ext = pathinfo($beer->photo_path, PATHINFO_EXTENSION);
                    $newPath = 'checkin-photos/' . uniqid() . '.' . $ext;
                    Storage::disk('public')->copy($beer->photo_path, $newPath);
                    CheckinPhoto::create([
                        'checkin_id' => $checkin->id,
                        'photo_path' => $newPath,
                    ]);
                }
            }

            return $checkin;
        });

        if ($this->checkinId) {
            $message = 'Check-in updated!';
        } else {
            $hasEnabledTarget = collect($this->shareTargets)->contains('enabled', true);
            if ($hasEnabledTarget) {
                CheckinCreated::dispatch($checkin, auth()->user());
            }

            $message = 'Check-in recorded!';
        }

        session()->flash('message', $message);
        $this->redirect(route('checkins.index'), navigate: true);
    }

    public function deleteCheckin(): void
    {
        if (config('app.demo_mode') || ! $this->checkinId) {
            return;
        }

        $checkin = Checkin::where('id', $this->checkinId)->where('user_id', auth()->id())->firstOrFail();
        $checkin->delete();

        session()->flash('message', 'Check-in deleted.');
        $this->redirect(route('checkins.index'), navigate: true);
    }

    public function getSelectedBeerProperty(): ?Beer
    {
        return $this->selectedBeerId ? Beer::find($this->selectedBeerId) : null;
    }

    public function render()
    {
        $beerSuggestions = [];
        $apiResults = [];
        if (strlen($this->beerQuery) >= 2 && ! $this->selectedBeerId) {
            $beerSuggestions = Beer::with('brewery')
                ->search($this->beerQuery)
                ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', ["%{$this->beerQuery}%"])
                ->orderBy('name')
                ->limit(10)
                ->get();

            $apiResults = $this->fetchApiBeerResults();
        }

        $venueSuggestions = [];
        if (strlen($this->venueQuery) >= 2 && ! $this->selectedVenueId) {
            $venueSuggestions = Venue::where('name', 'like', '%'.$this->venueQuery.'%')
                ->orderBy('name')
                ->limit(8)
                ->get();
        }

        return view('livewire.checkin-form', [
            'beerSuggestions' => $beerSuggestions,
            'apiResults' => $apiResults,
            'venueSuggestions' => $venueSuggestions,
        ])->title($this->checkinId ? 'Edit Check-in | Check-ins' : 'New Check-in | Check-ins');
    }
}
