<?php

namespace Tests\Unit;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Inventory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_can_be_created_with_all_fillable_fields(): void
    {
        $store = Store::create([
            'name' => 'Bottle Shop',
            'address' => '123 Main St',
            'city' => 'Portland',
            'state' => 'Oregon',
            'country' => 'US',
            'latitude' => 45.5152,
            'longitude' => -122.6784,
            'website' => 'https://bottleshop.example.com',
        ]);

        $this->assertDatabaseHas('stores', [
            'name' => 'Bottle Shop',
            'address' => '123 Main St',
            'city' => 'Portland',
            'state' => 'Oregon',
            'country' => 'US',
            'website' => 'https://bottleshop.example.com',
        ]);

        $this->assertEquals(45.5152, $store->latitude);
        $this->assertEquals(-122.6784, $store->longitude);
    }

    public function test_store_has_inventory_relationship(): void
    {
        $store = Store::create(['name' => 'Test Store']);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $store->inventory());
    }

    public function test_display_location_works_on_store(): void
    {
        $store = Store::create([
            'name' => 'Bottle Shop',
            'city' => 'Portland',
            'state' => 'Oregon',
            'country' => 'US',
        ]);

        $this->assertEquals('Portland, Oregon, US', $store->displayLocation());
    }

    public function test_store_without_address_fields_returns_empty_display_location(): void
    {
        $store = Store::create([
            'name' => 'Unnamed Store',
        ]);

        $this->assertEquals('', $store->displayLocation());
    }

    public function test_inventory_relationship_returns_correct_items(): void
    {
        $store = Store::create(['name' => 'Test Store']);
        $user = User::create([
            'name' => 'Test User',
            'username' => 'testuser',
            'password' => 'password',
        ]);
        $brewery = Brewery::create(['name' => 'Test Brewery']);
        $beer = Beer::create([
            'name' => 'Test IPA',
            'brewery_id' => $brewery->id,
        ]);

        Inventory::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'beer_id' => $beer->id,
            'quantity' => 6,
        ]);

        Inventory::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'beer_id' => $beer->id,
            'quantity' => 2,
        ]);

        $this->assertCount(2, $store->inventory);
        $this->assertEquals(6, $store->inventory->first()->quantity);
    }
}
