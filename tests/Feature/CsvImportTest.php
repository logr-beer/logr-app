<?php

namespace Tests\Feature;

use App\Livewire\CsvImport;
use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\Inventory;
use App\Models\Store;
use App\Models\User;
use App\Services\GeocodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class CsvImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.demo_mode' => false]);

        $this->user = User::factory()->create();

        // Mock geocoding to avoid external HTTP calls
        $this->mock(GeocodingService::class, function ($mock) {
            $mock->shouldReceive('geocode')->andReturn(null);
        });
    }

    private function makeCsvFile(string $content, string $name = 'test.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    private function uploadAndGetComponent(string $csv, string $importType = 'checkins'): \Livewire\Features\SupportTesting\Testable
    {
        $file = $this->makeCsvFile($csv);

        return Livewire::actingAs($this->user)
            ->test(CsvImport::class)
            ->set('importType', $importType)
            ->set('csvFile', $file);
    }

    private function runFullImport(string $csv, string $importType = 'checkins', array $mappingOverrides = []): \Livewire\Features\SupportTesting\Testable
    {
        $component = $this->uploadAndGetComponent($csv, $importType)
            ->assertSet('step', 'map');

        if ($mappingOverrides) {
            foreach ($mappingOverrides as $header => $field) {
                $component->set("mapping.{$header}", $field);
            }
        }

        return $component
            ->call('startPreview')
            ->assertSet('step', 'preview')
            ->call('runImport')
            ->assertSet('step', 'results');
    }

    // ── Auto-mapping tests ──────────────────────────────────────────────

    public function test_auto_maps_beer_name_header(): void
    {
        $csv = "beer_name\nTest IPA\n";
        $component = $this->uploadAndGetComponent($csv);

        $component->assertSet('mapping.beer_name', 'beer_name');
    }

    public function test_auto_maps_brewery_name_header(): void
    {
        $csv = "brewery_name\nTest Brewing\n";
        $component = $this->uploadAndGetComponent($csv);

        $component->assertSet('mapping.brewery_name', 'brewery_name');
    }

    public function test_auto_maps_rating_score_header(): void
    {
        $csv = "rating_score\n4.5\n";
        $component = $this->uploadAndGetComponent($csv);

        $component->assertSet('mapping.rating_score', 'rating');
    }

    public function test_auto_maps_venue_name_header(): void
    {
        $csv = "venue_name\nSome Bar\n";
        $component = $this->uploadAndGetComponent($csv);

        $component->assertSet('mapping.venue_name', 'venue_name');
    }

    public function test_auto_maps_store_alias_to_purchase_location(): void
    {
        $csv = "store\nBevMo\n";
        $component = $this->uploadAndGetComponent($csv, 'inventory');

        $component->assertSet('mapping.store', 'purchase_location');
    }

    public function test_unknown_headers_map_to_empty(): void
    {
        $csv = "some_random_column\nfoo\n";
        $component = $this->uploadAndGetComponent($csv);

        $component->assertSet('mapping.some_random_column', '');
    }

    // ── Import - Checkins type ──────────────────────────────────────────

    public function test_creates_beer_from_csv_row(): void
    {
        $csv = "beer_name,brewery_name\nTest IPA,Test Brewing\n";
        $this->runFullImport($csv);

        $this->assertDatabaseHas('beers', ['name' => 'Test IPA']);
    }

    public function test_creates_brewery_from_csv_row(): void
    {
        $csv = "beer_name,brewery_name\nTest IPA,Test Brewing\n";
        $this->runFullImport($csv);

        $this->assertDatabaseHas('breweries', ['name' => 'Test Brewing']);
    }

    public function test_creates_venue_from_csv_row_with_city_and_state(): void
    {
        $csv = "beer_name,venue_name,venue_city,venue_state\nTest IPA,The Pub,Portland,OR\n";
        $this->runFullImport($csv);

        $this->assertDatabaseHas('venues', [
            'name' => 'The Pub',
            'city' => 'Portland',
            'state' => 'OR',
        ]);
    }

    public function test_creates_checkin_with_rating_and_serving_type(): void
    {
        $csv = "beer_name,rating_score,serving_type\nTest IPA,4.5,can\n";
        $this->runFullImport($csv);

        $this->assertDatabaseHas('checkins', [
            'user_id' => $this->user->id,
            'rating' => 4.5,
            'serving_type' => 'can',
        ]);
    }

    public function test_sets_checkin_date_from_csv(): void
    {
        $csv = "beer_name,created_at\nTest IPA,2024-06-15 14:30:00\n";
        $this->runFullImport($csv);

        $checkin = Checkin::first();
        $this->assertNotNull($checkin);
        $this->assertEquals('2024-06-15', $checkin->created_at->toDateString());
    }

    public function test_skips_duplicate_untappd_checkins(): void
    {
        $beer = Beer::create(['name' => 'Existing IPA']);
        Checkin::create([
            'user_id' => $this->user->id,
            'beer_id' => $beer->id,
            'untappd_id' => '12345',
        ]);

        $csv = "beer_name,checkin_id\nExisting IPA,12345\n";
        $component = $this->runFullImport($csv);

        $this->assertEquals(1, Checkin::count());
        $component->assertSet('results.skipped', 1);
    }

    public function test_backfills_missing_brewery_location_on_existing_brewery(): void
    {
        Brewery::create(['name' => 'Existing Brewery']);

        $csv = "beer_name,brewery_name,brewery_city,brewery_state\nTest IPA,Existing Brewery,Portland,OR\n";
        $this->runFullImport($csv);

        $this->assertDatabaseHas('breweries', [
            'name' => 'Existing Brewery',
            'city' => 'Portland',
            'state' => 'OR',
        ]);
    }

    public function test_skips_rows_with_no_beer_name(): void
    {
        $csv = "beer_name,brewery_name\n,Test Brewing\n";
        $component = $this->runFullImport($csv);

        $this->assertEquals(0, Beer::count());
        $component->assertSet('results.skipped', 1);
    }

    // ── Import - Inventory type ─────────────────────────────────────────

    public function test_creates_inventory_with_quantity_and_storage_location(): void
    {
        $csv = "beer_name,quantity,storage_location\nTest IPA,3,Garage Fridge\n";
        $this->runFullImport($csv, 'inventory');

        $this->assertDatabaseHas('inventory', [
            'quantity' => 3,
            'storage_location' => 'Garage Fridge',
        ]);
    }

    public function test_creates_store_record_from_purchase_location(): void
    {
        $csv = "beer_name,store\nTest IPA,BevMo\n";
        $this->runFullImport($csv, 'inventory');

        $this->assertDatabaseHas('stores', ['name' => 'BevMo']);

        $inventory = Inventory::first();
        $this->assertNotNull($inventory);
        $this->assertNotNull($inventory->store_id);
        $this->assertEquals('BevMo', Store::find($inventory->store_id)->name);
    }

    public function test_defaults_quantity_to_one_when_not_specified(): void
    {
        $csv = "beer_name\nTest IPA\n";
        $this->runFullImport($csv, 'inventory');

        $this->assertDatabaseHas('inventory', [
            'quantity' => 1,
        ]);
    }

    public function test_merges_inventory_with_existing_same_beer_and_storage_location(): void
    {
        $beer = Beer::create(['name' => 'Test IPA']);
        Inventory::create([
            'beer_id' => $beer->id,
            'user_id' => $this->user->id,
            'quantity' => 2,
            'storage_location' => 'Garage Fridge',
        ]);

        $csv = "beer_name,quantity,storage_location\nTest IPA,3,Garage Fridge\n";
        $this->runFullImport($csv, 'inventory');

        $this->assertEquals(1, Inventory::count());
        $this->assertEquals(5, Inventory::first()->quantity);
    }

    // ── Import - Both type ──────────────────────────────────────────────

    public function test_creates_both_checkin_and_inventory_from_same_row(): void
    {
        $csv = "beer_name,rating_score,quantity,storage_location\nTest IPA,4.0,2,Cellar\n";
        $this->runFullImport($csv, 'both');

        $this->assertDatabaseHas('checkins', [
            'user_id' => $this->user->id,
            'rating' => 4.0,
        ]);
        $this->assertDatabaseHas('inventory', [
            'user_id' => $this->user->id,
            'quantity' => 2,
            'storage_location' => 'Cellar',
        ]);
    }

    // ── Style parsing ───────────────────────────────────────────────────

    public function test_parses_comma_separated_styles(): void
    {
        $csv = "beer_name,beer_style\nTest IPA,\"IPA, Hazy\"\n";
        $this->runFullImport($csv);

        $beer = Beer::where('name', 'Test IPA')->first();
        $this->assertEquals(['IPA', 'Hazy'], $beer->style);
    }

    public function test_parses_slash_separated_styles(): void
    {
        $csv = "beer_name,beer_style\nTest IPA,IPA / New England\n";
        $this->runFullImport($csv);

        $beer = Beer::where('name', 'Test IPA')->first();
        $this->assertEquals(['IPA', 'New England'], $beer->style);
    }
}
