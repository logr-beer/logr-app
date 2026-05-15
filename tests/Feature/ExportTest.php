<?php

namespace Tests\Feature;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\Collection;
use App\Models\Inventory;
use App\Models\Store;
use App\Models\Tag;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_export_requires_auth(): void
    {
        $this->get('/export')->assertRedirect('/login');
    }

    public function test_export_blocked_in_demo_mode(): void
    {
        config(['app.demo_mode' => true]);

        $this->actingAs($this->user)->get('/export')->assertForbidden();
    }

    public function test_export_returns_json(): void
    {
        $response = $this->actingAs($this->user)->get('/export');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_export_includes_all_sections(): void
    {
        $response = $this->actingAs($this->user)->get('/export');

        $data = json_decode($response->streamedContent(), true);

        $this->assertArrayHasKey('exported_at', $data);
        $this->assertArrayHasKey('version', $data);
        $this->assertArrayHasKey('tags', $data);
        $this->assertArrayHasKey('companions', $data);
        $this->assertArrayHasKey('breweries', $data);
        $this->assertArrayHasKey('beers', $data);
        $this->assertArrayHasKey('venues', $data);
        $this->assertArrayHasKey('stores', $data);
        $this->assertArrayHasKey('checkins', $data);
        $this->assertArrayHasKey('inventory', $data);
        $this->assertArrayHasKey('collections', $data);
    }

    public function test_export_includes_beer_data(): void
    {
        $brewery = Brewery::create([
            'name' => 'Export Brewing',
            'pub_uuid' => 'brew-export-1',
            'city' => 'Portland',
            'state' => 'OR',
        ]);
        $beer = Beer::create([
            'name' => 'Export IPA',
            'pub_uuid' => 'beer-export-1',
            'brewery_id' => $brewery->id,
            'style' => ['IPA'],
            'abv' => 7.0,
            'is_favorite' => true,
        ]);
        Checkin::create(['user_id' => $this->user->id, 'beer_id' => $beer->id, 'rating' => 4.5]);

        $data = json_decode($this->actingAs($this->user)->get('/export')->streamedContent(), true);

        $this->assertCount(1, $data['beers']);
        $this->assertEquals('Export IPA', $data['beers'][0]['name']);
        $this->assertEquals('beer-export-1', $data['beers'][0]['pub_uuid']);
        $this->assertEquals('Export Brewing', $data['beers'][0]['brewery_name']);
        $this->assertTrue($data['beers'][0]['is_favorite']);

        $this->assertCount(1, $data['breweries']);
        $this->assertEquals('brew-export-1', $data['breweries'][0]['pub_uuid']);
    }

    public function test_export_includes_checkin_with_venue(): void
    {
        $beer = Beer::create(['name' => 'Test Beer']);
        $venue = Venue::create(['name' => 'Cool Bar', 'city' => 'Portland']);
        Checkin::create([
            'user_id' => $this->user->id,
            'beer_id' => $beer->id,
            'venue_id' => $venue->id,
            'rating' => 4.0,
            'serving_type' => 'draft',
            'notes' => 'Great beer',
        ]);

        $data = json_decode($this->actingAs($this->user)->get('/export')->streamedContent(), true);

        $this->assertCount(1, $data['checkins']);
        $this->assertEquals('Cool Bar', $data['checkins'][0]['venue_name']);
        $this->assertEquals(4.0, $data['checkins'][0]['rating']);
        $this->assertEquals('draft', $data['checkins'][0]['serving_type']);
    }

    public function test_export_includes_inventory(): void
    {
        $beer = Beer::create(['name' => 'Cellared Beer']);
        $store = Store::create(['name' => 'Bottle Shop']);
        Inventory::create([
            'beer_id' => $beer->id,
            'user_id' => $this->user->id,
            'store_id' => $store->id,
            'quantity' => 3,
            'storage_location' => 'Garage Fridge',
        ]);

        $data = json_decode($this->actingAs($this->user)->get('/export')->streamedContent(), true);

        $this->assertCount(1, $data['inventory']);
        $this->assertEquals('Cellared Beer', $data['inventory'][0]['beer_name']);
        $this->assertEquals('Bottle Shop', $data['inventory'][0]['store_name']);
        $this->assertEquals(3, $data['inventory'][0]['quantity']);
    }

    public function test_export_scoped_to_user(): void
    {
        $otherUser = User::factory()->create();
        $beer = Beer::create(['name' => 'Shared Beer']);

        Checkin::create(['user_id' => $this->user->id, 'beer_id' => $beer->id]);
        Checkin::create(['user_id' => $otherUser->id, 'beer_id' => $beer->id]);

        $data = json_decode($this->actingAs($this->user)->get('/export')->streamedContent(), true);

        $this->assertCount(1, $data['checkins']);
    }

    public function test_export_does_not_leak_sensitive_data(): void
    {
        $beer = Beer::create(['name' => 'Test Beer']);
        Checkin::create(['user_id' => $this->user->id, 'beer_id' => $beer->id]);

        $json = $this->actingAs($this->user)->get('/export')->streamedContent();

        $this->assertStringNotContainsString('password', $json);
        $this->assertStringNotContainsString('api_key', $json);
        $this->assertStringNotContainsString('secret', $json);
    }
}
