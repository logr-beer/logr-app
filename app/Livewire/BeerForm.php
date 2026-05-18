<?php

namespace App\Livewire;

use App\Concerns\WithLocationAutocomplete;
use App\Events\CheckinCreated;
use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\Inventory;
use App\Models\Store;
use App\Models\Venue;
use App\Services\CatalogBeer;
use App\Services\OpenBreweryDb;
use App\Services\PubBeerDb;
use App\Services\Untappd;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithFileUploads;

class BeerForm extends Component
{
    use WithFileUploads;
    use WithLocationAutocomplete;

    public ?Beer $beer = null;

    public string $name = '';

    public ?int $brewery_id = null;

    public string $brewerySearch = '';

    public bool $showBreweryDropdown = false;

    public array $style = [];

    public ?float $abv = null;

    public ?int $ibu = null;

    public ?int $release_year = null;

    public string $brewer_master = '';

    public string $description = '';

    public $photo;

    // Inventory (add form only)
    public bool $addToInventory = false;

    public string $storageLocation = 'Fridge';

    public int $addQuantity = 1;

    public string $storeQuery = '';

    public ?int $selectedStoreId = null;

    public string $selectedStoreName = '';

    public string $purchaseDate = '';

    public bool $isGift = false;

    // Check-in (add form only)
    public bool $addCheckin = false;

    public ?float $checkinRating = null;

    public string $checkinServingType = '';

    public string $venueQuery = '';

    public ?int $selectedVenueId = null;

    public string $selectedVenueName = '';

    public string $checkinNotes = '';

    public $checkinPhotos = [];

    public bool $useBeerPhoto = true;

    public array $shareTargets = [];

    public array $inventoryShareTargets = [];

    // Beer search
    public string $beerSearchSource = '';

    public bool $showBeerDropdown = false;

    public int $beerApiLimit = 6;

    public array $beerResults = ['local' => [], 'localTotal' => 0, 'api' => []];

    // Brewery search results
    public array $breweryResults = ['local' => [], 'api' => []];

    public function mount(?Beer $beer = null): void
    {
        if ($beer && $beer->exists) {
            $this->beer = $beer;
            $this->name = $beer->name;
            $this->brewery_id = $beer->brewery_id;
            $this->brewerySearch = $beer->brewery?->name ?? '';
            $this->style = $beer->style ?? [];
            $this->abv = $beer->abv;
            $this->ibu = $beer->ibu;
            $this->release_year = $beer->release_year;
            $this->brewer_master = $beer->brewer_master ?? '';
            $this->description = $beer->description ?? '';
        }

        $this->shareTargets = CheckinForm::buildTargetsForType('publish_checkins');
        $this->inventoryShareTargets = CheckinForm::buildTargetsForType('publish_purchases');
    }

    // -- Beer search --

    public function updatedName(): void
    {
        // Only search on add form, not edit
        if ($this->beer && $this->beer->exists) {
            return;
        }

        $this->beerApiLimit = 6;

        if (strlen($this->name) < 2) {
            $this->showBeerDropdown = false;
            $this->beerResults = ['local' => [], 'localTotal' => 0, 'api' => []];

            return;
        }

        $this->showBeerDropdown = true;
        $this->beerResults = $this->fetchBeerResults();
    }

    public function loadMoreBeerResults(): void
    {
        $this->beerApiLimit += 10;
        $this->beerResults = $this->fetchBeerResults();
    }

    public function getAvailableSourcesProperty(): array
    {
        $sources = ['local' => 'Local Library'];
        if (PubBeerDb::forInstance()) {
            $sources['pub'] = 'LogrDB';
        }
        $user = auth()->user();
        if (($user->untappd_client_id ?: config('services.untappd.api_key')) && ($user->untappd_client_secret ?: config('services.untappd.api_secret'))) {
            $sources['untappd'] = 'Untappd';
        }
        if ($user->catalog_beer_api_key ?: config('services.catalog_beer.key')) {
            $sources['catalog'] = 'Catalog.beer';
        }

        return $sources;
    }

