<?php

namespace Tests\Feature;

use App\Livewire\BeerIndex;
use App\Livewire\BeerShow;
use App\Livewire\Dashboard;
use App\Models\Beer;
use App\Models\Collection;
use App\Models\Inventory;
use App\Models\User;
use App\Services\JsonImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PerUserDataTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;

    private User $bob;

    protected function setUp(): void
    {
        parent::setUp();
        $this->alice = User::factory()->create();
        $this->bob = User::factory()->create();
    }

    public function test_fridge_collection_excludes_other_users_inventory(): void
    {
        $mine = Beer::create(['name' => 'Alice Lager']);
        $theirs = Beer::create(['name' => 'Bob Stout']);
        Inventory::create(['user_id' => $this->alice->id, 'beer_id' => $mine->id, 'quantity' => 1, 'storage_location' => 'Fridge']);
        Inventory::create(['user_id' => $this->bob->id, 'beer_id' => $theirs->id, 'quantity' => 2, 'storage_location' => 'Fridge']);

        $fridge = Collection::create([
            'user_id' => $this->alice->id,
            'name' => 'Fridge',
            'is_dynamic' => true,
            'rules' => ['storage_location' => 'Fridge'],
        ]);

        $this->assertEquals(['Alice Lager'], $fridge->resolveBeers()->pluck('name')->all());
        $this->assertEquals(1, $fridge->resolveBeersCount());
    }

    public function test_oldest_in_stock_collection_excludes_other_users_inventory(): void
    {
        $mine = Beer::create(['name' => 'Alice Lager']);
        $theirs = Beer::create(['name' => 'Bob Stout']);
        Inventory::create(['user_id' => $this->alice->id, 'beer_id' => $mine->id, 'quantity' => 1]);
        Inventory::create(['user_id' => $this->bob->id, 'beer_id' => $theirs->id, 'quantity' => 1]);

        $collection = Collection::create([
            'user_id' => $this->alice->id,
            'name' => 'Oldest',
            'is_dynamic' => true,
            'rules' => ['oldest_in_stock' => true],
        ]);

        $this->assertEquals(['Alice Lager'], $collection->resolveBeers()->pluck('name')->all());
    }

    public function test_favoriting_does_not_change_other_users_favorites(): void
    {
        $beer = Beer::create(['name' => 'Shared IPA']);

        Livewire::actingAs($this->alice)
            ->test(BeerShow::class, ['beer' => $beer])
            ->call('toggleFavorite');

        $this->assertTrue($this->alice->favoriteBeers()->whereKey($beer->id)->exists());
        $this->assertFalse($this->bob->favoriteBeers()->whereKey($beer->id)->exists());

        $this->actingAs($this->bob);
        $this->assertFalse($beer->fresh()->is_favorite);
        $this->actingAs($this->alice);
        $this->assertTrue($beer->fresh()->is_favorite);
    }

    public function test_unfavoriting_keeps_other_users_favorite(): void
    {
        $beer = Beer::create(['name' => 'Shared IPA']);
        $this->alice->favoriteBeers()->attach($beer);
        $this->bob->favoriteBeers()->attach($beer);

        Livewire::actingAs($this->alice)->test(Dashboard::class)->call('toggleFavorite', $beer->id);

        $this->assertFalse($this->alice->favoriteBeers()->whereKey($beer->id)->exists());
        $this->assertTrue($this->bob->favoriteBeers()->whereKey($beer->id)->exists());
    }

    public function test_bulk_favorite_and_unfavorite_are_per_user(): void
    {
        $a = Beer::create(['name' => 'A']);
        $b = Beer::create(['name' => 'B']);
        $this->bob->favoriteBeers()->attach($a);

        Livewire::actingAs($this->alice)
            ->test(BeerIndex::class)
            ->set('selected', [$a->id, $b->id])
            ->call('favoriteSelected');

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $this->alice->favoriteBeers()->pluck('beers.id')->all());

        Livewire::actingAs($this->alice)
            ->test(BeerIndex::class)
            ->set('selected', [$a->id])
            ->call('unfavoriteSelected');

        $this->assertEquals([$b->id], $this->alice->favoriteBeers()->pluck('beers.id')->all());
        $this->assertEquals([$a->id], $this->bob->favoriteBeers()->pluck('beers.id')->all());
    }

    public function test_favorites_filter_and_collection_use_own_favorites(): void
    {
        $mine = Beer::create(['name' => 'Alice Fave']);
        $theirs = Beer::create(['name' => 'Bob Fave']);
        $this->alice->favoriteBeers()->attach($mine);
        $this->bob->favoriteBeers()->attach($theirs);

        Livewire::actingAs($this->alice)
            ->test(BeerIndex::class)
            ->set('filter', 'favorites')
            ->assertSee('Alice Fave')
            ->assertDontSee('Bob Fave');

        $collection = Collection::create([
            'user_id' => $this->alice->id,
            'name' => 'Favorites',
            'is_dynamic' => true,
            'rules' => ['favorites' => true],
        ]);

        $this->assertEquals(['Alice Fave'], $collection->resolveBeers()->pluck('name')->all());
    }

    public function test_export_import_round_trips_favorites(): void
    {
        $fave = Beer::create(['name' => 'Fave Pils']);
        $other = Beer::create(['name' => 'Plain Ale']);
        Inventory::create(['user_id' => $this->alice->id, 'beer_id' => $other->id, 'quantity' => 1]);
        $this->alice->favoriteBeers()->attach($fave);
        $this->bob->favoriteBeers()->attach($other);

        $export = json_decode($this->actingAs($this->alice)->get('/export')->streamedContent(), true);

        $exported = collect($export['beers'])->pluck('is_favorite', 'name')->all();
        $this->assertSame(['Fave Pils' => true, 'Plain Ale' => false], $exported);

        // Import Alice's export as a third user: only her favorites come along.
        $carol = User::factory()->create();
        (new JsonImportService($carol->id))->import($export);

        $this->assertEquals([$fave->id], $carol->favoriteBeers()->pluck('beers.id')->all());
        $this->assertEquals([$other->id], $this->bob->favoriteBeers()->pluck('beers.id')->all());
    }
}
