<?php

namespace Database\Seeders;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\Collection;
use App\Models\Inventory;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = auth()->user() ?? User::first() ?? User::create([
            'username' => 'demo',
            'name' => 'Demo User',
            'password' => Hash::make('password'),
        ]);

        $csvPath = database_path('seeders/data/cc-demo-data.csv');

        if (! file_exists($csvPath)) {
            $this->command?->error("Sample data CSV not found at: {$csvPath}");

            return;
        }

        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle);

        $breweryCache = [];
        $beerCache = [];
        $venueCache = [];
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);

            $breweryKey = $data['brewery'];
            if (! isset($breweryCache[$breweryKey])) {
                $breweryAttrs = [
                    'city' => $data['city'] ?: null,
                    'state' => $data['state'] ?: null,
                ];

                if (! empty($data['brewery_latitude'])) {
                    $breweryAttrs['latitude'] = $data['brewery_latitude'];
                    $breweryAttrs['longitude'] = $data['brewery_longitude'];
                }

                $breweryCache[$breweryKey] = Brewery::firstOrCreate(
                    ['name' => $data['brewery']],
                    $breweryAttrs,
                );
            }
            $brewery = $breweryCache[$breweryKey];

            $beerKey = $data['beer_name'].'|'.$brewery->id;
            if (! isset($beerCache[$beerKey])) {
                $beerAttrs = [
                    'style' => $data['style'] ? [$data['style']] : null,
                    'abv' => $data['abv'] ?: null,
                ];

                if (! empty($data['catalog_beer_id'])) {
                    $beerCache[$beerKey] = Beer::firstOrCreate(
                        ['catalog_beer_id' => $data['catalog_beer_id']],
                        ['name' => $data['beer_name'], 'brewery_id' => $brewery->id] + $beerAttrs,
                    );
                } else {
                    $beerCache[$beerKey] = Beer::firstOrCreate(
                        ['name' => $data['beer_name'], 'brewery_id' => $brewery->id],
                        $beerAttrs,
                    );
                }
            }
            $beer = $beerCache[$beerKey];

            $venueId = null;
            if (! empty($data['venue_name'])) {
                $venueKey = $data['venue_name'].'|'.($data['venue_city'] ?? '');
                if (! isset($venueCache[$venueKey])) {
                    $venueAttrs = [
                        'state' => $data['venue_state'] ?: null,
                        'country' => $data['venue_country'] ?: null,
                    ];

                    if (! empty($data['venue_latitude'])) {
                        $venueAttrs['latitude'] = $data['venue_latitude'];
                        $venueAttrs['longitude'] = $data['venue_longitude'];
                    }

                    $venueCache[$venueKey] = Venue::firstOrCreate(
                        ['name' => $data['venue_name'], 'city' => $data['venue_city'] ?: null],
                        $venueAttrs,
                    );
                }
                $venueId = $venueCache[$venueKey]->id;
            }

            $checkinDate = Carbon::parse($data['date']);

            Checkin::create([
                'user_id' => $user->id,
                'beer_id' => $beer->id,
                'venue_id' => $venueId,
                'rating' => $data['rating'] ?: null,
                'notes' => $data['notes'] ?: null,
                'serving_type' => $data['serving_type'] ?: null,
                'created_at' => $checkinDate->setTime(rand(12, 22), rand(0, 59)),
                'updated_at' => $checkinDate,
            ]);

            $count++;
        }

        fclose($handle);

        $this->command?->info("Imported {$count} checkins ({$csvPath})");
        $this->command?->info('Breweries: '.count($breweryCache).', Beers: '.count($beerCache).', Venues: '.count($venueCache));

        $this->seedInventory($user, $beerCache);
        $this->seedCollections($user, $beerCache);
    }

    private function seedInventory(User $user, array $beerCache): void
    {
        $locations = ['Garage Fridge', 'Office Fridge', 'Basement Shelf'];

        $beers = collect($beerCache)->values();
        $sample = $beers->random((int) ceil($beers->count() * 0.1));

        $inventoryCount = 0;

        foreach ($sample as $beer) {
            Inventory::firstOrCreate(
                ['beer_id' => $beer->id, 'user_id' => $user->id],
                [
                    'quantity' => rand(1, 6),
                    'storage_location' => $locations[array_rand($locations)],
                    'date_acquired' => now()->subDays(rand(1, 90)),
                ],
            );
            $inventoryCount++;
        }

        $this->command?->info("Inventory: {$inventoryCount} beers across ".count($locations).' locations');
    }

    private function seedCollections(User $user, array $beerCache): void
    {
        $allBeers = collect($beerCache)->values();

        $styleGroups = [
            'IPA Collection' => ['IPA', 'Hazy IPA', 'Double IPA', 'West Coast IPA', 'DDH IPA', 'Imperial IPA', 'Fresh Hop IPA', 'East Coast IPA', 'Triple IPA'],
            'Dark & Roasty' => ['Stout', 'Imperial Stout', 'Oatmeal Stout', 'Brown Porter', 'Robust Porter', 'Coffee Stout', 'Pastry Stout', 'Schwarzbier'],
            'Belgian & Farmhouse' => ['Belgian', 'Belgian Blonde', 'Belgian Golden Ale', 'Belgian Dark Ale', 'Quadrupel', 'Belgian-Style Dark Strong Ale', 'Belgian Wheat'],
            'Easy Drinkers' => ['Pale Ale', 'Blonde Ale', 'Blonde', 'Cream Ale', 'Kolsch-Style Ale', 'Pilsner', 'Hefeweizen', 'Wheat Ale', 'Shandy'],
        ];

        $descriptions = [
            'IPA Collection' => 'All the hop-forward beers in the log — hazy, west coast, double, and everything in between.',
            'Dark & Roasty' => 'Stouts, porters, and dark ales for cold nights and cozy vibes.',
            'Belgian & Farmhouse' => 'Belgian styles from blondes to quads.',
            'Easy Drinkers' => 'Light, approachable beers perfect for a sunny afternoon.',
        ];

        $collectionCount = 0;

        foreach ($styleGroups as $name => $styles) {
            $matchingBeers = $allBeers->filter(function ($beer) use ($styles) {
                $beerStyles = is_array($beer->style) ? $beer->style : [];

                foreach ($beerStyles as $beerStyle) {
                    foreach ($styles as $targetStyle) {
                        if (stripos($beerStyle, $targetStyle) !== false) {
                            return true;
                        }
                    }
                }

                return false;
            });

            if ($matchingBeers->isEmpty()) {
                continue;
            }

            $collection = Collection::create([
                'user_id' => $user->id,
                'name' => $name,
                'description' => $descriptions[$name],
                'is_dynamic' => false,
            ]);

            $matchingBeers->values()->each(function ($beer, $index) use ($collection) {
                $collection->beers()->attach($beer->id, ['sort_order' => $index]);
            });

            $collectionCount++;
            $this->command?->info("Collection '{$name}': {$matchingBeers->count()} beers");
        }

        $this->command?->info("Collections: {$collectionCount} created");
    }
}