    private function fetchBeerResults(): array
    {
        $source = $this->beerSearchSource;
        $local = [];
        $localTotal = 0;

        if ($source === '' || $source === 'local') {
            $query = Beer::search($this->name);
            $localTotal = $query->count();
            $local = Beer::with('brewery')
                ->search($this->name)
                ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', ["%{$this->name}%"])
                ->orderBy('name')
                ->limit(6)
                ->get()
                ->toArray();
        }

        $api = ($source !== 'local')
            ? $this->fetchApiBeerResults($this->beerApiLimit)
            : [];

        return ['local' => $local, 'localTotal' => $localTotal, 'api' => $api];
    }

    private function fetchApiBeerResults(int $limit = 6): array
    {
        $user = auth()->user();
        $source = $this->beerSearchSource;

        try {
            if ($source === '' || $source === 'pub') {
                $pub = PubBeerDb::forInstance();
                if ($pub) {
                    $results = $pub->searchBeers($this->name, $limit);
                    foreach ($results as &$result) {
                        $result['_source'] = 'pub';
                        Cache::put("beer_api_{$result['id']}", array_merge($result, [
                            '_source' => 'pub',
                            'brewery' => [
                                'id' => $result['brewery_id'] ?? null,
                                'name' => $result['brewery_name'],
                                'city' => $result['brewery_city'],
                                'state' => $result['brewery_state'],
                                'country' => $result['brewery_country'] ?? null,
                                'website' => $result['brewery_website'] ?? null,
                            ],
                        ]), now()->addMinutes(5));
                    }

                    if ($source === 'pub' || count($results) > 0) {
                        return $results;
                    }
                }
            }

            if ($source === '' || $source === 'untappd') {
                $untappdKey = $user->untappd_client_id ?: config('services.untappd.api_key');
                $untappdSecret = $user->untappd_client_secret ?: config('services.untappd.api_secret');
                if ($untappdKey && $untappdSecret) {
                    $untappd = new Untappd($untappdKey, $untappdSecret);
                    $results = $untappd->searchBeers($this->name, $limit);
                    foreach ($results as &$result) {
                        $result['_source'] = 'untappd';
                        Cache::put("beer_api_{$result['bid']}", array_merge($result, ['_source' => 'untappd']), now()->addMinutes(5));
                    }

                    if ($source === 'untappd' || count($results) > 0) {
                        return $results;
                    }
                }
            }

            if ($source === '' || $source === 'catalog') {
                $catalogKey = $user->catalog_beer_api_key ?: config('services.catalog_beer.key');
                if ($catalogKey) {
                    $results = app(CatalogBeer::class)->search($this->name, $limit, $catalogKey);
                    foreach ($results as &$result) {
                        $result['_source'] = 'catalog';
                        Cache::put("beer_api_{$result['id']}", array_merge($result, ['_source' => 'catalog']), now()->addMinutes(5));
                    }

                    return $results;
                }
            }

            return [];
        } catch (\Exception $e) {
            \Log::error('BeerForm: beer search failed', [
                'query' => $this->name,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function importBeer(string $cacheKey): void
    {
        $data = Cache::get("beer_api_{$cacheKey}");
        if (! $data) {
            return;
        }

        $this->name = $data['name'] ?? '';
        $this->style = $this->parseApiStyle($data['style'] ?? '');
        $this->abv = ($data['abv'] ?? null) ? (float) $data['abv'] : null;
        $this->ibu = ($data['ibu'] ?? null) ? (int) $data['ibu'] : null;
        $this->description = $data['description'] ?? '';

        // Get brewery data from either format
        $isPub = ($data['_source'] ?? null) === 'pub';
        $breweryData = $data['brewery'] ?? $data['brewer'] ?? null;
        if (! empty($breweryData['name'])) {
            $breweryMatch = $isPub && ! empty($breweryData['id'])
                ? ['pub_uuid' => $breweryData['id']]
                : ['name' => $breweryData['name']];

            $brewery = Brewery::firstOrCreate(
                $breweryMatch,
                array_filter([
                    'name' => $breweryData['name'],
                    'city' => $breweryData['city'] ?? null,
                    'state' => $breweryData['state'] ?? null,
                    'country' => $breweryData['country'] ?? null,
                    'website' => $breweryData['url'] ?? $breweryData['website'] ?? null,
                    'pub_uuid' => $isPub ? ($breweryData['id'] ?? null) : null,
                ], fn ($v) => $v !== null)
            );
            $this->brewery_id = $brewery->id;
            $this->brewerySearch = $brewery->name;
        }

        $this->showBeerDropdown = false;
    }

    public function selectExistingBeer(int $id): void
    {
        $beer = Beer::with('brewery')->find($id);
        if (! $beer) {
            return;
        }

        $this->name = $beer->name;
        $this->style = $beer->style ?? [];
        $this->abv = $beer->abv;
        $this->ibu = $beer->ibu;
        $this->release_year = $beer->release_year;
        $this->brewer_master = $beer->brewer_master ?? '';
        $this->description = $beer->description ?? '';

        if ($beer->brewery) {
            $this->brewery_id = $beer->brewery_id;
            $this->brewerySearch = $beer->brewery->name;
        }

        $this->showBeerDropdown = false;
    }

    // -- Brewery search (Open Brewery DB) --

    public function updatedBrewerySearch(): void
    {
        if (strlen($this->brewerySearch) < 2) {
            $this->showBreweryDropdown = false;
            $this->breweryResults = ['local' => [], 'api' => []];
            $this->brewery_id = null;

            return;
        }

        $this->showBreweryDropdown = true;
        $this->breweryResults = $this->fetchBreweryResults();
    }

    public function selectBrewery(int $id): void
    {
        $brewery = Brewery::find($id);
        if ($brewery) {
            $this->brewery_id = $brewery->id;
            $this->brewerySearch = $brewery->name;
            $this->showBreweryDropdown = false;
        }
    }

    public function importAndSelectBrewery(string $cacheKey): void
    {
        $data = Cache::get("brewery_api_{$cacheKey}");
        if (! $data) {
            return;
        }

        $isPub = ($data['_source'] ?? null) === 'pub';
        $match = $isPub && ! empty($data['id'])
            ? ['pub_uuid' => $data['id']]
            : ['name' => $data['name']];

        $brewery = Brewery::firstOrCreate(
            $match,
            array_filter([
                'name' => $data['name'],
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'country' => $data['country'] ?? null,
                'website' => $data['website'] ?? $data['url'] ?? null,
                'pub_uuid' => $isPub ? ($data['id'] ?? null) : null,
            ], fn ($v) => $v !== null)
        );

        $this->brewery_id = $brewery->id;
        $this->brewerySearch = $brewery->name;
        $this->showBreweryDropdown = false;
    }

    public function clearBrewery(): void
    {
        $this->brewery_id = null;
        $this->brewerySearch = '';
        $this->showBreweryDropdown = false;
    }

    private function fetchBreweryResults(): array
    {
        $local = Brewery::where('name', 'like', "%{$this->brewerySearch}%")
            ->orderBy('name')
            ->limit(5)
            ->get();

        $api = [];
        try {
            $user = auth()->user();
            $pub = PubBeerDb::forInstance();
            $source = null;
            if ($pub) {
                $api = $pub->searchBreweries($this->brewerySearch, 5);
                $source = 'pub';
            } elseif (($user->untappd_client_id ?: config('services.untappd.api_key')) && ($user->untappd_client_secret ?: config('services.untappd.api_secret'))) {
                $untappd = new Untappd($user->untappd_client_id ?: config('services.untappd.api_key'), $user->untappd_client_secret ?: config('services.untappd.api_secret'));
                $api = $untappd->searchBreweries($this->brewerySearch, 5);
                $source = 'untappd';
            } elseif ($user->catalog_beer_api_key || config('services.catalog_beer.key')) {
                $api = app(CatalogBeer::class)->searchBrewers($this->brewerySearch, 5);
                $source = 'catalog';
            } else {
                $api = app(OpenBreweryDb::class)->search($this->brewerySearch, 5);
                $source = 'openbrewerydb';
            }
            foreach ($api as &$result) {
                $result['_source'] = $source;
                Cache::put("brewery_api_{$result['id']}", $result, now()->addMinutes(5));
            }
            $localNames = $local->pluck('name')->map(fn ($n) => strtolower($n))->toArray();
            $api = array_filter($api, fn ($r) => ! in_array(strtolower($r['name']), $localNames));
        } catch (\Exception $e) {
            // API failure is non-critical
        }

        return ['local' => $local->toArray(), 'api' => array_values($api)];
    }

    // -- Delete --

    public function deleteBeer(): void
    {
        if (config('app.demo_mode') || ! $this->beer) {
            return;
        }

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

    // -- Save --

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'brewery_id' => 'nullable|exists:breweries,id',
            'style' => 'nullable|array',
            'style.*' => 'string|max:255',
            'abv' => 'nullable|numeric|min:0|max:100',
            'ibu' => 'nullable|integer|min:0|max:999',
            'release_year' => 'nullable|integer|min:1800|max:'.(date('Y') + 1),
            'brewer_master' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'photo' => 'nullable|image|max:10240',
        ];

        $validated = $this->validate($rules);

        $data = [
            'name' => $validated['name'],
            'brewery_id' => $validated['brewery_id'],
            'style' => ! empty($validated['style']) ? $validated['style'] : null,
            'abv' => $validated['abv'],
            'ibu' => $validated['ibu'],
            'release_year' => $validated['release_year'],
            'brewer_master' => $validated['brewer_master'] ?: null,
            'description' => $validated['description'] ?: null,
        ];

        if ($this->photo) {
            $data['photo_path'] = $this->photo->store('beers', 'public');
        }

        if ($this->beer) {
            $this->beer->update($data);
            $beer = $this->beer;
        } else {
            $beer = Beer::create($data);

            // Add to inventory if requested
            if ($this->addToInventory) {
                $location = trim($this->storageLocation) ?: 'Fridge';

                $storeId = $this->resolveLocationId('store', Store::class);

                $inventory = Inventory::create([
                    'beer_id' => $beer->id,
                    'user_id' => auth()->id(),
                    'storage_location' => $location,
                    'quantity' => $this->addQuantity,
                    'store_id' => $storeId,
                    'date_acquired' => $this->purchaseDate ?: now()->toDateString(),
                    'is_gift' => $this->isGift,
                ]);

                if (collect($this->inventoryShareTargets)->contains('enabled', true)) {
                    $currentUser = auth()->user();
                    \App\Services\Discord::sendPurchase($inventory, $currentUser);
                    \App\Services\PubDiscord::sendPurchase($inventory, $currentUser);
                }
            }

            // Create check-in if requested
            if ($this->addCheckin) {
                $venueId = $this->resolveLocationId('venue', Venue::class);

                $checkin = Checkin::create([
                    'user_id' => auth()->id(),
                    'beer_id' => $beer->id,
                    'rating' => $this->checkinRating,
                    'serving_type' => $this->checkinServingType ?: null,
                    'venue_id' => $venueId,
                    'notes' => trim($this->checkinNotes) ?: null,
                ]);

                // Store checkin photos
                if ($this->checkinPhotos) {
                    foreach ($this->checkinPhotos as $photo) {
                        $path = $photo->store('checkin-photos', 'public');
                        \App\Models\CheckinPhoto::create([
                            'checkin_id' => $checkin->id,
                            'photo_path' => $path,
                        ]);
                    }
                }

                // Use beer photo if selected and no other photos added
                if ($this->useBeerPhoto && empty($this->checkinPhotos) && $beer->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($beer->photo_path)) {
                    $ext = pathinfo($beer->photo_path, PATHINFO_EXTENSION);
                    $newPath = 'checkin-photos/'.uniqid().'.'.$ext;
                    \Illuminate\Support\Facades\Storage::disk('public')->copy($beer->photo_path, $newPath);
                    \App\Models\CheckinPhoto::create([
                        'checkin_id' => $checkin->id,
                        'photo_path' => $newPath,
                    ]);
                }

                if (collect($this->shareTargets)->contains('enabled', true)) {
                    event(new CheckinCreated($checkin, auth()->user(), $this->shareTargets));
                }
            }

        }

        session()->flash('message', $this->beer ? 'Beer updated successfully.' : 'Beer added successfully.');

        $this->redirect(route('beers.show', $beer), navigate: true);
    }

    public function render()
    {
        $isEditing = $this->beer && $this->beer->exists;

        return view('livewire.beer-form', [
            'styles' => $this->getStyles(),
            'isEditing' => $isEditing,
            'hasApiKey' => ! empty($this->availableSources),
            'availableSources' => $this->availableSources,
            'venueSuggestions' => $this->getLocationSuggestions('venue', Venue::class, 8),
            'venueApiResults' => $this->getLocationApiResults('venue'),
            'storeSuggestions' => $this->getLocationSuggestions('store', Store::class),
            'storeApiResults' => $this->getLocationApiResults('store'),
        ])->title($isEditing ? 'Edit '.$this->beer->name.' | Beers' : 'Add Beer | Beers');
    }

    private function parseApiStyle(string $apiStyle): array
    {
        if (! $apiStyle) {
            return [];
        }

        $allStyles = collect($this->getStyles())->flatten()->toArray();
        $matched = [];
        $normalized = strtolower($apiStyle);

        // Direct keyword mappings for common API style strings
        $mappings = [
            'india pale ale' => 'IPA',
            'double india pale ale' => 'Double IPA',
            'imperial india pale ale' => 'Double IPA',
            'new england ipa' => 'Hazy IPA',
            'hazy ipa' => 'Hazy IPA',
            'juicy ipa' => 'Hazy IPA',
            'session ipa' => 'Session IPA',
            'american pale ale' => 'American Pale Ale',
            'pale ale' => 'Pale Ale',
            'imperial stout' => 'Imperial Stout',
            'milk stout' => 'Milk Stout',
            'pastry stout' => 'Pastry Stout',
            'oatmeal stout' => 'Stout',
            'stout' => 'Stout',
            'porter' => 'Porter',
            'pilsner' => 'Pilsner',
            'lager' => 'Lager',
            'helles' => 'Helles',
            'hefeweizen' => 'Hefeweizen',
            'wheat' => 'Wheat Beer',
            'witbier' => 'Witbier',
            'belgian' => 'Belgian Blonde',
            'dubbel' => 'Belgian Dubbel',
            'tripel' => 'Belgian Tripel',
            'quadrupel' => 'Belgian Quad',
            'saison' => 'Saison',
            'farmhouse' => 'Farmhouse Ale',
            'gose' => 'Gose',
            'berliner' => 'Berliner Weisse',
            'lambic' => 'Lambic',
            'gueuze' => 'Gueuze',
            'sour' => 'Sour',
            'fruit' => 'Fruit Beer',
            'barleywine' => 'Barleywine',
            'scotch ale' => 'Scotch Ale',
            'bitter' => 'Bitter',
            'esb' => 'ESB',
            'extra special bitter' => 'ESB',
            'mild' => 'Mild',
            'kölsch' => 'Kölsch',
            'kolsch' => 'Kölsch',
            'altbier' => 'Altbier',
            'bock' => 'Bock',
            'doppelbock' => 'Doppelbock',
            'märzen' => 'Märzen',
            'marzen' => 'Märzen',
            'oktoberfest' => 'Märzen',
            'dunkel' => 'Dunkel',
            'schwarzbier' => 'Schwarzbier',
            'rauchbier' => 'Rauchbier',
            'cream ale' => 'Cream Ale',
            'blonde ale' => 'Blonde Ale',
            'golden ale' => 'Golden Ale',
            'brown ale' => 'Brown Ale',
            'amber' => 'Amber Ale',
            'red ale' => 'Red Ale',
            'cider' => 'Cider',
            'mead' => 'Mead',
            'seltzer' => 'Seltzer',
        ];

        // Check mappings from most specific to least specific
        $sortedMappings = $mappings;
        uksort($sortedMappings, fn ($a, $b) => strlen($b) - strlen($a));

        foreach ($sortedMappings as $keyword => $style) {
            if (str_contains($normalized, $keyword) && ! in_array($style, $matched)) {
                $matched[] = $style;
            }
        }

        // Also check for "imperial" / "double" as modifiers
        if (empty($matched) && (str_contains($normalized, 'imperial') || str_contains($normalized, 'double'))) {
            // Try to find a base style match
            foreach ($allStyles as $style) {
                if (str_contains($normalized, strtolower($style))) {
                    $matched[] = $style;
                }
            }
        }

        return $matched;
    }

    private function getStyles(): array
    {
        return config('beer-styles.grouped');
    }
}
