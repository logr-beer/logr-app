<?php

namespace Database\Seeders;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = auth()->user() ?? User::firstOrCreate(
            ['username' => 'demo'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
            ],
        );

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

            $beerKey = $data['beer_name'] . '|' . $brewery->id;
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
                $venueKey = $data['venue_name'] . '|' . ($data['venue_city'] ?? '');
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
        $this->command?->info('Breweries: ' . count($breweryCache) . ', Beers: ' . count($beerCache) . ', Venues: ' . count($venueCache));
    }
}
