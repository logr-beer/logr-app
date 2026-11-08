<?php

namespace Tests\Feature;

use App\Livewire\BeerForm;
use App\Livewire\BeerIndex;
use App\Livewire\BeerShow;
use App\Livewire\Rankings;
use App\Models\Beer;
use App\Models\Checkin;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InputValidationTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────
    //  BeerForm
    // ──────────────────────────────────────────────

    public function test_beer_form_rejects_negative_inventory_quantity(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('name', 'Bad Qty Beer')
            ->set('addToInventory', true)
            ->set('addQuantity', -5)
            ->call('save')
            ->assertHasErrors(['addQuantity' => 'min']);

        $this->assertDatabaseMissing('beers', ['name' => 'Bad Qty Beer']);
        $this->assertSame(0, Inventory::count());
    }

    public function test_beer_form_rejects_bad_purchase_date(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('name', 'Bad Date Beer')
            ->set('addToInventory', true)
            ->set('purchaseDate', 'not-a-date')
            ->call('save')
            ->assertHasErrors(['purchaseDate']);

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('name', 'Future Date Beer')
            ->set('addToInventory', true)
            ->set('purchaseDate', now()->addDays(3)->toDateString())
            ->call('save')
            ->assertHasErrors(['purchaseDate' => 'before_or_equal']);

        $this->assertSame(0, Inventory::count());
    }

    public function test_beer_form_rejects_bad_checkin_fields(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('name', 'Bad Checkin Beer')
            ->set('addCheckin', true)
            ->set('checkinRating', 9)
            ->set('checkinServingType', 'bucket')
            ->set('checkinNotes', str_repeat('a', 2001))
            ->call('save')
            ->assertHasErrors(['checkinRating' => 'max', 'checkinServingType' => 'in', 'checkinNotes' => 'max']);

        $this->assertSame(0, Checkin::count());
    }

    public function test_beer_form_ignores_inventory_fields_when_not_adding_to_inventory(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('name', 'Plain Beer')
            ->set('addQuantity', 0)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('beers', ['name' => 'Plain Beer']);
    }

    public function test_beer_form_valid_inventory_and_checkin_still_saved(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('name', 'Good Beer')
            ->set('addToInventory', true)
            ->set('storageLocation', 'Cellar')
            ->set('addQuantity', 3)
            ->set('purchaseDate', now()->toDateString())
            ->set('addCheckin', true)
            ->set('checkinRating', 4.25)
            ->set('checkinServingType', 'can')
            ->set('checkinNotes', 'Nice')
            ->call('save')
            ->assertHasNoErrors();

        $beer = Beer::where('name', 'Good Beer')->firstOrFail();
        $this->assertDatabaseHas('inventory', ['beer_id' => $beer->id, 'storage_location' => 'Cellar', 'quantity' => 3]);
        $this->assertDatabaseHas('checkins', ['beer_id' => $beer->id, 'serving_type' => 'can', 'notes' => 'Nice']);
    }

    // ──────────────────────────────────────────────
    //  BeerShow::addToFridge
    // ──────────────────────────────────────────────

    public function test_add_to_fridge_rejects_non_positive_quantity(): void
    {
        $user = User::factory()->create();
        $beer = Beer::create(['name' => 'Fridge Beer']);

        foreach ([0, -3, 1000] as $quantity) {
            Livewire::actingAs($user)
                ->test(BeerShow::class, ['beer' => $beer])
                ->set('addQuantity', $quantity)
                ->call('addToFridge')
                ->assertHasErrors(['addQuantity']);
        }

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer])
            ->set('addQuantity', 0)
            ->call('addToFridge')
            ->assertSee('The add quantity field must be at least 1.');

        $this->assertSame(0, Inventory::count());
    }

    public function test_add_to_fridge_rejects_bad_date_and_long_location(): void
    {
        $user = User::factory()->create();
        $beer = Beer::create(['name' => 'Fridge Beer']);

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer])
            ->set('purchaseDate', '2024-13-45')
            ->call('addToFridge')
            ->assertHasErrors(['purchaseDate']);

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer])
            ->set('storageLocation', str_repeat('x', 101))
            ->call('addToFridge')
            ->assertHasErrors(['storageLocation' => 'max']);

        $this->assertSame(0, Inventory::count());
    }

    public function test_add_to_fridge_valid_input_still_works(): void
    {
        $user = User::factory()->create();
        $beer = Beer::create(['name' => 'Fridge Beer']);

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer])
            ->set('addQuantity', 4)
            ->set('purchaseDate', '2025-01-15')
            ->call('addToFridge')
            ->assertHasNoErrors()
            ->assertDispatched('fridge-added');

        $this->assertDatabaseHas('inventory', ['beer_id' => $beer->id, 'storage_location' => 'Fridge', 'quantity' => 4]);
    }

    // ──────────────────────────────────────────────
    //  BeerIndex::addSelectedToInventory
    // ──────────────────────────────────────────────

    public function test_bulk_add_to_inventory_rejects_bad_quantity_and_location(): void
    {
        $user = User::factory()->create();
        $beer = Beer::create(['name' => 'Bulk Beer']);

        Livewire::actingAs($user)
            ->test(BeerIndex::class)
            ->set('selected', [$beer->id])
            ->set('inventoryQuantity', -1)
            ->set('inventoryLocation', str_repeat('x', 101))
            ->call('addSelectedToInventory')
            ->assertHasErrors(['inventoryQuantity' => 'min', 'inventoryLocation' => 'max']);

        $this->assertSame(0, Inventory::count());
    }

    public function test_bulk_add_to_inventory_valid_input_still_works(): void
    {
        $user = User::factory()->create();
        $beer = Beer::create(['name' => 'Bulk Beer']);

        Livewire::actingAs($user)
            ->test(BeerIndex::class)
            ->set('selected', [$beer->id])
            ->set('inventoryQuantity', 2)
            ->set('inventoryLocation', 'Cellar')
            ->call('addSelectedToInventory')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inventory', ['beer_id' => $beer->id, 'storage_location' => 'Cellar', 'quantity' => 2]);
    }

    // ──────────────────────────────────────────────
    //  Rankings::expandSection
    // ──────────────────────────────────────────────

    public function test_expand_section_ignores_unknown_section(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Rankings::class)
            ->call('expandSection', 'topRated', 10)
            ->assertSet('topRatedLimit', 10)
            ->call('expandSection', 'paginators', 10)
            ->assertSet('topRatedLimit', 10)
            ->assertSet('mostCheckedInLimit', 5)
            ->assertSet('topBreweriesLimit', 5)
            ->assertSet('highestAbvLimit', 5);
    }

    public function test_expand_section_clamps_limit(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Rankings::class)
            ->call('expandSection', 'highestAbv', 1000000)
            ->assertSet('highestAbvLimit', 100)
            ->call('expandSection', 'mostCheckedIn', -5)
            ->assertSet('mostCheckedInLimit', 1);
    }
}
