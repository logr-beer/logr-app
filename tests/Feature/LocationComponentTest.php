<?php

namespace Tests\Feature;

use App\Livewire\CheckinForm;
use App\Livewire\LocationIndex;
use App\Livewire\LocationShow;
use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\Store;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class LocationComponentTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------
    // WithLocationAutocomplete trait (tested via CheckinForm)
    // -------------------------------------------------------

    public function test_select_location_sets_venue_and_clears_query(): void
    {
        $user = User::factory()->create();
        $venue = Venue::create(['name' => 'The Pub']);

        Livewire::actingAs($user)
            ->test(CheckinForm::class)
            ->set('venueQuery', 'The')
            ->call('selectLocation', 'venue', $venue->id, Venue::class)
            ->assertSet('selectedVenueId', $venue->id)
            ->assertSet('selectedVenueName', 'The Pub')
            ->assertSet('venueQuery', '');
    }

    public function test_clear_location_clears_venue_fields(): void
    {
        $user = User::factory()->create();
        $venue = Venue::create(['name' => 'The Pub']);

        Livewire::actingAs($user)
            ->test(CheckinForm::class)
            ->set('selectedVenueId', $venue->id)
            ->set('selectedVenueName', 'The Pub')
            ->set('venueQuery', 'leftover')
            ->call('clearLocation', 'venue')
            ->assertSet('selectedVenueId', null)
            ->assertSet('selectedVenueName', '')
            ->assertSet('venueQuery', '');
    }

    public function test_resolve_location_id_returns_existing_selected_id(): void
    {
        $user = User::factory()->create();
        $brewery = Brewery::create(['name' => 'Test Brewery']);
        $beer = Beer::create(['name' => 'Test IPA', 'brewery_id' => $brewery->id]);
        $venue = Venue::create(['name' => 'The Pub']);

        Livewire::actingAs($user)
            ->test(CheckinForm::class)
            ->set('selectedBeerId', $beer->id)
            ->set('selectedVenueId', $venue->id)
            ->set('selectedVenueName', 'The Pub')
            ->call('submitCheckin');

        $this->assertDatabaseHas('checkins', [
            'user_id' => $user->id,
            'beer_id' => $beer->id,
            'venue_id' => $venue->id,
        ]);
    }

    public function test_resolve_location_id_creates_new_venue_from_query(): void
    {
        $user = User::factory()->create();
        $brewery = Brewery::create(['name' => 'Test Brewery']);
        $beer = Beer::create(['name' => 'Test IPA', 'brewery_id' => $brewery->id]);

        Livewire::actingAs($user)
            ->test(CheckinForm::class)
            ->set('selectedBeerId', $beer->id)
            ->set('venueQuery', 'Brand New Bar')
            ->call('submitCheckin');

        $this->assertDatabaseHas('venues', ['name' => 'Brand New Bar']);

        $venue = Venue::where('name', 'Brand New Bar')->first();
        $this->assertDatabaseHas('checkins', [
            'user_id' => $user->id,
            'beer_id' => $beer->id,
            'venue_id' => $venue->id,
        ]);
    }

    public function test_resolve_location_id_returns_null_when_no_selection_and_no_query(): void
    {
        $user = User::factory()->create();
        $brewery = Brewery::create(['name' => 'Test Brewery']);
        $beer = Beer::create(['name' => 'Test IPA', 'brewery_id' => $brewery->id]);

        Livewire::actingAs($user)
            ->test(CheckinForm::class)
            ->set('selectedBeerId', $beer->id)
            ->call('submitCheckin');

        $this->assertDatabaseHas('checkins', [
            'user_id' => $user->id,
            'beer_id' => $beer->id,
            'venue_id' => null,
        ]);
    }

    public function test_import_and_select_location_creates_from_cached_nominatim_data(): void
    {
        $user = User::factory()->create();
        $cacheKey = 'test_nominatim_key';

        Cache::put("location_nominatim_{$cacheKey}", [
            'name' => 'Imported Bar',
            'address' => '123 Main St',
            'city' => 'Portland',
            'state' => 'Oregon',
            'country' => 'United States',
            'lat' => '45.5152',
            'lon' => '-122.6784',
        ], now()->addMinutes(10));

        Livewire::actingAs($user)
            ->test(CheckinForm::class)
            ->call('importAndSelectLocation', 'venue', $cacheKey, Venue::class)
            ->assertSet('selectedVenueName', 'Imported Bar')
            ->assertSet('venueQuery', '');

        $this->assertDatabaseHas('venues', [
            'name' => 'Imported Bar',
            'address' => '123 Main St',
            'city' => 'Portland',
            'state' => 'Oregon',
            'country' => 'United States',
        ]);
    }

    // -------------------------------------------------------
    // LocationShow component
    // -------------------------------------------------------

    public function test_mount_with_venue_sets_type_to_venue(): void
    {
        $user = User::factory()->create();
        $venue = Venue::create(['name' => 'Test Venue']);

        Livewire::actingAs($user)
            ->test(LocationShow::class, ['location' => $venue])
            ->assertSet('type', 'venue');
    }

    public function test_mount_with_brewery_sets_type_to_brewery(): void
    {
        $user = User::factory()->create();
        $brewery = Brewery::create(['name' => 'Test Brewery']);

        Livewire::actingAs($user)
            ->test(LocationShow::class, ['location' => $brewery])
            ->assertSet('type', 'brewery');
    }

    public function test_mount_with_store_sets_type_to_store(): void
    {
        $user = User::factory()->create();
        $store = Store::create(['name' => 'Test Store']);

        Livewire::actingAs($user)
            ->test(LocationShow::class, ['location' => $store])
            ->assertSet('type', 'store');
    }

    public function test_edit_mode_fills_form_with_location_data(): void
    {
        $user = User::factory()->create();
        $venue = Venue::create([
            'name' => 'The Pub',
            'address' => '123 Main St',
            'city' => 'Portland',
            'state' => 'Oregon',
            'country' => 'United States',
            'website' => 'https://thepub.example.com',
        ]);

        Livewire::actingAs($user)
            ->test(LocationShow::class, ['location' => $venue])
            ->call('edit')
            ->assertSet('editing', true)
            ->assertSet('name', 'The Pub')
            ->assertSet('address', '123 Main St')
            ->assertSet('city', 'Portland')
            ->assertSet('state', 'Oregon')
            ->assertSet('country', 'United States')
            ->assertSet('website', 'https://thepub.example.com');
    }

    public function test_save_updates_location(): void
    {
        $user = User::factory()->create();
        $venue = Venue::create(['name' => 'Old Name']);

        Livewire::actingAs($user)
            ->test(LocationShow::class, ['location' => $venue])
            ->call('edit')
            ->set('name', 'New Name')
            ->set('city', 'Seattle')
            ->set('state', 'Washington')
            ->set('country', 'United States')
            ->set('address', '456 Oak Ave')
            ->set('website', 'https://newname.example.com')
            ->call('save')
            ->assertSet('editing', false);

        $this->assertDatabaseHas('venues', [
            'id' => $venue->id,
            'name' => 'New Name',
            'city' => 'Seattle',
            'state' => 'Washington',
            'country' => 'United States',
            'address' => '456 Oak Ave',
            'website' => 'https://newname.example.com',
        ]);
    }

    public function test_cancel_resets_form_to_original_values(): void
    {
        $user = User::factory()->create();
        $venue = Venue::create([
            'name' => 'Original Name',
            'city' => 'Portland',
        ]);

        Livewire::actingAs($user)
            ->test(LocationShow::class, ['location' => $venue])
            ->call('edit')
            ->set('name', 'Changed Name')
            ->set('city', 'Seattle')
            ->call('cancel')
            ->assertSet('editing', false)
            ->assertSet('name', 'Original Name')
            ->assertSet('city', 'Portland');
    }

    public function test_delete_removes_location_and_redirects(): void
    {
        $user = User::factory()->create();
        $venue = Venue::create(['name' => 'Doomed Venue']);

        Livewire::actingAs($user)
            ->test(LocationShow::class, ['location' => $venue])
            ->call('delete')
            ->assertRedirect(route('locations.venues'));

        $this->assertDatabaseMissing('venues', ['id' => $venue->id]);
    }

    public function test_delete_venue_blocked_when_other_users_have_checkins(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $brewery = Brewery::create(['name' => 'Test Brewery']);
        $beer = Beer::create(['name' => 'Test IPA', 'brewery_id' => $brewery->id]);
        $venue = Venue::create(['name' => 'Shared Venue']);

        Checkin::create([
            'user_id' => $otherUser->id,
            'beer_id' => $beer->id,
            'venue_id' => $venue->id,
        ]);

        Livewire::actingAs($user)
            ->test(LocationShow::class, ['location' => $venue])
            ->call('delete')
            ->assertStatus(403);
    }

    public function test_delete_blocked_in_demo_mode(): void
    {
        $user = User::factory()->create();
        $venue = Venue::create(['name' => 'Demo Venue']);

        config(['app.demo_mode' => true]);

        Livewire::actingAs($user)
            ->test(LocationShow::class, ['location' => $venue])
            ->call('delete');

        $this->assertDatabaseHas('venues', ['id' => $venue->id]);
    }

    // -------------------------------------------------------
    // LocationIndex component
    // -------------------------------------------------------

    public function test_create_new_creates_location_and_redirects(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(LocationIndex::class, ['type' => 'venue'])
            ->call('createNew')
            ->assertRedirect();

        $this->assertDatabaseHas('venues', ['name' => 'New Venue']);
    }
}
