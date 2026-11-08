<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\Setup;
use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\Collection;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_load_demo_data_seeds_into_current_user_without_creating_demo_account(): void
    {
        config(['app.demo_mode' => false]);
        $user = User::factory()->create(['username' => 'owner']);

        Livewire::actingAs($user)->test(Dashboard::class)->call('loadDemoData');

        $this->assertDatabaseMissing('users', ['username' => 'demo']);
        $this->assertSame(1, User::count());
        $this->assertGreaterThan(0, Checkin::count());
        $this->assertSame(0, Checkin::where('user_id', '!=', $user->id)->count());
        $this->assertGreaterThan(0, Collection::where('user_id', $user->id)->count());
    }

    public function test_load_demo_data_does_nothing_when_library_has_beers(): void
    {
        $user = User::factory()->create();
        Beer::create(['name' => 'Existing', 'brewery_id' => Brewery::create(['name' => 'Existing Brewing'])->id]);

        Livewire::actingAs($user)->test(Dashboard::class)->call('loadDemoData');

        $this->assertSame(0, Checkin::count());
    }

    public function test_demo_seeder_creates_demo_account_without_copying_api_keys(): void
    {
        config(['services.catalog_beer.key' => 'test-catalog-key']);

        $this->seed(DemoSeeder::class);

        $demo = User::where('username', 'demo')->firstOrFail();
        $this->assertNull($demo->getData('catalog_beer_api_key'));
        $this->assertGreaterThan(0, Checkin::where('user_id', $demo->id)->count());
    }

    public function test_demo_seeder_refuses_to_create_demo_account_in_production(): void
    {
        config(['app.demo_mode' => false]);
        $this->app['env'] = 'production';

        (new DemoSeeder)->run();

        $this->assertDatabaseMissing('users', ['username' => 'demo']);
    }

    public function test_setup_loads_demo_data_into_new_account(): void
    {
        Livewire::test(Setup::class)
            ->set('username', 'owner')
            ->set('password', 'secret-pass')
            ->set('password_confirmation', 'secret-pass')
            ->set('connectPub', false)
            ->set('loadDemoData', true)
            ->call('createAccount');

        $owner = User::where('username', 'owner')->firstOrFail();
        $this->assertDatabaseMissing('users', ['username' => 'demo']);
        $this->assertGreaterThan(0, Checkin::where('user_id', $owner->id)->count());
        $this->assertSame(0, Checkin::where('user_id', '!=', $owner->id)->count());
    }

    public function test_purge_with_demo_seeds_into_admin_account(): void
    {
        $admin = User::factory()->create();

        $this->artisan('logr:purge', ['--demo' => true, '--force' => true])->assertSuccessful();

        $this->assertDatabaseMissing('users', ['username' => 'demo']);
        $this->assertGreaterThan(0, Checkin::where('user_id', $admin->id)->count());
    }
}
