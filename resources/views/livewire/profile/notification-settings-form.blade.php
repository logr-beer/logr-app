<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    // Discord Webhooks
    public array $discordWebhooks = [];
    public string $newWebhookLabel = '';
    public string $newWebhookUrl = '';
    public bool $newWebhookCheckins = true;
    public bool $newWebhookPurchases = true;

    // Discord Bots (global config, multiple servers)
    public array $discordBots = [];
    public array $botPrefs = [];
    public array $botChannels = [];
    public ?int $loadingChannelsFor = null;

    // Discord identity
    public ?string $discordUsername = null;

    // Test results
    public string $testResult = '';
    public string $testStatus = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->discordBots = Setting::get('discord_bots', []);
        $this->botPrefs = $user->getData('discord_bot_prefs') ?? [];

        if (config('app.demo_mode')) {
            $this->discordWebhooks = [];
            $this->discordUsername = null;
        } else {
            $this->discordWebhooks = $user->getData('discord_webhooks') ?? [];
            $this->discordUsername = $user->getData('discord_username');
        }
    }

    // -- Webhooks --

    public function addWebhook(): void
    {
        if (config('app.demo_mode')) {
            return;
        }

        $this->validate([
            'newWebhookUrl' => ['required', 'url', 'max:500', new \App\Rules\DiscordWebhookUrl],
            'newWebhookLabel' => 'nullable|string|max:100',
        ]);

        $exists = collect($this->discordWebhooks)->contains('url', $this->newWebhookUrl);
        if ($exists) {
            $this->addError('newWebhookUrl', 'This webhook URL has already been added.');
            return;
        }

        $this->discordWebhooks[] = [
            'label' => $this->newWebhookLabel ?: null,
            'url' => $this->newWebhookUrl,
            'publish_checkins' => $this->newWebhookCheckins,
            'publish_purchases' => $this->newWebhookPurchases,
        ];

        $user = Auth::user();
        $user->setData('discord_webhooks', $this->discordWebhooks);
        $user->save();

        $this->newWebhookLabel = '';
        $this->newWebhookUrl = '';
        $this->newWebhookCheckins = true;
        $this->newWebhookPurchases = true;
    }

    public function removeWebhook(int $index): void
    {
        if (config('app.demo_mode')) {
            return;
        }

        unset($this->discordWebhooks[$index]);
        $this->discordWebhooks = array_values($this->discordWebhooks);

        $user = Auth::user();
        $user->setData('discord_webhooks', $this->discordWebhooks ?: null);
        $user->save();
    }

    public function toggleWebhookSetting(int $index, string $setting): void
    {
        if (config('app.demo_mode')) {
            return;
        }

        if (isset($this->discordWebhooks[$index])) {
            $this->discordWebhooks[$index][$setting] = !($this->discordWebhooks[$index][$setting] ?? false);

            $user = Auth::user();
            $user->setData('discord_webhooks', $this->discordWebhooks);
            $user->save();
        }
    }

    public function testCheckin(int $index): void
    {
        if (config('app.demo_mode')) {
            return;
        }

        $webhook = $this->discordWebhooks[$index] ?? null;
        if (!$webhook || empty($webhook['url'])) {
            $this->testResult = 'No webhook URL configured.';
            $this->testStatus = 'error';
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)->post($webhook['url'], [
                'username' => 'Logr Bot',
                'avatar_url' => url('/img/logr-discord.png'),
                'embeds' => [[
                    'title' => 'Check-in: Pliny the Elder',
                    'description' => "**Pliny the Elder** by Russian River Brewing Company\n\n> Perfectly balanced, one of the best DIPAs out there.",
                    'color' => 0xF59E0B,
                    'fields' => [
                        ['name' => 'Style', 'value' => 'Double IPA, IPA, Pale Ale', 'inline' => true],
                        ['name' => 'ABV', 'value' => '8.0%', 'inline' => true],
                        ['name' => 'IBU', 'value' => '100', 'inline' => true],
                        ['name' => 'Rating', 'value' => '**5** / 5 ⭐⭐⭐⭐⭐', 'inline' => true],
                        ['name' => 'Serving', 'value' => 'Draft', 'inline' => true],
                        ['name' => 'Venue', 'value' => 'The Local Taproom', 'inline' => true],
                    ],
                    'footer' => ['text' => 'Logr'],
                    'timestamp' => now()->toIso8601String(),
                ]],
            ]);

            $this->testResult = $response->successful()
                ? 'Test check-in sent! Check your Discord channel.'
                : "HTTP {$response->status()}: {$response->body()}";
            $this->testStatus = $response->successful() ? 'success' : 'error';
        } catch (\Exception $e) {
            $this->testResult = $e->getMessage();
            $this->testStatus = 'error';
        }
    }

    public function testInventory(int $index): void
    {
        if (config('app.demo_mode')) {
            return;
        }

        $webhook = $this->discordWebhooks[$index] ?? null;
        if (!$webhook || empty($webhook['url'])) {
            $this->testResult = 'No webhook URL configured.';
            $this->testStatus = 'error';
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)->post($webhook['url'], [
                'username' => 'Logr Bot',
                'avatar_url' => url('/img/logr-discord.png'),
                'embeds' => [[
                    'title' => 'Added to Inventory: Two Hearted Ale',
                    'description' => "**Two Hearted Ale** by Bell's Brewery",
                    'color' => 0x3B82F6,
                    'fields' => [
                        ['name' => 'Style', 'value' => 'American IPA', 'inline' => true],
                        ['name' => 'ABV', 'value' => '7.0%', 'inline' => true],
                        ['name' => 'IBU', 'value' => '55', 'inline' => true],
                        ['name' => 'Quantity', 'value' => '6', 'inline' => true],
                        ['name' => 'Storage', 'value' => 'Fridge', 'inline' => true],
                        ['name' => 'From', 'value' => 'Total Wine', 'inline' => true],
                    ],
                    'footer' => ['text' => 'Logr'],
                    'timestamp' => now()->toIso8601String(),
                ]],
            ]);

            $this->testResult = $response->successful()
                ? 'Test inventory notification sent! Check your Discord channel.'
                : "HTTP {$response->status()}: {$response->body()}";
            $this->testStatus = $response->successful() ? 'success' : 'error';
        } catch (\Exception $e) {
            $this->testResult = $e->getMessage();
            $this->testStatus = 'error';
        }
    }

    // -- Bots (Logr Bot) --

    public function toggleBotPref(string $guildId, string $setting): void
    {
        if (config('app.demo_mode')) {
            return;
        }

        $this->botPrefs[$guildId][$setting] = ! ($this->botPrefs[$guildId][$setting] ?? false);

        $user = Auth::user();
        $user->setData('discord_bot_prefs', $this->botPrefs);
        $user->save();
    }

    public function loadChannels(int $index): void
    {
        $bot = $this->discordBots[$index] ?? null;
        if (config('app.demo_mode') || ! Auth::user()->is_admin || ! $bot) {
            return;
        }

        $this->loadingChannelsFor = $index;

        $channels = \App\Services\Hub::fetchChannels($bot['hub_url'], $bot['hub_api_key'], $bot['guild_id']);
        $this->botChannels = $channels ?? [];
    }

    public function changeBotChannel(int $index, string $channelId): void
    {
        $bot = $this->discordBots[$index] ?? null;
        if (config('app.demo_mode') || ! Auth::user()->is_admin || ! $bot) {
            return;
        }

        $success = \App\Services\Hub::updateChannel($bot['hub_url'], $bot['hub_api_key'], $bot['guild_id'], $channelId);

        if ($success) {
            $channel = collect($this->botChannels)->firstWhere('id', $channelId);
            $this->discordBots[$index]['channel_name'] = $channel['name'] ?? null;
            Setting::set('discord_bots', $this->discordBots);

            $this->testResult = 'Channel updated to #' . ($channel['name'] ?? 'unknown');
            $this->testStatus = 'success';
        } else {
            $this->testResult = 'Failed to update channel.';
            $this->testStatus = 'error';
        }

        $this->loadingChannelsFor = null;
        $this->botChannels = [];
    }

    public function cancelChannelPicker(): void
    {
        $this->loadingChannelsFor = null;
        $this->botChannels = [];
    }

    public function disconnectBot(int $index): void
    {
        if (config('app.demo_mode') || ! Auth::user()->is_admin) {
            return;
        }

        unset($this->discordBots[$index]);
        $this->discordBots = array_values($this->discordBots);
        Setting::set('discord_bots', $this->discordBots ?: []);
    }

    public function testBotCheckin(int $index): void
    {
        if (config('app.demo_mode')) {
            return;
        }

        $bot = $this->discordBots[$index] ?? null;
        if (! $bot) {
            $this->testResult = 'Bot not configured.';
            $this->testStatus = 'error';
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($bot['hub_api_key'])
                ->accept('application/json')
                ->timeout(15)
                ->post(rtrim($bot['hub_url'], '/') . '/api/discord/post', [
                    'guild_id' => $bot['guild_id'],
                    'type' => 'checkin',
                    'payload' => [
                        'beer_name' => 'Pliny the Elder',
                        'brewery' => 'Russian River Brewing Company',
                        'style' => 'Double IPA, IPA, Pale Ale',
                        'abv' => '8.0%',
                        'ibu' => '100',
                        'rating' => 5.0,
                        'serving' => 'Draft',
                        'venue' => 'The Local Taproom',
                        'notes' => 'Perfectly balanced, one of the best DIPAs out there.',
                        'user' => Auth::user()->name,
                    ],
                ]);

            $this->testResult = $response->successful()
                ? 'Test check-in sent! Check your Discord channel.'
                : "HTTP {$response->status()}: {$response->body()}";
            $this->testStatus = $response->successful() ? 'success' : 'error';
        } catch (\Exception $e) {
            $this->testResult = $e->getMessage();
            $this->testStatus = 'error';
        }
    }

    public function testBotInventory(int $index): void
    {
        if (config('app.demo_mode')) {
            return;
        }

        $bot = $this->discordBots[$index] ?? null;
        if (! $bot) {
            $this->testResult = 'Bot not configured.';
            $this->testStatus = 'error';
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($bot['hub_api_key'])
                ->accept('application/json')
                ->timeout(15)
                ->post(rtrim($bot['hub_url'], '/') . '/api/discord/post', [
                    'guild_id' => $bot['guild_id'],
                    'type' => 'purchase',
                    'payload' => [
                        'beer_name' => 'Two Hearted Ale',
                        'brewery' => "Bell's Brewery",
                        'style' => 'American IPA',
                        'abv' => '7.0%',
                        'quantity' => 6,
                        'storage_location' => 'Fridge',
                        'purchase_location' => 'Total Wine',
                        'user' => Auth::user()->name,
                    ],
                ]);

            $this->testResult = $response->successful()
                ? 'Test inventory notification sent! Check your Discord channel.'
                : "HTTP {$response->status()}: {$response->body()}";
            $this->testStatus = $response->successful() ? 'success' : 'error';
        } catch (\Exception $e) {
            $this->testResult = $e->getMessage();
            $this->testStatus = 'error';
        }
    }
}; ?>

<section>
    <header>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Notifications</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Configure where check-ins and inventory additions are published.</p>
    </header>

    @if($testResult)
        <div class="mt-4 p-3 rounded-lg text-sm {{ $testStatus === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' }}">
            {{ $testResult }}
        </div>
    @endif

    @if(session('message'))
        <div class="mt-4 p-3 rounded-lg text-sm bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mt-4 p-3 rounded-lg text-sm bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="mt-6 space-y-6">
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                <x-icon name="discord" size="4" :solid="true" class="inline-block mr-1 text-amber-400" />
                Discord
            </h3>
            @include('livewire.profile.partials.discord-bots')
            @include('livewire.profile.partials.discord-webhooks')
        </div>
    </div>
</section>
