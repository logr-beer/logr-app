<?php

namespace Tests\Feature;

use App\Livewire\Admin\SystemInfo;
use App\Livewire\Setup;
use App\Livewire\VenueShow;
use App\Models\Beer;
use App\Models\Checkin;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VenueAuthTest extends TestCase
{
    use RefreshDatabase;

    // ── Venue CRUD ──────────────────────────────────────────────────

    public function test_update_venue(): void
    {
        $user = User::factory()->create();

        $venue = Venue::create([
            'name' => 'Original Pub',
            'city' => 'Portland',
        ]);

        Livewire::actingAs($user)
            ->test(VenueShow::class, ['venue' => $venue])
            ->call('edit')
            ->set('name', 'Updated Pub')
            ->set('city', 'Seattle')
            ->call('save');

        $this->assertDatabaseHas('venues', [
            'id' => $venue->id,
            'name' => 'Updated Pub',
            'city' => 'Seattle',
        ]);
    }

    public function test_delete_venue(): void
    {
        $user = User::factory()->create();

        $venue = Venue::create(['name' => 'Delete Me']);

        $beer = Beer::create(['name' => 'Test Beer', 'abv' => 5.0]);

        Checkin::create([
            'user_id' => $user->id,
            'beer_id' => $beer->id,
            'venue_id' => $venue->id,
            'rating' => 4.0,
        ]);

        Livewire::actingAs($user)
            ->test(VenueShow::class, ['venue' => $venue])
            ->call('delete');

        $this->assertDatabaseMissing('venues', [
            'id' => $venue->id,
        ]);
    }

    public function test_delete_venue_blocked_when_other_users_have_checkins(): void
    {
        $user1 = User::factory()->create(); // admin
        $user2 = User::factory()->create(); // non-admin

        $venue = Venue::create(['name' => 'Shared Venue']);

        $beer = Beer::create(['name' => 'Test Beer', 'abv' => 5.0]);

        Checkin::create([
            'user_id' => $user1->id,
            'beer_id' => $beer->id,
            'venue_id' => $venue->id,
            'rating' => 4.0,
        ]);

        Livewire::actingAs($user2)
            ->test(VenueShow::class, ['venue' => $venue])
            ->call('delete')
            ->assertStatus(403);

        $this->assertDatabaseHas('venues', [
            'id' => $venue->id,
        ]);
    }

    public function test_delete_venue_blocked_in_demo_mode(): void
    {
        $user = User::factory()->create();

        $venue = Venue::create(['name' => 'Protected Venue']);

        config(['app.demo_mode' => true]);

        Livewire::actingAs($user)
            ->test(VenueShow::class, ['venue' => $venue])
            ->call('delete');

        $this->assertDatabaseHas('venues', [
            'id' => $venue->id,
        ]);
    }

    // ── Authorization ───────────────────────────────────────────────

    public function test_admin_can_access_system_info(): void
    {
        $admin = User::factory()->create(); // first user = admin

        $response = $this->actingAs($admin)->get('/admin/system');

        $response->assertStatus(200);
    }

    public function test_non_admin_blocked_from_system_info(): void
    {
        User::factory()->create(); // first user = admin
        $user = User::factory()->create(); // second = non-admin

        $response = $this->actingAs($user)->get('/admin/system');

        $response->assertStatus(403);
    }

    public function test_setup_redirects_when_users_exist(): void
    {
        User::factory()->create();

        $response = $this->get('/setup');

        $response->assertRedirect(route('login'));
    }

    public function test_setup_accessible_when_no_users(): void
    {
        $response = $this->get('/setup');

        $response->assertStatus(200);
    }
}
