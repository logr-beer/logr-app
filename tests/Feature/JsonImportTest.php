<?php

namespace Tests\Feature;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\Collection;
use App\Models\Companion;
use App\Models\Inventory;
use App\Models\Store;
use App\Models\Tag;
use App\Models\User;
use App\Models\Venue;
use App\Services\JsonImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JsonImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function makeExportData(array $overrides = []): array
    {
        return array_merge([
            'version' => '0.3.1',
            'exported_at' => now()->toIso8601String(),
            'tags' => [],
            'companions' => [],
            'breweries' => [],
            'beers' => [],
            'venues' => [],
            'stores' => [],
            'checkins' => [],
            'inventory' => [],
            'collections' => [],
        ], $overrides);
    }

    public function test_preview_returns_counts(): void
    {
        $data = $this->makeExportData([
            'beers' => [['name' => 'A'], ['name' => 'B']],
            'breweries' => [['name' => 'X']],
            'checkins' => [['beer_name' => 'A']],
        ]);

        $preview = JsonImportService::preview($data);

        $this->assertEquals(2, $preview['beers']);
        $this->assertEquals(1, $preview['breweries']);
        $this->assertEquals(1, $preview['checkins']);
        $this->assertEquals('0.3.1', $preview['version']);
    }

    public function test_import_creates_brewery(): void
    {
        $data = $this->makeExportData([
            'breweries' => [[
                'name' => 'Test Brewing',
                'pub_uuid' => 'brew-uuid-1',
                'city' => 'Portland',
                'state' => 'OR',
                'country' => 'US',
            ]],
        ]);

        $service = new JsonImportService($this->user->id);
        $service->import($data);

        $this->assertDatabaseHas('breweries', ['name' => 'Test Brewing', 'pub_uuid' => 'brew-uuid-1']);
        $this->assertEquals(1, $service->results['breweries']['created']);
    }

    public function test_import_skips_existing_brewery_by_pub_uuid(): void
    {
        Brewery::create(['name' => 'Test Brewing', 'pub_uuid' => 'brew-uuid-1']);

        $data = $this->makeExportData([
            'breweries' => [['name' => 'Test Brewing', 'pub_uuid' => 'brew-uuid-1', 'city' => 'Portland']],
        ]);

        $service = new JsonImportService($this->user->id);
        $service->import($data);

        $this->assertEquals(1, $service->results['breweries']['existing']);
        $this->assertEquals(1, Brewery::count());
        // Backfill should have added city
        $this->assertEquals('Portland', Brewery::first()->city);
    }

    public function test_import_creates_beer_with_brewery(): void
    {
        $data = $this->makeExportData([
            'breweries' => [['name' => 'Test Brewing']],
            'beers' => [[
                'name' => 'Hoppy IPA',
                'brewery_name' => 'Test Brewing',
                'pub_uuid' => 'beer-uuid-1',
                'style' => ['IPA'],
                'abv' => 6.5,
                'ibu' => 55,
            ]],
        ]);

        $service = new JsonImportService($this->user->id);
        $service->import($data);

        $this->assertDatabaseHas('beers', ['name' => 'Hoppy IPA', 'pub_uuid' => 'beer-uuid-1']);
        $beer = Beer::where('pub_uuid', 'beer-uuid-1')->first();
        $this->assertNotNull($beer->brewery_id);
        $this->assertEquals('Test Brewing', $beer->brewery->name);
    }

    public function test_import_creates_checkin_with_dedup(): void
    {
        $brewery = Brewery::create(['name' => 'Test Brewing']);
        $beer = Beer::create(['name' => 'Test Beer', 'brewery_id' => $brewery->id]);

        $data = $this->makeExportData([
            'checkins' => [
                [
                    'beer_name' => 'Test Beer',
                    'brewery_name' => 'Test Brewing',
                    'rating' => 4.5,
                    'serving_type' => 'draft',
                    'created_at' => '2026-05-01T12:00:00+00:00',
                ],
                [
                    'beer_name' => 'Test Beer',
                    'brewery_name' => 'Test Brewing',
                    'rating' => 4.5,
                    'serving_type' => 'draft',
                    'created_at' => '2026-05-01T12:00:00+00:00',
                ],
            ],
        ]);

        $service = new JsonImportService($this->user->id);
        $service->import($data);

        $this->assertEquals(1, $service->results['checkins']['created']);
        $this->assertEquals(1, $service->results['checkins']['skipped']);
        $this->assertEquals(1, Checkin::where('user_id', $this->user->id)->count());
    }

    public function test_import_deduplicates_by_untappd_id(): void
    {
        $beer = Beer::create(['name' => 'Test Beer']);
        Checkin::create([
            'user_id' => $this->user->id,
            'beer_id' => $beer->id,
            'untappd_id' => '12345',
        ]);

        $data = $this->makeExportData([
            'checkins' => [[
                'beer_name' => 'Test Beer',
                'untappd_id' => '12345',
                'rating' => 4.0,
                'created_at' => '2026-05-01T12:00:00+00:00',
            ]],
        ]);

        $service = new JsonImportService($this->user->id);
        $service->import($data);

        $this->assertEquals(1, $service->results['checkins']['skipped']);
        $this->assertEquals(1, Checkin::where('user_id', $this->user->id)->count());
    }

    public function test_import_creates_venue(): void
    {
        $data = $this->makeExportData([
            'venues' => [[
                'name' => 'Cool Bar',
                'city' => 'Portland',
                'state' => 'OR',
                'latitude' => 45.5,
                'longitude' => -122.6,
            ]],
        ]);

        $service = new JsonImportService($this->user->id);
        $service->import($data);

        $this->assertDatabaseHas('venues', ['name' => 'Cool Bar', 'city' => 'Portland']);
        $this->assertEquals(1, $service->results['venues']['created']);
    }

    public function test_import_creates_store(): void
    {
        $data = $this->makeExportData([
            'stores' => [['name' => 'Bottle Shop', 'city' => 'Portland']],
        ]);

        $service = new JsonImportService($this->user->id);
        $service->import($data);

        $this->assertDatabaseHas('stores', ['name' => 'Bottle Shop']);
        $this->assertEquals(1, $service->results['stores']['created']);
    }

    public function test_import_creates_inventory(): void
    {
        $beer = Beer::create(['name' => 'Cellar Beer']);

        $data = $this->makeExportData([
            'inventory' => [[
                'beer_name' => 'Cellar Beer',
                'quantity' => 3,
                'storage_location' => 'Garage Fridge',
                'is_gift' => false,
            ]],
        ]);

        $service = new JsonImportService($this->user->id);
        $service->import($data);

        $this->assertEquals(1, $service->results['inventory']['created']);
        $this->assertDatabaseHas('inventory', [
            'beer_id' => $beer->id,
            'user_id' => $this->user->id,
            'quantity' => 3,
            'storage_location' => 'Garage Fridge',
        ]);
    }

    public function test_import_merges_inventory_quantity(): void
    {
        $beer = Beer::create(['name' => 'Cellar Beer']);
        Inventory::create([
            'beer_id' => $beer->id,
            'user_id' => $this->user->id,
            'quantity' => 2,
            'storage_location' => 'Garage Fridge',
        ]);

        $data = $this->makeExportData([
            'inventory' => [[
                'beer_name' => 'Cellar Beer',
                'quantity' => 3,
                'storage_location' => 'Garage Fridge',
            ]],
        ]);

        $service = new JsonImportService($this->user->id);
        $service->import($data);

        $this->assertEquals(1, $service->results['inventory']['merged']);
        $this->assertEquals(5, Inventory::first()->quantity);
    }

    public function test_import_creates_tags_and_attaches_to_beers(): void
    {
        $data = $this->makeExportData([
            'tags' => [['name' => 'Favorites', 'color' => '#ff0000']],
            'beers' => [[
                'name' => 'Tagged Beer',
                'tags' => ['Favorites'],
            ]],
        ]);

        $service = new JsonImportService($this->user->id);
        $service->import($data);

        $this->assertDatabaseHas('tags', ['name' => 'Favorites', 'color' => '#ff0000']);
        $beer = Beer::where('name', 'Tagged Beer')->first();
        $this->assertCount(1, $beer->tags);
        $this->assertEquals('Favorites', $beer->tags->first()->name);
    }

    public function test_import_creates_companions(): void
    {
        $data = $this->makeExportData([
            'companions' => [['name' => 'Drinking Buddy']],
        ]);

        $service = new JsonImportService($this->user->id);
        $service->import($data);

        $this->assertDatabaseHas('companions', ['name' => 'Drinking Buddy']);
        $this->assertEquals(1, $service->results['companions']['created']);
    }

    public function test_import_creates_static_collection_with_beers(): void
    {
        $beer = Beer::create(['name' => 'Collection Beer']);

        $data = $this->makeExportData([
            'collections' => [[
                'name' => 'My IPAs',
                'description' => 'All the IPAs',
                'is_dynamic' => false,
                'rules' => null,
                'beers' => [['name' => 'Collection Beer', 'sort_order' => 0]],
            ]],
        ]);

        $service = new JsonImportService($this->user->id);
        $service->import($data);

        $this->assertDatabaseHas('collections', ['name' => 'My IPAs', 'user_id' => $this->user->id]);
        $collection = Collection::where('name', 'My IPAs')->first();
        $this->assertCount(1, $collection->beers);
    }

    public function test_import_creates_dynamic_collection(): void
    {
        $data = $this->makeExportData([
            'collections' => [[
                'name' => 'High Rated',
                'is_dynamic' => true,
                'rules' => ['min_rating' => 4.0],
                'beers' => [],
            ]],
        ]);

        $service = new JsonImportService($this->user->id);
        $service->import($data);

        $collection = Collection::where('name', 'High Rated')->first();
        $this->assertTrue($collection->is_dynamic);
        $this->assertEquals(['min_rating' => 4.0], $collection->rules);
    }

    public function test_import_skips_duplicate_collection(): void
    {
        Collection::create(['user_id' => $this->user->id, 'name' => 'My IPAs']);

        $data = $this->makeExportData([
            'collections' => [['name' => 'My IPAs', 'is_dynamic' => false, 'beers' => []]],
        ]);

        $service = new JsonImportService($this->user->id);
        $service->import($data);

        $this->assertEquals(1, $service->results['collections']['existing']);
        $this->assertEquals(1, Collection::count());
    }

    public function test_full_round_trip_export_import(): void
    {
        $brewery = Brewery::create(['name' => 'Round Trip Brewing', 'city' => 'Portland', 'state' => 'OR']);
        $beer = Beer::create(['name' => 'Round Trip IPA', 'brewery_id' => $brewery->id, 'style' => ['IPA'], 'abv' => 6.5]);
        $venue = Venue::create(['name' => 'Test Pub', 'city' => 'Portland']);
        $checkin = Checkin::create([
            'user_id' => $this->user->id,
            'beer_id' => $beer->id,
            'venue_id' => $venue->id,
            'rating' => 4.5,
            'serving_type' => 'draft',
        ]);

        // Export
        $response = $this->actingAs($this->user)->get('/export');
        $response->assertOk();

        $exportData = json_decode($response->streamedContent(), true);

        $this->assertNotEmpty($exportData['beers']);
        $this->assertNotEmpty($exportData['breweries']);
        $this->assertNotEmpty($exportData['checkins']);
        $this->assertNotEmpty($exportData['venues']);

        // Clear and re-import
        Checkin::query()->delete();
        Beer::query()->delete();
        Brewery::query()->delete();
        Venue::query()->delete();

        $service = new JsonImportService($this->user->id);
        $service->import($exportData);

        $this->assertEquals(1, $service->results['breweries']['created']);
        $this->assertEquals(1, $service->results['beers']['created']);
        $this->assertEquals(1, $service->results['checkins']['created']);
        $this->assertEquals(1, $service->results['venues']['created']);

        $this->assertDatabaseHas('beers', ['name' => 'Round Trip IPA']);
        $this->assertDatabaseHas('breweries', ['name' => 'Round Trip Brewing']);
    }
}
