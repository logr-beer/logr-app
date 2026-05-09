<?php

namespace Tests\Feature;

use App\Livewire\BeerShow;
use App\Livewire\CollectionIndex;
use App\Livewire\CollectionShow;
use App\Livewire\InventoryIndex;
use App\Models\Beer;
use App\Models\Collection;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CollectionInventoryTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────
    //  Collections
    // ──────────────────────────────────────────────

    public function test_create_static_collection(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CollectionIndex::class)
            ->set('name', 'My IPAs')
            ->set('description', 'Hoppy favourites')
            ->call('createCollection');

        $this->assertDatabaseHas('collections', [
            'user_id' => $user->id,
            'name' => 'My IPAs',
            'is_dynamic' => false,
        ]);
    }

    public function test_create_dynamic_collection(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CollectionIndex::class)
            ->set('dynamicType', 'favorites')
            ->call('createDynamicCollection');

        $collection = Collection::where('user_id', $user->id)
            ->where('is_dynamic', true)
            ->first();

        $this->assertNotNull($collection);
        $this->assertEquals('Favorites', $collection->name);
        $this->assertTrue($collection->rules['favorites']);
    }

    public function test_add_beer_to_collection(): void
    {
        $user = User::factory()->create();
        $collection = Collection::create([
            'user_id' => $user->id,
            'name' => 'Test Collection',
        ]);
        $beer = Beer::create(['name' => 'Test IPA']);

        Livewire::actingAs($user)
            ->test(CollectionShow::class, ['collection' => $collection])
            ->call('addBeer', $beer->id);

        $this->assertTrue($collection->beers()->where('beers.id', $beer->id)->exists());
    }

    public function test_remove_beer_from_collection(): void
    {
        $user = User::factory()->create();
        $collection = Collection::create([
            'user_id' => $user->id,
            'name' => 'Test Collection',
        ]);
        $beer = Beer::create(['name' => 'Test IPA']);
        $collection->beers()->attach($beer->id, ['sort_order' => 1]);

        Livewire::actingAs($user)
            ->test(CollectionShow::class, ['collection' => $collection])
            ->call('removeBeer', $beer->id);

        $this->assertFalse($collection->beers()->where('beers.id', $beer->id)->exists());
    }

    public function test_update_collection(): void
    {
        $user = User::factory()->create();
        $collection = Collection::create([
            'user_id' => $user->id,
            'name' => 'Old Name',
        ]);

        Livewire::actingAs($user)
            ->test(CollectionShow::class, ['collection' => $collection])
            ->set('editName', 'New Name')
            ->set('editDescription', 'Updated description')
            ->call('updateCollection');

        $collection->refresh();
        $this->assertEquals('New Name', $collection->name);
        $this->assertEquals('Updated description', $collection->description);
    }

    public function test_delete_collection_keeps_beers(): void
    {
        $user = User::factory()->create();
        $collection = Collection::create([
            'user_id' => $user->id,
            'name' => 'Delete Me',
        ]);
        $beer = Beer::create(['name' => 'Survivor Beer']);
        $collection->beers()->attach($beer->id, ['sort_order' => 1]);

        Livewire::actingAs($user)
            ->test(CollectionShow::class, ['collection' => $collection])
            ->call('deleteCollection');

        $this->assertDatabaseMissing('collections', ['id' => $collection->id]);
        $this->assertDatabaseHas('beers', ['id' => $beer->id]);
    }

    public function test_collection_authorization(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $collection = Collection::create([
            'user_id' => $owner->id,
            'name' => 'Private Collection',
        ]);

        Livewire::actingAs($other)
            ->test(CollectionShow::class, ['collection' => $collection])
            ->assertStatus(403);
    }

    // ──────────────────────────────────────────────
    //  Inventory
    // ──────────────────────────────────────────────

    public function test_add_to_fridge(): void
    {
        $user = User::factory()->create();
        $beer = Beer::create(['name' => 'Fridge Beer']);

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer])
            ->set('storageLocation', 'Fridge')
            ->call('addToFridge');

        $this->assertDatabaseHas('inventory', [
            'beer_id' => $beer->id,
            'user_id' => $user->id,
            'storage_location' => 'Fridge',
            'quantity' => 1,
        ]);
    }

    public function test_add_multiple_to_fridge(): void
    {
        $user = User::factory()->create();
        $beer = Beer::create(['name' => 'Stacked Beer']);

        $component = Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer]);

        $component->set('storageLocation', 'Fridge')->call('addToFridge');
        $component->set('storageLocation', 'Fridge')->call('addToFridge');

        $inventory = Inventory::where('beer_id', $beer->id)
            ->where('user_id', $user->id)
            ->where('storage_location', 'Fridge')
            ->first();

        $this->assertNotNull($inventory);
        $this->assertEquals(2, $inventory->quantity);
    }

    public function test_remove_from_fridge_decrements(): void
    {
        $user = User::factory()->create();
        $beer = Beer::create(['name' => 'Decrement Beer']);
        $inventory = Inventory::create([
            'beer_id' => $beer->id,
            'user_id' => $user->id,
            'quantity' => 2,
            'storage_location' => 'Fridge',
        ]);

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer])
            ->call('removeFromFridge', $inventory->id);

        $inventory->refresh();
        $this->assertEquals(1, $inventory->quantity);
    }

    public function test_delete_inventory_item(): void
    {
        $user = User::factory()->create();
        $beer = Beer::create(['name' => 'Delete Inventory Beer']);
        $inventory = Inventory::create([
            'beer_id' => $beer->id,
            'user_id' => $user->id,
            'quantity' => 3,
            'storage_location' => 'Cellar',
        ]);

        Livewire::actingAs($user)
            ->test(InventoryIndex::class)
            ->call('deleteItem', $inventory->id);

        $this->assertDatabaseMissing('inventory', ['id' => $inventory->id]);
    }
}
