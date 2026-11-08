<?php

namespace Tests\Feature;

use App\Livewire\BeerForm;
use App\Livewire\BeerIndex;
use App\Livewire\BeerShow;
use App\Livewire\LocationShow;
use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\Inventory;
use App\Models\Store;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class SharedCatalogDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $userA;

    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        // The first user created is the admin.
        $this->admin = User::factory()->create();
        $this->userA = User::factory()->create();
        $this->userB = User::factory()->create();
    }

    // ── Beers ───────────────────────────────────────────────────────

    public function test_beer_show_delete_blocked_when_other_user_has_checkin(): void
    {
        $beer = Beer::create(['name' => 'Shared Beer']);
        $checkin = Checkin::create(['user_id' => $this->userA->id, 'beer_id' => $beer->id]);

        Livewire::actingAs($this->userB)
            ->test(BeerShow::class, ['beer' => $beer])
            ->assertDontSeeHtml('wire:click="deleteBeer"')
            ->call('deleteBeer')
            ->assertForbidden();

        $this->assertDatabaseHas('beers', ['id' => $beer->id]);
        $this->assertDatabaseHas('checkins', ['id' => $checkin->id]);
    }

    public function test_beer_show_delete_blocked_when_other_user_has_inventory(): void
    {
        $beer = Beer::create(['name' => 'Shared Beer']);
        $inventory = Inventory::create(['user_id' => $this->userA->id, 'beer_id' => $beer->id, 'quantity' => 1]);

        Livewire::actingAs($this->userB)
            ->test(BeerShow::class, ['beer' => $beer])
            ->call('deleteBeer')
            ->assertForbidden();

        $this->assertDatabaseHas('beers', ['id' => $beer->id]);
        $this->assertDatabaseHas('inventory', ['id' => $inventory->id]);
    }

    public function test_beer_form_delete_blocked_when_other_user_has_checkin_or_inventory(): void
    {
        $beer = Beer::create(['name' => 'Shared Beer']);
        $checkin = Checkin::create(['user_id' => $this->userA->id, 'beer_id' => $beer->id]);
        $inventory = Inventory::create(['user_id' => $this->userA->id, 'beer_id' => $beer->id, 'quantity' => 1]);

        Livewire::actingAs($this->userB)
            ->test(BeerForm::class, ['beer' => $beer])
            ->assertDontSeeHtml('wire:click="deleteBeer"')
            ->call('deleteBeer')
            ->assertForbidden();

        $this->assertDatabaseHas('beers', ['id' => $beer->id]);
        $this->assertDatabaseHas('checkins', ['id' => $checkin->id]);
        $this->assertDatabaseHas('inventory', ['id' => $inventory->id]);
    }

    public function test_user_can_delete_beer_only_they_use(): void
    {
        $beer = Beer::create(['name' => 'My Beer']);
        Checkin::create(['user_id' => $this->userB->id, 'beer_id' => $beer->id]);
        Inventory::create(['user_id' => $this->userB->id, 'beer_id' => $beer->id, 'quantity' => 1]);

        Livewire::actingAs($this->userB)
            ->test(BeerShow::class, ['beer' => $beer])
            ->assertSeeHtml('wire:click="deleteBeer"')
            ->call('deleteBeer')
            ->assertRedirect(route('beers.index'));

        $this->assertDatabaseMissing('beers', ['id' => $beer->id]);
        $this->assertDatabaseCount('checkins', 0);
        $this->assertDatabaseCount('inventory', 0);
    }

    public function test_admin_can_delete_shared_beer(): void
    {
        $beer = Beer::create(['name' => 'Shared Beer']);
        Checkin::create(['user_id' => $this->userA->id, 'beer_id' => $beer->id]);
        Inventory::create(['user_id' => $this->userA->id, 'beer_id' => $beer->id, 'quantity' => 1]);

        Livewire::actingAs($this->admin)
            ->test(BeerForm::class, ['beer' => $beer])
            ->assertSeeHtml('wire:click="deleteBeer"')
            ->call('deleteBeer')
            ->assertRedirect(route('beers.index'));

        $this->assertDatabaseMissing('beers', ['id' => $beer->id]);
    }

    public function test_bulk_delete_skips_beers_with_other_users_inventory(): void
    {
        $shared = Beer::create(['name' => 'Shared Beer']);
        $mine = Beer::create(['name' => 'My Beer']);
        $inventory = Inventory::create(['user_id' => $this->userA->id, 'beer_id' => $shared->id, 'quantity' => 2]);

        Livewire::actingAs($this->userB)
            ->test(BeerIndex::class)
            ->set('selected', [$shared->id, $mine->id])
            ->call('deleteSelected');

        $this->assertDatabaseHas('beers', ['id' => $shared->id]);
        $this->assertDatabaseHas('inventory', ['id' => $inventory->id]);
        $this->assertDatabaseMissing('beers', ['id' => $mine->id]);
    }

    public function test_admin_can_bulk_delete_shared_beers(): void
    {
        $beer = Beer::create(['name' => 'Shared Beer']);
        Inventory::create(['user_id' => $this->userA->id, 'beer_id' => $beer->id, 'quantity' => 2]);

        Livewire::actingAs($this->admin)
            ->test(BeerIndex::class)
            ->set('selected', [$beer->id])
            ->call('deleteSelected');

        $this->assertDatabaseMissing('beers', ['id' => $beer->id]);
    }

    // ── Locations ───────────────────────────────────────────────────

    public function test_brewery_delete_blocked_when_other_user_has_checkin(): void
    {
        $brewery = Brewery::create(['name' => 'Shared Brewery']);
        $beer = Beer::create(['name' => 'Shared Beer', 'brewery_id' => $brewery->id]);
        $checkin = Checkin::create(['user_id' => $this->userA->id, 'beer_id' => $beer->id]);

        Livewire::actingAs($this->userB)
            ->test(LocationShow::class, ['location' => $brewery])
            ->call('edit')
            ->assertDontSeeHtml('wire:click="delete"')
            ->call('delete')
            ->assertForbidden();

        $this->assertDatabaseHas('breweries', ['id' => $brewery->id]);
        $this->assertDatabaseHas('beers', ['id' => $beer->id, 'brewery_id' => $brewery->id]);
        $this->assertDatabaseHas('checkins', ['id' => $checkin->id]);
    }

    public function test_brewery_delete_blocked_when_other_user_has_inventory(): void
    {
        $brewery = Brewery::create(['name' => 'Shared Brewery']);
        $beer = Beer::create(['name' => 'Shared Beer', 'brewery_id' => $brewery->id]);
        Inventory::create(['user_id' => $this->userA->id, 'beer_id' => $beer->id, 'quantity' => 1]);

        Livewire::actingAs($this->userB)
            ->test(LocationShow::class, ['location' => $brewery])
            ->call('delete')
            ->assertForbidden();

        $this->assertDatabaseHas('breweries', ['id' => $brewery->id]);
    }

    public function test_store_delete_blocked_when_other_user_has_inventory(): void
    {
        $store = Store::create(['name' => 'Shared Store']);
        $beer = Beer::create(['name' => 'Shared Beer']);
        $inventory = Inventory::create([
            'user_id' => $this->userA->id, 'beer_id' => $beer->id, 'store_id' => $store->id, 'quantity' => 1,
        ]);

        Livewire::actingAs($this->userB)
            ->test(LocationShow::class, ['location' => $store])
            ->call('delete')
            ->assertForbidden();

        $this->assertDatabaseHas('stores', ['id' => $store->id]);
        $this->assertDatabaseHas('inventory', ['id' => $inventory->id, 'store_id' => $store->id]);
    }

    public function test_tampering_location_type_does_not_bypass_check(): void
    {
        $venue = Venue::create(['name' => 'Shared Venue']);
        $beer = Beer::create(['name' => 'Shared Beer']);
        Checkin::create(['user_id' => $this->userA->id, 'beer_id' => $beer->id, 'venue_id' => $venue->id]);

        try {
            Livewire::actingAs($this->userB)
                ->test(LocationShow::class, ['location' => $venue])
                ->set('type', 'store')
                ->call('delete');
            $this->fail('The location type should not be writable from the client.');
        } catch (CannotUpdateLockedPropertyException) {
            // expected
        }

        Livewire::actingAs($this->userB)
            ->test(LocationShow::class, ['location' => $venue])
            ->call('delete')
            ->assertForbidden();

        $this->assertDatabaseHas('venues', ['id' => $venue->id]);
    }

    public function test_user_can_delete_location_only_they_use(): void
    {
        $store = Store::create(['name' => 'My Store']);
        $beer = Beer::create(['name' => 'My Beer']);
        Inventory::create(['user_id' => $this->userB->id, 'beer_id' => $beer->id, 'store_id' => $store->id, 'quantity' => 1]);

        Livewire::actingAs($this->userB)
            ->test(LocationShow::class, ['location' => $store])
            ->call('edit')
            ->assertSeeHtml('wire:click="delete"')
            ->call('delete')
            ->assertRedirect(route('locations.stores'));

        $this->assertDatabaseMissing('stores', ['id' => $store->id]);
    }

    public function test_admin_can_delete_shared_locations(): void
    {
        $brewery = Brewery::create(['name' => 'Shared Brewery']);
        $store = Store::create(['name' => 'Shared Store']);
        $beer = Beer::create(['name' => 'Shared Beer', 'brewery_id' => $brewery->id]);
        Inventory::create(['user_id' => $this->userA->id, 'beer_id' => $beer->id, 'store_id' => $store->id, 'quantity' => 1]);

        Livewire::actingAs($this->admin)
            ->test(LocationShow::class, ['location' => $brewery])
            ->call('delete')
            ->assertRedirect(route('locations.breweries'));

        Livewire::actingAs($this->admin)
            ->test(LocationShow::class, ['location' => $store])
            ->call('delete')
            ->assertRedirect(route('locations.stores'));

        $this->assertDatabaseMissing('breweries', ['id' => $brewery->id]);
        $this->assertDatabaseMissing('stores', ['id' => $store->id]);
    }
}
