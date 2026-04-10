<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscordBotTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_user_is_admin(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->assertTrue($first->is_admin);
        $this->assertFalse($second->fresh()->is_admin);
    }

    public function test_setting_model_crud(): void
    {
        Setting::set('test_key', ['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], Setting::get('test_key'));

        Setting::set('test_key', ['foo' => 'updated']);
        $this->assertEquals(['foo' => 'updated'], Setting::get('test_key'));

        Setting::remove('test_key');
        $this->assertNull(Setting::get('test_key'));
        $this->assertEquals('default', Setting::get('test_key', 'default'));
    }

    public function test_admin_can_connect_discord(): void
    {
        config(['services.logr.discord_url' => 'https://discord.test']);

        $admin = User::factory()->create();

        $this->actingAs($admin);
        session(['logr_state' => 'test-state']);

        $response = $this->get('/logr/callback?'.http_build_query([
            'api_key' => 'test-api-key',
            'guild_id' => '12345',
            'guild_name' => 'Test Server',
            'channel_name' => 'general',
            'state' => 'test-state',
        ]));

        $response->assertRedirect(route('admin.notifications'));
        $response->assertSessionHas('message', 'Connected to Test Server!');

        $bots = Setting::get('discord_bots', []);
        $this->assertCount(1, $bots);
        $this->assertEquals('12345', $bots[0]['guild_id']);
        $this->assertEquals('Test Server', $bots[0]['guild_name']);

        $prefs = $admin->fresh()->getData('discord_bot_prefs');
        $this->assertTrue($prefs['12345']['publish_checkins']);
        $this->assertTrue($prefs['12345']['publish_purchases']);
    }

    public function test_non_admin_cannot_connect_discord(): void
    {
        config(['services.logr.discord_url' => 'https://discord.test']);

        User::factory()->create(); // first user is admin
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/logr/connect');

        $response->assertRedirect(route('admin.notifications'));
        $response->assertSessionHas('error');
    }

    public function test_duplicate_guild_not_added(): void
    {
        config(['services.logr.discord_url' => 'https://discord.test']);

        $admin = User::factory()->create();

        Setting::set('discord_bots', [[
            'hub_url' => 'https://discord.test',
            'hub_api_key' => 'existing-key',
            'guild_id' => '12345',
            'guild_name' => 'Test Server',
            'channel_name' => 'general',
        ]]);

        $this->actingAs($admin);
        session(['logr_state' => 'test-state']);

        $this->get('/logr/callback?'.http_build_query([
            'api_key' => 'new-key',
            'guild_id' => '12345',
            'guild_name' => 'Test Server',
            'channel_name' => 'general',
            'state' => 'test-state',
        ]));

        $bots = Setting::get('discord_bots', []);
        $this->assertCount(1, $bots);
        $this->assertEquals('existing-key', $bots[0]['hub_api_key']);
    }

    public function test_multiple_servers_can_be_connected(): void
    {
        config(['services.logr.discord_url' => 'https://discord.test']);

        $admin = User::factory()->create();
        $this->actingAs($admin);

        session(['logr_state' => 'state1']);
        $this->get('/logr/callback?'.http_build_query([
            'api_key' => 'key1',
            'guild_id' => '111',
            'guild_name' => 'Server A',
            'channel_name' => 'general',
            'state' => 'state1',
        ]));

        session(['logr_state' => 'state2']);
        $this->get('/logr/callback?'.http_build_query([
            'api_key' => 'key2',
            'guild_id' => '222',
            'guild_name' => 'Server B',
            'channel_name' => 'beer',
            'state' => 'state2',
        ]));

        $bots = Setting::get('discord_bots', []);
        $this->assertCount(2, $bots);
        $this->assertEquals('Server A', $bots[0]['guild_name']);
        $this->assertEquals('Server B', $bots[1]['guild_name']);

        $prefs = $admin->fresh()->getData('discord_bot_prefs');
        $this->assertArrayHasKey('111', $prefs);
        $this->assertArrayHasKey('222', $prefs);
    }

    public function test_hub_bots_for_user_filters_by_prefs(): void
    {
        Setting::set('discord_bots', [
            ['hub_url' => 'https://hub.test', 'hub_api_key' => 'key1', 'guild_id' => '111', 'guild_name' => 'A'],
            ['hub_url' => 'https://hub.test', 'hub_api_key' => 'key2', 'guild_id' => '222', 'guild_name' => 'B'],
        ]);

        $user = User::factory()->create();
        $user->setData('discord_bot_prefs', [
            '111' => ['publish_checkins' => true, 'publish_purchases' => false],
            '222' => ['publish_checkins' => false, 'publish_purchases' => true],
        ]);
        $user->save();

        $this->assertTrue(\App\Services\Hub::hasPublishing($user, 'publish_checkins'));
        $this->assertTrue(\App\Services\Hub::hasPublishing($user, 'publish_purchases'));
    }

    public function test_hub_no_publishing_without_prefs(): void
    {
        Setting::set('discord_bots', [
            ['hub_url' => 'https://hub.test', 'hub_api_key' => 'key1', 'guild_id' => '111', 'guild_name' => 'A'],
        ]);

        $user = User::factory()->create();

        $this->assertFalse(\App\Services\Hub::hasPublishing($user, 'publish_checkins'));
        $this->assertFalse(\App\Services\Hub::hasPublishing($user, 'publish_purchases'));
    }

    public function test_hub_no_publishing_without_bots(): void
    {
        $user = User::factory()->create();
        $user->setData('discord_bot_prefs', [
            '111' => ['publish_checkins' => true, 'publish_purchases' => true],
        ]);
        $user->save();

        $this->assertFalse(\App\Services\Hub::hasPublishing($user, 'publish_checkins'));
    }

    public function test_data_migration_converts_single_bot_to_array(): void
    {
        $admin = User::factory()->create();
        $admin->setData('discord_publish_checkins', true);
        $admin->setData('discord_publish_purchases', false);
        $admin->save();

        Setting::set('discord_bot', [
            'hub_url' => 'https://hub.test',
            'hub_api_key' => 'key1',
            'guild_id' => '999',
            'guild_name' => 'Old Server',
            'channel_name' => 'general',
        ]);

        // Simulate the migration logic
        $single = Setting::where('key', 'discord_bot')->first();
        if ($single) {
            $bot = $single->value;
            if ($bot && ! empty($bot['guild_id'])) {
                Setting::set('discord_bots', [$bot]);
            }
            $single->delete();
        }

        foreach (User::all() as $user) {
            $publishCheckins = $user->getData('discord_publish_checkins');
            $publishPurchases = $user->getData('discord_publish_purchases');

            if ($publishCheckins === null && $publishPurchases === null) {
                continue;
            }

            $bots = Setting::get('discord_bots', []);
            $prefs = $user->getData('discord_bot_prefs') ?? [];

            foreach ($bots as $b) {
                $guildId = $b['guild_id'];
                if (! isset($prefs[$guildId])) {
                    $prefs[$guildId] = [
                        'publish_checkins' => (bool) $publishCheckins,
                        'publish_purchases' => (bool) $publishPurchases,
                    ];
                }
            }

            $user->setData('discord_bot_prefs', $prefs);
            $user->setData('discord_publish_checkins', null);
            $user->setData('discord_publish_purchases', null);
            $user->save();
        }

        // Verify
        $this->assertNull(Setting::get('discord_bot'));
        $bots = Setting::get('discord_bots', []);
        $this->assertCount(1, $bots);
        $this->assertEquals('999', $bots[0]['guild_id']);

        $admin->refresh();
        $prefs = $admin->getData('discord_bot_prefs');
        $this->assertTrue($prefs['999']['publish_checkins']);
        $this->assertFalse($prefs['999']['publish_purchases']);
        $this->assertNull($admin->getData('discord_publish_checkins'));
        $this->assertNull($admin->getData('discord_publish_purchases'));
    }
}
