<?php

namespace Database\Seeders;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\Collection;
use App\Models\Companion;
use App\Models\Inventory;
use App\Models\Tag;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name' => 'Demo User',
            'email' => 'demo@logr.beer',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        // Venues
        $venues = collect([
            ['name' => 'Home', 'city' => null, 'state' => null, 'country' => null],
            ['name' => 'The Hoppy Monk', 'address' => '123 Main St', 'city' => 'Portland', 'state' => 'OR', 'country' => 'US', 'latitude' => 45.5152, 'longitude' => -122.6784],
            ['name' => 'Barrel & Brew', 'address' => '456 Oak Ave', 'city' => 'Denver', 'state' => 'CO', 'country' => 'US', 'latitude' => 39.7392, 'longitude' => -104.9903],
            ['name' => 'The Grain Station', 'address' => '789 Pine St', 'city' => 'San Diego', 'state' => 'CA', 'country' => 'US', 'latitude' => 32.7157, 'longitude' => -117.1611],
            ['name' => 'Northern Pint', 'address' => '12 Birch Rd', 'city' => 'Burlington', 'state' => 'VT', 'country' => 'US', 'latitude' => 44.4759, 'longitude' => -73.2121],
            ['name' => 'Craft & Draft', 'address' => '88 Elm St', 'city' => 'Asheville', 'state' => 'NC', 'country' => 'US', 'latitude' => 35.5951, 'longitude' => -82.5515],
        ])->map(fn ($v) => Venue::create($v));

        // Breweries
        $breweries = collect([
            ['name' => 'Mountain Sun Brewing', 'city' => 'Boulder', 'state' => 'CO', 'country' => 'US'],
            ['name' => 'Cascade Brewing', 'city' => 'Portland', 'state' => 'OR', 'country' => 'US'],
            ['name' => 'Alchemist Brewing', 'city' => 'Stowe', 'state' => 'VT', 'country' => 'US'],
            ['name' => 'Pisgah Brewing', 'city' => 'Black Mountain', 'state' => 'NC', 'country' => 'US'],
            ['name' => 'Societe Brewing', 'city' => 'San Diego', 'state' => 'CA', 'country' => 'US'],
            ['name' => 'Trillium Brewing', 'city' => 'Boston', 'state' => 'MA', 'country' => 'US'],
            ['name' => 'Side Project Brewing', 'city' => 'Maplewood', 'state' => 'MO', 'country' => 'US'],
            ['name' => 'Brasserie Dupont', 'city' => 'Tourpes', 'state' => 'Hainaut', 'country' => 'BE'],
        ])->map(fn ($b) => Brewery::create($b));

        // Tags
        $tags = collect([
            ['name' => 'Hazy', 'color' => '#fbbf24'],
            ['name' => 'Barrel-Aged', 'color' => '#92400e'],
            ['name' => 'Sessionable', 'color' => '#34d399'],
            ['name' => 'Crushable', 'color' => '#60a5fa'],
            ['name' => 'Cellar Worthy', 'color' => '#a78bfa'],
            ['name' => 'Fruit Forward', 'color' => '#f472b6'],
        ])->map(fn ($t) => Tag::create($t));

        // Companions
        $companions = collect([
            ['name' => 'Alex'],
            ['name' => 'Jordan'],
            ['name' => 'Sam'],
            ['name' => 'Riley'],
        ])->map(fn ($c) => Companion::create($c));

        // Beers
        $beers = collect([
            ['name' => 'Heady Topper', 'brewery_id' => $breweries[2]->id, 'style' => ['IPA', 'Double IPA'], 'abv' => 8.0, 'ibu' => 75, 'description' => 'The iconic Vermont double IPA. Drink from the can.', 'is_favorite' => true],
            ['name' => 'Focal Banger', 'brewery_id' => $breweries[2]->id, 'style' => ['IPA'], 'abv' => 7.0, 'ibu' => 60, 'description' => 'Citra and Mosaic hops create tropical and citrus notes.'],
            ['name' => 'Colorado Kind Ale', 'brewery_id' => $breweries[0]->id, 'style' => ['Amber Ale'], 'abv' => 5.2, 'ibu' => 28, 'description' => 'A smooth, malty amber with caramel sweetness.'],
            ['name' => 'Saison Dupont', 'brewery_id' => $breweries[7]->id, 'style' => ['Saison', 'Farmhouse Ale'], 'abv' => 6.5, 'ibu' => 30, 'description' => 'The benchmark farmhouse ale. Dry, spicy, effervescent.', 'is_favorite' => true],
            ['name' => 'The Pupil', 'brewery_id' => $breweries[4]->id, 'style' => ['IPA'], 'abv' => 7.5, 'ibu' => 70, 'description' => 'West coast IPA with bright citrus and pine.'],
            ['name' => 'Apricot Sour', 'brewery_id' => $breweries[1]->id, 'style' => ['Sour', 'Fruit Beer'], 'abv' => 7.3, 'ibu' => 12, 'description' => 'Barrel-aged sour with apricot. Tart and complex.'],
            ['name' => 'Congress Street', 'brewery_id' => $breweries[5]->id, 'style' => ['IPA', 'New England IPA'], 'abv' => 7.2, 'ibu' => 55, 'description' => 'Juicy and hazy with notes of peach and mango.'],
            ['name' => 'Pisgah Pale', 'brewery_id' => $breweries[3]->id, 'style' => ['Pale Ale'], 'abv' => 5.6, 'ibu' => 42, 'description' => 'A classic American pale ale with citrus hops.'],
            ['name' => 'Beer : Barrel : Time', 'brewery_id' => $breweries[6]->id, 'style' => ['Stout', 'Imperial Stout'], 'abv' => 13.5, 'ibu' => 50, 'description' => 'Barrel-aged imperial stout. Decadent and complex.', 'is_favorite' => true],
            ['name' => 'DDH Fort Point', 'brewery_id' => $breweries[5]->id, 'style' => ['Pale Ale', 'New England Pale Ale'], 'abv' => 6.6, 'ibu' => 45, 'description' => 'Double dry-hopped pale ale. Bright tropical notes.'],
            ['name' => 'Avec Les Bons Voeux', 'brewery_id' => $breweries[7]->id, 'style' => ['Belgian Strong Ale'], 'abv' => 9.5, 'ibu' => 35, 'description' => 'A special holiday saison. Rich, fruity, and warming.'],
            ['name' => 'Vlad the Imp Aler', 'brewery_id' => $breweries[1]->id, 'style' => ['Sour', 'Flanders Red'], 'abv' => 9.2, 'ibu' => 15, 'description' => 'Barrel-aged Flanders-style red. Sour cherry and oak.'],
            ['name' => 'Sun King', 'brewery_id' => $breweries[0]->id, 'style' => ['Lager', 'Helles'], 'abv' => 4.8, 'ibu' => 18, 'description' => 'Clean, crisp Munich-style helles lager.'],
            ['name' => 'The Harlot', 'brewery_id' => $breweries[4]->id, 'style' => ['Belgian Blonde'], 'abv' => 7.0, 'ibu' => 22, 'description' => 'Belgian-inspired blonde ale. Spicy and dry.'],
            ['name' => 'Scaled IPA', 'brewery_id' => $breweries[5]->id, 'style' => ['IPA', 'Double IPA'], 'abv' => 8.5, 'ibu' => 65, 'description' => 'Trillium\'s rotating double IPA. Resinous and tropical.'],
            ['name' => 'Nitro Pale', 'brewery_id' => $breweries[3]->id, 'style' => ['Pale Ale'], 'abv' => 5.2, 'ibu' => 38, 'description' => 'Nitrogen-poured pale ale. Creamy with floral hops.'],
            ['name' => 'Derailed Dunkel', 'brewery_id' => $breweries[6]->id, 'style' => ['Lager', 'Dunkel'], 'abv' => 5.1, 'ibu' => 20, 'description' => 'Dark Munich lager. Bread crust and light chocolate.'],
            ['name' => 'Sunnyside Wheat', 'brewery_id' => $breweries[0]->id, 'style' => ['Wheat Beer', 'Hefeweizen'], 'abv' => 4.6, 'ibu' => 14, 'description' => 'Bavarian hefeweizen with banana and clove.'],
            ['name' => 'Bourbonic Plague', 'brewery_id' => $breweries[1]->id, 'style' => ['Stout', 'Imperial Stout'], 'abv' => 11.1, 'ibu' => 45, 'description' => 'Barrel-aged imperial stout with dates and spices.'],
            ['name' => 'Cutting Tiles Mosaic', 'brewery_id' => $breweries[5]->id, 'style' => ['IPA', 'New England IPA'], 'abv' => 6.5, 'ibu' => 50, 'description' => 'Single-hop Mosaic IPA. Blueberry and dank.'],
        ])->map(fn ($b) => Beer::create($b));

        // Tag some beers
        $beers[0]->tags()->attach([$tags[0]->id]); // Heady Topper -> Hazy
        $beers[5]->tags()->attach([$tags[1]->id, $tags[5]->id]); // Apricot Sour -> Barrel-Aged, Fruit Forward
        $beers[6]->tags()->attach([$tags[0]->id, $tags[3]->id]); // Congress Street -> Hazy, Crushable
        $beers[8]->tags()->attach([$tags[1]->id, $tags[4]->id]); // Beer:Barrel:Time -> Barrel-Aged, Cellar Worthy
        $beers[9]->tags()->attach([$tags[0]->id, $tags[3]->id]); // DDH Fort Point -> Hazy, Crushable
        $beers[11]->tags()->attach([$tags[1]->id, $tags[4]->id]); // Vlad -> Barrel-Aged, Cellar Worthy
        $beers[12]->tags()->attach([$tags[2]->id, $tags[3]->id]); // Sun King -> Sessionable, Crushable
        $beers[18]->tags()->attach([$tags[1]->id, $tags[4]->id]); // Bourbonic Plague -> Barrel-Aged, Cellar Worthy
        $beers[19]->tags()->attach([$tags[0]->id]); // Cutting Tiles -> Hazy

        // Checkins spread over the past 6 months
        $servingTypes = ['draft', 'can', 'bottle', 'crowler', 'growler'];
        $notes = [
            'Incredible hop aroma. One of the best I\'ve had.',
            'Solid choice. Would definitely order again.',
            'A bit too bitter for my taste but well-crafted.',
            'Perfect balance of malt and hops.',
            'Great sessionable option for a hot day.',
            'Smooth and easy drinking.',
            'The barrel character really shines through.',
            'Tart and funky in all the right ways.',
            'Malty sweetness with a clean finish.',
            'Juicy, hazy, and bursting with tropical fruit.',
            'Complex and layered. Deserves a slow pour.',
            'Nice and crisp. Great lawnmower beer.',
            'Bold flavors. Not for the faint of heart.',
            'Refreshing and bright. Summer in a glass.',
            null, null, null, // Some checkins without notes
        ];

        $checkins = [];
        $checkinData = [
            ['beer' => 0, 'rating' => 4.75, 'venue' => 4, 'serving' => 'can', 'days_ago' => 170],
            ['beer' => 1, 'rating' => 4.5, 'venue' => 4, 'serving' => 'can', 'days_ago' => 168],
            ['beer' => 2, 'rating' => 3.5, 'venue' => 2, 'serving' => 'draft', 'days_ago' => 155],
            ['beer' => 3, 'rating' => 4.5, 'venue' => 0, 'serving' => 'bottle', 'days_ago' => 150],
            ['beer' => 4, 'rating' => 4.0, 'venue' => 3, 'serving' => 'draft', 'days_ago' => 142],
            ['beer' => 5, 'rating' => 4.25, 'venue' => 1, 'serving' => 'draft', 'days_ago' => 135],
            ['beer' => 6, 'rating' => 4.5, 'venue' => 0, 'serving' => 'can', 'days_ago' => 128],
            ['beer' => 7, 'rating' => 3.75, 'venue' => 5, 'serving' => 'draft', 'days_ago' => 120],
            ['beer' => 8, 'rating' => 5.0, 'venue' => 0, 'serving' => 'bottle', 'days_ago' => 115],
            ['beer' => 9, 'rating' => 4.0, 'venue' => 0, 'serving' => 'can', 'days_ago' => 108],
            ['beer' => 10, 'rating' => 4.25, 'venue' => 0, 'serving' => 'bottle', 'days_ago' => 100],
            ['beer' => 11, 'rating' => 4.5, 'venue' => 1, 'serving' => 'draft', 'days_ago' => 95],
            ['beer' => 12, 'rating' => 3.25, 'venue' => 2, 'serving' => 'draft', 'days_ago' => 88],
            ['beer' => 13, 'rating' => 3.75, 'venue' => 3, 'serving' => 'draft', 'days_ago' => 80],
            ['beer' => 14, 'rating' => 4.5, 'venue' => 0, 'serving' => 'can', 'days_ago' => 72],
            ['beer' => 15, 'rating' => 3.5, 'venue' => 5, 'serving' => 'draft', 'days_ago' => 65],
            ['beer' => 16, 'rating' => 3.75, 'venue' => 0, 'serving' => 'bottle', 'days_ago' => 58],
            ['beer' => 17, 'rating' => 3.5, 'venue' => 2, 'serving' => 'draft', 'days_ago' => 50],
            ['beer' => 18, 'rating' => 4.75, 'venue' => 0, 'serving' => 'bottle', 'days_ago' => 45],
            ['beer' => 19, 'rating' => 4.25, 'venue' => 0, 'serving' => 'can', 'days_ago' => 38],
            // Second checkins for some favorites
            ['beer' => 0, 'rating' => 5.0, 'venue' => 0, 'serving' => 'can', 'days_ago' => 30],
            ['beer' => 3, 'rating' => 4.75, 'venue' => 0, 'serving' => 'bottle', 'days_ago' => 25],
            ['beer' => 6, 'rating' => 4.25, 'venue' => 1, 'serving' => 'draft', 'days_ago' => 20],
            ['beer' => 8, 'rating' => 5.0, 'venue' => 0, 'serving' => 'bottle', 'days_ago' => 18],
            ['beer' => 14, 'rating' => 4.25, 'venue' => 0, 'serving' => 'can', 'days_ago' => 14],
            ['beer' => 5, 'rating' => 4.0, 'venue' => 1, 'serving' => 'draft', 'days_ago' => 10],
            ['beer' => 9, 'rating' => 4.25, 'venue' => 0, 'serving' => 'can', 'days_ago' => 7],
            ['beer' => 4, 'rating' => 4.25, 'venue' => 3, 'serving' => 'draft', 'days_ago' => 5],
            ['beer' => 19, 'rating' => 4.5, 'venue' => 0, 'serving' => 'can', 'days_ago' => 3],
            ['beer' => 1, 'rating' => 4.5, 'venue' => 0, 'serving' => 'can', 'days_ago' => 1],
        ];

        foreach ($checkinData as $i => $data) {
            $checkin = Checkin::create([
                'user_id' => $user->id,
                'beer_id' => $beers[$data['beer']]->id,
                'venue_id' => $venues[$data['venue']]->id,
                'rating' => $data['rating'],
                'notes' => $notes[$i % count($notes)],
                'serving_type' => $data['serving'],
                'created_at' => now()->subDays($data['days_ago'])->setTime(rand(12, 22), rand(0, 59)),
                'updated_at' => now()->subDays($data['days_ago']),
            ]);
            $checkins[] = $checkin;
        }

        // Tag some checkins
        $checkins[0]->tags()->attach([$tags[0]->id]);
        $checkins[8]->tags()->attach([$tags[1]->id]);
        $checkins[5]->tags()->attach([$tags[5]->id]);
        $checkins[20]->tags()->attach([$tags[0]->id]);

        // Attach companions to some checkins
        $checkins[1]->companions()->attach([$companions[0]->id, $companions[1]->id]);
        $checkins[4]->companions()->attach([$companions[2]->id]);
        $checkins[7]->companions()->attach([$companions[0]->id, $companions[3]->id]);
        $checkins[11]->companions()->attach([$companions[1]->id]);
        $checkins[22]->companions()->attach([$companions[0]->id, $companions[2]->id]);
        $checkins[27]->companions()->attach([$companions[3]->id]);

        // Inventory
        $inventoryData = [
            ['beer' => 0, 'quantity' => 3, 'storage' => 'fridge', 'purchase' => 'The Alchemist Brewery', 'days_ago' => 30],
            ['beer' => 3, 'quantity' => 2, 'storage' => 'cellar', 'purchase' => 'Total Wine', 'days_ago' => 60],
            ['beer' => 8, 'quantity' => 1, 'storage' => 'cellar', 'purchase' => 'Side Project Cellar', 'days_ago' => 90],
            ['beer' => 10, 'quantity' => 2, 'storage' => 'cellar', 'purchase' => 'Craft Beer Cellar', 'days_ago' => 100],
            ['beer' => 14, 'quantity' => 4, 'storage' => 'fridge', 'purchase' => 'Trillium Tap Room', 'days_ago' => 14],
            ['beer' => 18, 'quantity' => 1, 'storage' => 'cellar', 'purchase' => 'Cascade Barrel House', 'days_ago' => 45, 'is_gift' => true],
            ['beer' => 19, 'quantity' => 2, 'storage' => 'fridge', 'purchase' => 'Trillium Tap Room', 'days_ago' => 7],
            ['beer' => 12, 'quantity' => 6, 'storage' => 'fridge', 'purchase' => 'Liquor Mart', 'days_ago' => 5],
            ['beer' => 17, 'quantity' => 2, 'storage' => 'fridge', 'purchase' => 'Whole Foods', 'days_ago' => 3],
        ];

        foreach ($inventoryData as $data) {
            Inventory::create([
                'beer_id' => $beers[$data['beer']]->id,
                'user_id' => $user->id,
                'quantity' => $data['quantity'],
                'storage_location' => $data['storage'],
                'purchase_location' => $data['purchase'],
                'date_acquired' => now()->subDays($data['days_ago']),
                'is_gift' => $data['is_gift'] ?? false,
            ]);
        }

        // Collections
        $ipas = Collection::create([
            'user_id' => $user->id,
            'name' => 'IPA Hall of Fame',
            'description' => 'The best IPAs I\'ve had.',
            'is_dynamic' => false,
        ]);
        $ipas->beers()->attach([
            $beers[0]->id => ['sort_order' => 1],
            $beers[1]->id => ['sort_order' => 2],
            $beers[4]->id => ['sort_order' => 3],
            $beers[6]->id => ['sort_order' => 4],
            $beers[14]->id => ['sort_order' => 5],
        ]);

        Collection::create([
            'user_id' => $user->id,
            'name' => 'Top Rated',
            'description' => 'Everything rated 4.5 or above.',
            'is_dynamic' => true,
            'rules' => ['min_rating' => 4.5],
        ]);

        Collection::create([
            'user_id' => $user->id,
            'name' => 'Cellar Stash',
            'description' => 'What\'s aging in the cellar.',
            'is_dynamic' => true,
            'rules' => ['storage_location' => 'cellar'],
        ]);
    }
}
