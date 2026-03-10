<?php

namespace App\Livewire;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Inventory;
use App\Services\CatalogBeer;
use App\Services\LogrDb;
use App\Services\OpenBreweryDb;
use App\Services\Untappd;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithFileUploads;

class BeerForm extends Component
{
    use WithFileUploads;

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
    public string $purchaseLocation = '';
    public string $purchaseDate = '';
    public bool $isGift = false;

    // Beer search
    public string $beerSearch = '';
    public bool $showBeerDropdown = false;
    public array $beerResults = [];

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
    }

    // -- Beer search (Untappd > catalog.beer) --

    public function updatedBeerSearch(): void
    {
        if (strlen($this->beerSearch) < 2) {
            $this->showBeerDropdown = false;
            $this->beerResults = [];
            return;
        }

        $this->showBeerDropdown = true;
        $this->beerResults = $this->fetchBeerResults();
    }

    private function fetchBeerResults(): array
    {
        $user = auth()->user();

        try {
            // Logr DB is primary when configured
            $logrDb = LogrDb::forUser();
            if ($logrDb) {
                $results = $logrDb->searchBeers($this->beerSearch, 8);
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

            // Untappd when configured
            if ($user->untappd_client_id && $user->untappd_client_secret) {
                $untappd = new Untappd($user->untappd_client_id, $user->untappd_client_secret);
                $results = $untappd->searchBeers($this->beerSearch, 8);
                foreach ($results as &$result) {
                    $result['_source'] = 'untappd';
                    Cache::put("beer_api_{$result['bid']}", array_merge($result, ['_source' => 'untappd']), now()->addMinutes(5));
                }
                return $results;
            }

            // Catalog.beer as fallback
            $catalogKey = $user->catalog_beer_api_key;
            if ($catalogKey) {
                $results = app(CatalogBeer::class)->search($this->beerSearch, 8, $catalogKey);
                foreach ($results as &$result) {
                    $result['_source'] = 'catalog';
                    Cache::put("beer_api_{$result['id']}", array_merge($result, ['_source' => 'catalog']), now()->addMinutes(5));
                }
                return $results;
            }

            return [];
        } catch (\Exception $e) {
            \Log::error('BeerForm: beer search failed', [
                'query' => $this->beerSearch,
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
            $this->brewery_id = $brewery->id;
            $this->brewerySearch = $brewery->name;
        }

        $this->beerSearch = '';
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

        $brewery = Brewery::firstOrCreate(
            ['name' => $data['name']],
            [
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'country' => $data['country'] ?? null,
                'website' => $data['website'] ?? $data['url'] ?? null,
            ]
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
            $logrDb = LogrDb::forUser($user);
            $source = null;
            if ($logrDb) {
                $api = $logrDb->searchBreweries($this->brewerySearch, 5);
                $source = 'logr_db';
            } elseif ($user->untappd_client_id && $user->untappd_client_secret) {
                $untappd = new Untappd($user->untappd_client_id, $user->untappd_client_secret);
                $api = $untappd->searchBreweries($this->brewerySearch, 5);
                $source = 'untappd';
            } elseif ($user->catalog_beer_api_key) {
                $api = app(CatalogBeer::class)->searchBrewers($this->brewerySearch, 5, $user->catalog_beer_api_key);
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
        if (! $this->beer) {
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
            'release_year' => 'nullable|integer|min:1800|max:' . (date('Y') + 1),
            'brewer_master' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'photo' => 'nullable|image|max:4096',
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

                $inventory = Inventory::create([
                    'beer_id' => $beer->id,
                    'user_id' => auth()->id(),
                    'storage_location' => $location,
                    'quantity' => $this->addQuantity,
                    'purchase_location' => $this->purchaseLocation ?: null,
                    'date_acquired' => $this->purchaseDate ?: now()->toDateString(),
                    'is_gift' => $this->isGift,
                ]);
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
            'hasApiKey' => LogrDb::forUser() !== null || (bool) (auth()->user()->untappd_client_id || auth()->user()->catalog_beer_api_key ?? config('services.catalog_beer.key')),
        ])->title($isEditing ? 'Edit ' . $this->beer->name : 'Add Beer');
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
        return [
            'Hoppy' => ['IPA', 'Double IPA', 'Hazy IPA', 'Session IPA', 'Pale Ale', 'American Pale Ale'],
            'Light & Crisp' => ['Lager', 'Pilsner', 'Helles', 'Cream Ale', 'Blonde Ale', 'Golden Ale', 'Kölsch'],
            'Dark & Malty' => ['Stout', 'Imperial Stout', 'Milk Stout', 'Pastry Stout', 'Porter', 'Brown Ale', 'Schwarzbier'],
            'Amber & Red' => ['Amber Ale', 'Red Ale', 'ESB', 'Bitter', 'Mild', 'Altbier', 'Scotch Ale'],
            'Wheat' => ['Wheat Beer', 'Hefeweizen', 'Witbier'],
            'Belgian' => ['Belgian Blonde', 'Belgian Dubbel', 'Belgian Tripel', 'Belgian Quad', 'Saison', 'Farmhouse Ale'],
            'Sour & Wild' => ['Sour', 'Gose', 'Berliner Weisse', 'Lambic', 'Gueuze', 'Fruit Beer'],
            'German' => ['Bock', 'Doppelbock', 'Märzen', 'Dunkel', 'Rauchbier'],
            'Strong' => ['Barleywine'],
            'Other' => ['Cider', 'Mead', 'Seltzer', 'Other'],
        ];
    }
}
