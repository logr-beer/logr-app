<?php

namespace Tests\Feature;

use App\Livewire\BeerForm;
use App\Livewire\BeerShow;
use App\Livewire\CheckinForm;
use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\Inventory;
use App\Models\Setting;
use App\Models\User;
use App\Services\Discord;
use App\Services\PubDiscord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DiscordShareTargetsTest extends TestCase
{
    use RefreshDatabase;

    private const HOOK_1 = 'https://discord.test/webhook/1';

    private const HOOK_2 = 'https://discord.test/webhook/2';

    protected function setUp(): void
    {
        parent::setUp();
        Setting::clearCache();

        Http::fake([
            'discord.test/*' => Http::response('', 204),
            'hub.test/*' => Http::response(['ok' => true], 200),
        ]);
    }

    /**
     * Two webhooks and two bots; only the first of each is enabled for the given publish key.
     */
    private function createUser(string $publishKey): User
    {
        $user = User::factory()->create();
        $user->setData('discord_webhooks', [
            ['url' => self::HOOK_1, 'label' => 'One', $publishKey => true],
            ['url' => self::HOOK_2, 'label' => 'Two', $publishKey => false],
        ]);
        $user->setData('discord_bot_prefs', [
            '111' => [$publishKey => true],
            '222' => [$publishKey => false],
        ]);
        $user->save();

        Setting::set('discord_bots', [
            ['hub_url' => 'https://hub.test', 'hub_api_key' => 'key1', 'guild_id' => '111', 'guild_name' => 'A'],
            ['hub_url' => 'https://hub.test', 'hub_api_key' => 'key2', 'guild_id' => '222', 'guild_name' => 'B'],
        ]);

        return $user;
    }

    private function createBeer(): Beer
    {
        $brewery = Brewery::create(['name' => 'Test Brewery']);

        return Beer::create(['name' => 'Test IPA', 'brewery_id' => $brewery->id]);
    }

    /** @return array<int, string> */
    private function sentWebhooks(): array
    {
        return Http::recorded()
            ->map(fn ($pair) => $pair[0]->url())
            ->filter(fn ($url) => str_starts_with($url, 'https://discord.test/'))
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function sentGuilds(): array
    {
        return Http::recorded()
            ->map(fn ($pair) => $pair[0])
            ->filter(fn (Request $r) => str_starts_with($r->url(), 'https://hub.test/'))
            ->map(fn (Request $r) => (string) $r->data()['guild_id'])
            ->values()
            ->all();
    }

    private function assertNothingSent(): void
    {
        $this->assertSame([], $this->sentWebhooks());
        $this->assertSame([], $this->sentGuilds());
    }

    // ── Check-ins ──

    public function test_checkin_form_posts_only_to_enabled_targets(): void
    {
        $user = $this->createUser('publish_checkins');
        $beer = $this->createBeer();

        Livewire::actingAs($user)
            ->test(CheckinForm::class)
            ->set('selectedBeerId', $beer->id)
            ->call('submitCheckin');

        $this->assertSame([self::HOOK_1], $this->sentWebhooks());
        $this->assertSame(['111'], $this->sentGuilds());
    }

    public function test_checkin_form_toggle_off_sends_nothing(): void
    {
        $user = $this->createUser('publish_checkins');
        $beer = $this->createBeer();

        Livewire::actingAs($user)
            ->test(CheckinForm::class)
            ->set('selectedBeerId', $beer->id)
            ->set('shareTargets.0.enabled', false)
            ->set('shareTargets.2.enabled', false)
            ->call('submitCheckin');

        $this->assertNothingSent();
    }

    public function test_checkin_form_toggle_on_sends_to_selected_target_only(): void
    {
        $user = $this->createUser('publish_checkins');
        $beer = $this->createBeer();

        Livewire::actingAs($user)
            ->test(CheckinForm::class)
            ->set('selectedBeerId', $beer->id)
            ->set('shareTargets.0.enabled', false)
            ->set('shareTargets.1.enabled', true)
            ->set('shareTargets.2.enabled', false)
            ->set('shareTargets.3.enabled', true)
            ->call('submitCheckin');

        $this->assertSame([self::HOOK_2], $this->sentWebhooks());
        $this->assertSame(['222'], $this->sentGuilds());
    }

    public function test_beer_show_checkin_posts_only_to_enabled_targets(): void
    {
        $user = $this->createUser('publish_checkins');
        $beer = $this->createBeer();

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer])
            ->call('submitCheckin');

        $this->assertSame([self::HOOK_1], $this->sentWebhooks());
        $this->assertSame(['111'], $this->sentGuilds());
    }

    public function test_beer_show_checkin_toggle_off_sends_nothing(): void
    {
        $user = $this->createUser('publish_checkins');
        $beer = $this->createBeer();

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer])
            ->set('checkinShareTargets.0.enabled', false)
            ->set('checkinShareTargets.2.enabled', false)
            ->call('submitCheckin');

        $this->assertNothingSent();
    }

    public function test_beer_form_checkin_posts_only_to_enabled_targets(): void
    {
        $user = $this->createUser('publish_checkins');

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('name', 'New IPA')
            ->set('addCheckin', true)
            ->call('save');

        $this->assertSame([self::HOOK_1], $this->sentWebhooks());
        $this->assertSame(['111'], $this->sentGuilds());
    }

    public function test_service_falls_back_to_stored_checkin_flags(): void
    {
        $user = $this->createUser('publish_checkins');
        $checkin = Checkin::create(['user_id' => $user->id, 'beer_id' => $this->createBeer()->id]);

        Discord::sendCheckin($checkin, $user);
        PubDiscord::sendCheckin($checkin, $user);

        $this->assertSame([self::HOOK_1], $this->sentWebhooks());
        $this->assertSame(['111'], $this->sentGuilds());
    }

    // ── Purchases ──

    public function test_beer_show_purchase_posts_only_to_enabled_targets(): void
    {
        $user = $this->createUser('publish_purchases');
        $beer = $this->createBeer();

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer])
            ->call('addToFridge');

        $this->assertSame([self::HOOK_1], $this->sentWebhooks());
        $this->assertSame(['111'], $this->sentGuilds());
    }

    public function test_beer_show_purchase_toggle_off_sends_nothing(): void
    {
        $user = $this->createUser('publish_purchases');
        $beer = $this->createBeer();

        Livewire::actingAs($user)
            ->test(BeerShow::class, ['beer' => $beer])
            ->set('inventoryShareTargets.0.enabled', false)
            ->set('inventoryShareTargets.2.enabled', false)
            ->call('addToFridge');

        $this->assertNothingSent();
    }

    public function test_beer_form_purchase_posts_only_to_enabled_targets(): void
    {
        $user = $this->createUser('publish_purchases');

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('name', 'New IPA')
            ->set('addToInventory', true)
            ->call('save');

        $this->assertSame([self::HOOK_1], $this->sentWebhooks());
        $this->assertSame(['111'], $this->sentGuilds());
    }

    public function test_beer_form_purchase_toggle_off_sends_nothing(): void
    {
        $user = $this->createUser('publish_purchases');

        Livewire::actingAs($user)
            ->test(BeerForm::class)
            ->set('name', 'New IPA')
            ->set('addToInventory', true)
            ->set('inventoryShareTargets.0.enabled', false)
            ->set('inventoryShareTargets.2.enabled', false)
            ->call('save');

        $this->assertNothingSent();
    }

    public function test_service_falls_back_to_stored_purchase_flags(): void
    {
        $user = $this->createUser('publish_purchases');
        $inventory = Inventory::create([
            'beer_id' => $this->createBeer()->id,
            'user_id' => $user->id,
            'storage_location' => 'Fridge',
            'quantity' => 1,
        ]);

        Discord::sendPurchase($inventory, $user);
        PubDiscord::sendPurchase($inventory, $user);

        $this->assertSame([self::HOOK_1], $this->sentWebhooks());
        $this->assertSame(['111'], $this->sentGuilds());
    }
}
