<?php

namespace Tests\Feature;

use App\Livewire\Setup;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class SettingsSecretsTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_URL = 'https://discord.com/api/webhooks/123456/webhook-token-abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        Setting::clearCache();
        Http::preventStrayRequests();
        config(['services.logr.pub_url' => '']);
    }

    private function seedBots(): void
    {
        Setting::set('discord_bots', [[
            'hub_url' => 'https://hub.test',
            'hub_api_key' => 'hub-secret-key-9876',
            'guild_id' => '111',
            'guild_name' => 'Server A',
            'channel_name' => 'general',
        ]]);
    }

    private function adminWithWebhook(): User
    {
        $admin = User::factory()->create();
        $admin->setData('discord_webhooks', [[
            'label' => 'Beer log',
            'url' => self::WEBHOOK_URL,
            'channel_id' => null,
            'guild_id' => null,
            'publish_checkins' => false,
            'publish_purchases' => false,
        ]]);
        $admin->save();

        return $admin;
    }

    public function test_notification_settings_page_does_not_expose_bot_key_or_webhook_token(): void
    {
        $this->seedBots();
        $admin = $this->adminWithWebhook();

        $response = $this->actingAs($admin)->get(route('admin.notifications'));

        $response->assertOk();
        $response->assertSee('Server A');
        $response->assertSee('Beer log');
        $response->assertDontSee('hub-secret-key-9876');
        $response->assertDontSee('https://hub.test');
        $response->assertDontSee('webhook-token-abcdef');
    }

    public function test_toggle_webhook_setting_ignores_tampered_url(): void
    {
        $admin = $this->adminWithWebhook();
        $this->actingAs($admin);

        Volt::test('profile.notification-settings-form')
            ->set('discordWebhooks.0.url', 'https://attacker.test/hook')
            ->call('toggleWebhookSetting', 0, 'publish_checkins');

        $webhooks = $admin->fresh()->getData('discord_webhooks');
        $this->assertSame(self::WEBHOOK_URL, $webhooks[0]['url']);
        $this->assertTrue($webhooks[0]['publish_checkins']);
    }

    public function test_toggle_webhook_setting_rejects_unknown_setting(): void
    {
        $admin = $this->adminWithWebhook();
        $this->actingAs($admin);

        Volt::test('profile.notification-settings-form')
            ->call('toggleWebhookSetting', 0, 'url');

        $this->assertSame(self::WEBHOOK_URL, $admin->fresh()->getData('discord_webhooks')[0]['url']);
    }

    public function test_change_bot_channel_keeps_hub_url_and_key(): void
    {
        $this->seedBots();
        $admin = User::factory()->create();
        $this->actingAs($admin);

        Http::fake([
            'hub.test/api/discord/guilds/111/channels' => Http::response(['channels' => [
                ['id' => '42', 'name' => 'beer'],
            ]]),
            'hub.test/api/discord/guilds/111/channel' => Http::response(['ok' => true]),
        ]);

        Volt::test('profile.notification-settings-form')
            ->call('loadChannels', 0)
            ->set('discordBots.0.hub_url', 'https://attacker.test')
            ->set('discordBots.0.hub_api_key', 'tampered')
            ->call('changeBotChannel', 0, '42');

        $bots = Setting::get('discord_bots');
        $this->assertSame('https://hub.test', $bots[0]['hub_url']);
        $this->assertSame('hub-secret-key-9876', $bots[0]['hub_api_key']);
        $this->assertSame('beer', $bots[0]['channel_name']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'attacker.test'));
    }

    public function test_disconnect_bot_removes_only_that_bot_from_stored_settings(): void
    {
        Setting::set('discord_bots', [
            ['hub_url' => 'https://hub.test', 'hub_api_key' => 'key1', 'guild_id' => '111', 'guild_name' => 'A'],
            ['hub_url' => 'https://hub.test', 'hub_api_key' => 'key2', 'guild_id' => '222', 'guild_name' => 'B'],
        ]);
        $admin = User::factory()->create();
        $this->actingAs($admin);

        Volt::test('profile.notification-settings-form')
            ->set('discordBots.1.hub_api_key', 'tampered')
            ->call('disconnectBot', 0);

        $bots = Setting::get('discord_bots');
        $this->assertCount(1, $bots);
        $this->assertSame('222', $bots[0]['guild_id']);
        $this->assertSame('key2', $bots[0]['hub_api_key']);
    }

    private function adminWithApiSecrets(): User
    {
        $admin = User::factory()->create();
        $admin->setData('pub_secret_key', 'pub-secret-value-1111');
        $admin->setData('untappd_client_id', 'untappd-client-id');
        $admin->setData('untappd_client_secret', 'untappd-secret-value-2222');
        $admin->setData('catalog_beer_api_key', 'catalog-key-value-3333');
        $admin->setData('untappd_rss_feeds', [['label' => 'Main', 'url' => 'https://untappd.com/rss/user/main']]);
        $admin->save();

        return $admin;
    }

    public function test_api_settings_page_does_not_expose_secrets(): void
    {
        $admin = $this->adminWithApiSecrets();
        Setting::set('pub_api_key', 'instance-key');

        $response = $this->actingAs($admin)->get(route('admin.api'));

        $response->assertOk();
        $response->assertDontSee('pub-secret-value-1111');
        $response->assertDontSee('untappd-secret-value-2222');
        $response->assertDontSee('catalog-key-value-3333');
        $response->assertSee('••••1111');
        $response->assertSee('••••2222');
        $response->assertSee('••••3333');
    }

    public function test_saving_api_settings_with_blank_secrets_keeps_stored_values(): void
    {
        $admin = $this->adminWithApiSecrets();
        $this->actingAs($admin);

        Volt::test('profile.api-settings-form')
            ->set('untappd_username', 'beerfan')
            ->call('save');

        $admin->refresh();
        $this->assertSame('beerfan', $admin->getData('untappd_username'));
        $this->assertSame('pub-secret-value-1111', $admin->getData('pub_secret_key'));
        $this->assertSame('untappd-secret-value-2222', $admin->getData('untappd_client_secret'));
        $this->assertSame('catalog-key-value-3333', $admin->getData('catalog_beer_api_key'));
    }

    public function test_saving_api_settings_with_new_secret_overwrites_it(): void
    {
        $admin = $this->adminWithApiSecrets();
        $this->actingAs($admin);

        Volt::test('profile.api-settings-form')
            ->set('catalog_beer_api_key', 'new-catalog-key-4444')
            ->call('save')
            ->assertSet('catalog_beer_api_key', '');

        $this->assertSame('new-catalog-key-4444', $admin->fresh()->getData('catalog_beer_api_key'));
    }

    public function test_clear_secret_removes_stored_value(): void
    {
        $admin = $this->adminWithApiSecrets();
        $this->actingAs($admin);

        Volt::test('profile.api-settings-form')
            ->call('clearSecret', 'catalog_beer_api_key')
            ->call('clearSecret', 'untappd_username');

        $admin->refresh();
        $this->assertNull($admin->getData('catalog_beer_api_key'));
        $this->assertSame('untappd-secret-value-2222', $admin->getData('untappd_client_secret'));
    }

    public function test_toggle_feed_submit_ignores_tampered_feed_url(): void
    {
        $admin = $this->adminWithApiSecrets();
        $this->actingAs($admin);

        Volt::test('profile.api-settings-form')
            ->set('rssFeeds.0.url', 'https://attacker.test/feed')
            ->call('toggleFeedSubmit', 0);

        $feeds = $admin->fresh()->getData('untappd_rss_feeds');
        $this->assertSame('https://untappd.com/rss/user/main', $feeds[0]['url']);
        $this->assertTrue($feeds[0]['submit_to_logrdb']);
    }

    public function test_remove_feed_ignores_tampered_feed_list(): void
    {
        $admin = $this->adminWithApiSecrets();
        $this->actingAs($admin);

        Volt::test('profile.api-settings-form')
            ->set('rssFeeds.1', ['label' => 'Injected', 'url' => 'https://attacker.test/feed'])
            ->call('removeFeed', 0);

        $this->assertNull($admin->fresh()->getData('untappd_rss_feeds'));
    }

    public function test_setup_does_not_expose_env_secrets_but_still_applies_them(): void
    {
        config([
            'services.catalog_beer.key' => 'env-catalog-key-5555',
            'services.untappd.api_key' => 'env-untappd-id-6666',
            'services.untappd.api_secret' => 'env-untappd-secret-7777',
        ]);

        $this->get(route('setup'))
            ->assertOk()
            ->assertSee('Set via environment')
            ->assertDontSee('env-catalog-key-5555')
            ->assertDontSee('env-untappd-id-6666')
            ->assertDontSee('env-untappd-secret-7777');

        Livewire::test(Setup::class)
            ->set('untappd_api_secret', 'tampered')
            ->set('username', 'admin')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('connectPub', false)
            ->call('createAccount');

        $user = User::first();
        $this->assertSame('env-catalog-key-5555', $user->getData('catalog_beer_api_key'));
        $this->assertSame('env-untappd-id-6666', $user->getData('untappd_client_id'));
        $this->assertSame('env-untappd-secret-7777', $user->getData('untappd_client_secret'));
    }
}
