<?php

namespace Tests\Feature;

use App\Livewire\BeerForm;
use App\Livewire\BeerIndex;
use App\Livewire\BeerShow;
use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BeerCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_beer(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('name', 'Test IPA')
            ->set('style', ['IPA'])
            ->set('abv', 6.5)
            ->call('save');

        $this->assertDatabaseHas('beers', [
            'name' => 'Test IPA',
            'abv' => 6.5,
        ]);
    }

    public function test_create_beer_with_brewery(): void
    {
        $user = User::factory()->create();

        $brewery = Brewery::create([
            'name' => 'Test Brewing Co',
            'city' => 'Portland',
            'state' => 'OR',
        ]);

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('name', 'Brewery IPA')
            ->set('style', ['IPA'])
            ->set('abv', 7.0)
            ->set('brewery_id', $brewery->id)
            ->call('save');

        $this->assertDatabaseHas('beers', [
            'name' => 'Brewery IPA',
            'brewery_id' => $brewery->id,
        ]);
    }

    public function test_update_beer(): void
    {
        $user = User::factory()->create();

        $beer = Beer::create([
            'name' => 'Original Name',
            'style' => ['Stout'],
            'abv' => 5.0,
        ]);

        Livewire::actingAs($user)
            ->test(BeerForm::class, ['beer' => $beer])
            ->set('name', 'Updated Name')
            ->call('save');

        $this->assertDatabaseHas('beers', [
            'id' => $beer->id,
            'name' => 'Updated Name',
        ]);

        $this->assertDatabaseMissing('beers', [
            'id' => $beer->id,
            'name' => 'Original Name',
        ]);
    }

    public function test_delete_beer(): void
    {
        $user = User::factory()->create();

        $beer = Beer::create([
            'name' => 'Delete Me',
            'abv' => 4.0,
        ]);

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer])
            ->call('deleteBeer');

        $this->assertDatabaseMissing('beers', [
            'id' => $beer->id,
        ]);
    }

    public function test_delete_beer_blocked_in_demo_mode(): void
    {
        $user = User::factory()->create();

        $beer = Beer::create([
            'name' => 'Protected Beer',
            'abv' => 5.0,
        ]);

        config(['app.demo_mode' => true]);

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer])
            ->call('deleteBeer');

        $this->assertDatabaseHas('beers', [
            'id' => $beer->id,
            'name' => 'Protected Beer',
        ]);
    }

    public function test_toggle_favorite(): void
    {
        $user = User::factory()->create();

        $beer = Beer::create([
            'name' => 'Favorite Beer',
            'abv' => 6.0,
            'is_favorite' => false,
        ]);

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer])
            ->call('toggleFavorite');

        $this->assertTrue($beer->fresh()->is_favorite);

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer->fresh()])
            ->call('toggleFavorite');

        $this->assertFalse($beer->fresh()->is_favorite);
    }

    public function test_bulk_delete_scoped_by_other_user_checkins(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $beer = Beer::create([
            'name' => 'Shared Beer',
            'abv' => 5.5,
        ]);

        // User1 has a checkin on this beer
        Checkin::create([
            'user_id' => $user1->id,
            'beer_id' => $beer->id,
            'rating' => 4.0,
        ]);

        // User2 tries to bulk delete it
        Livewire::actingAs($user2)
            ->test(BeerIndex::class)
            ->set('selected', [$beer->id])
            ->call('deleteSelected');

        // Beer should still exist because user1 has checkins on it
        $this->assertDatabaseHas('beers', [
            'id' => $beer->id,
        ]);
    }

    public function test_beer_search_shows_dropdown(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('beerSearch', 'IP')
            ->assertSet('showBeerDropdown', true);
    }

    public function test_create_beer_with_inline_checkin(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('name', 'Inline Checkin Beer')
            ->set('addCheckin', true)
            ->set('checkinRating', 4.0)
            ->set('checkinServingType', 'can')
            ->set('checkinVenue', 'Home')
            ->set('checkinNotes', 'Great first try')
            ->call('save');

        $this->assertDatabaseHas('beers', ['name' => 'Inline Checkin Beer']);

        $beer = Beer::where('name', 'Inline Checkin Beer')->first();
        $this->assertDatabaseHas('checkins', [
            'beer_id' => $beer->id,
            'user_id' => $user->id,
            'rating' => 4.0,
            'serving_type' => 'can',
            'notes' => 'Great first try',
        ]);
        $this->assertDatabaseHas('venues', ['name' => 'Home']);
    }
}
