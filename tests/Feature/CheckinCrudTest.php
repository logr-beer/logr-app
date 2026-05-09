<?php

namespace Tests\Feature;

use App\Livewire\BeerShow;
use App\Livewire\CheckinForm;
use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckinCrudTest extends TestCase
{
    use RefreshDatabase;

    private function createUserAndBeer(): array
    {
        $user = User::factory()->create();
        $brewery = Brewery::create(['name' => 'Test Brewery']);
        $beer = Beer::create(['name' => 'Test IPA', 'brewery_id' => $brewery->id]);

        return [$user, $beer];
    }

    public function test_create_checkin(): void
    {
        [$user, $beer] = $this->createUserAndBeer();

        Livewire::actingAs($user)
            ->test(CheckinForm::class)
            ->set('selectedBeerId', $beer->id)
            ->set('rating', 4.5)
            ->set('serving_type', 'can')
            ->set('notes', 'Great beer')
            ->call('submitCheckin');

        $this->assertDatabaseHas('checkins', [
            'user_id' => $user->id,
            'beer_id' => $beer->id,
            'rating' => 4.5,
            'serving_type' => 'can',
            'notes' => 'Great beer',
        ]);
    }

    public function test_create_checkin_with_venue(): void
    {
        [$user, $beer] = $this->createUserAndBeer();

        Livewire::actingAs($user)
            ->test(CheckinForm::class)
            ->set('selectedBeerId', $beer->id)
            ->set('rating', 4.0)
            ->set('venueQuery', 'Test Bar')
            ->call('submitCheckin');

        $this->assertDatabaseHas('venues', ['name' => 'Test Bar']);

        $venue = Venue::where('name', 'Test Bar')->first();
        $this->assertDatabaseHas('checkins', [
            'user_id' => $user->id,
            'beer_id' => $beer->id,
            'venue_id' => $venue->id,
        ]);
    }

    public function test_update_checkin(): void
    {
        [$user, $beer] = $this->createUserAndBeer();

        $checkin = Checkin::create([
            'user_id' => $user->id,
            'beer_id' => $beer->id,
            'rating' => 3.0,
            'serving_type' => 'draft',
        ]);

        Livewire::actingAs($user)
            ->test(CheckinForm::class, ['checkin' => $checkin->id])
            ->set('rating', 4.5)
            ->call('submitCheckin');

        $this->assertDatabaseHas('checkins', [
            'id' => $checkin->id,
            'rating' => 4.5,
        ]);
    }

    public function test_delete_checkin(): void
    {
        [$user, $beer] = $this->createUserAndBeer();

        $checkin = Checkin::create([
            'user_id' => $user->id,
            'beer_id' => $beer->id,
            'rating' => 3.0,
        ]);

        Livewire::actingAs($user)
            ->test(CheckinForm::class, ['checkin' => $checkin->id])
            ->call('deleteCheckin');

        $this->assertDatabaseMissing('checkins', ['id' => $checkin->id]);
    }

    public function test_delete_checkin_blocked_in_demo_mode(): void
    {
        [$user, $beer] = $this->createUserAndBeer();

        $checkin = Checkin::create([
            'user_id' => $user->id,
            'beer_id' => $beer->id,
            'rating' => 3.0,
        ]);

        config(['app.demo_mode' => true]);

        Livewire::actingAs($user)
            ->test(CheckinForm::class, ['checkin' => $checkin->id])
            ->call('deleteCheckin');

        $this->assertDatabaseHas('checkins', ['id' => $checkin->id]);
    }

    public function test_checkin_from_beer_show_page(): void
    {
        [$user, $beer] = $this->createUserAndBeer();

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer])
            ->set('rating', 4.0)
            ->set('serving_type', 'draft')
            ->call('submitCheckin');

        $this->assertDatabaseHas('checkins', [
            'user_id' => $user->id,
            'beer_id' => $beer->id,
            'rating' => 4.0,
            'serving_type' => 'draft',
        ]);
    }
}
