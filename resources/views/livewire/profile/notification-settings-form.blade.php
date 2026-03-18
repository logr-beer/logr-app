<?php

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

    // Discord Bots (via Logr hub)
    public array $discordBots = [];
    public array $botChannels = [];
    public ?int $loadingChannelsFor = null;

    // Test results
    public string $testResult = '';
    public string $testStatus = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->discordWebhooks = $user->getData('discord_webhooks') ?? [];
        $this->discordBots = $user->getData('discord_bots') ?? [];
    }

    // -- Webhooks --

    public function addWebhook(): void
    {
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
        unset($this->discordWebhooks[$index]);
        $this->discordWebhooks = array_values($this->discordWebhooks);

        $user = Auth::user();
        $user->setData('discord_webhooks', $this->discordWebhooks ?: null);
        $user->save();
    }

    public function toggleWebhookSetting(int $index, string $setting): void
    {
        if (isset($this->discordWebhooks[$index])) {
            $this->discordWebhooks[$index][$setting] = !($this->discordWebhooks[$index][$setting] ?? false);

            $user = Auth::user();
            $user->setData('discord_webhooks', $this->discordWebhooks);
            $user->save();
        }
    }

    public function testDiscord(int $index): void
    {
        $webhook = $this->discordWebhooks[$index] ?? null;
        if (!$webhook || empty($webhook['url'])) {
            $this->testResult = 'No webhook URL configured.';
            $this->testStatus = 'error';
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::post($webhook['url'], [
                'embeds' => [[
                    'title' => 'Logr Connected!',
                    'description' => 'Your Discord webhook is working.' . ($webhook['label'] ? " ({$webhook['label']})" : ''),
                    'color' => 0xF59E0B,
                    'footer' => ['text' => 'Logr'],
                    'timestamp' => now()->toIso8601String(),
                ]],
            ]);

            if ($response->successful()) {
                $this->testResult = 'Test message sent! Check your Discord channel.';
                $this->testStatus = 'success';
            } else {
                $this->testResult = "HTTP {$response->status()}: {$response->body()}";
                $this->testStatus = 'error';
            }
        } catch (\Exception $e) {
            $this->testResult = $e->getMessage();
            $this->testStatus = 'error';
        }
    }

    // -- Bots (Logr Hub) --

    public function loadChannels(int $index): void
    {
        $bot = $this->discordBots[$index] ?? null;
        if (!$bot || empty($bot['hub_url']) || empty($bot['hub_api_key']) || empty($bot['guild_id'])) {
            return;
        }

        $this->loadingChannelsFor = $index;

        $channels = \App\Services\Hub::fetchChannels($bot['hub_url'], $bot['hub_api_key'], $bot['guild_id']);
        $this->botChannels = $channels ?? [];
    }

    public function changeBotChannel(int $index, string $channelId): void
    {
        $bot = $this->discordBots[$index] ?? null;
        if (!$bot || empty($bot['hub_url']) || empty($bot['hub_api_key']) || empty($bot['guild_id'])) {
            return;
        }

        $success = \App\Services\Hub::updateChannel($bot['hub_url'], $bot['hub_api_key'], $bot['guild_id'], $channelId);

        if ($success) {
            $channel = collect($this->botChannels)->firstWhere('id', $channelId);
            $this->discordBots[$index]['channel_name'] = $channel['name'] ?? null;

            $user = Auth::user();
            $user->setData('discord_bots', $this->discordBots);
            $user->save();

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

    public function removeBot(int $index): void
    {
        unset($this->discordBots[$index]);
        $this->discordBots = array_values($this->discordBots);

        $user = Auth::user();
        $user->setData('discord_bots', $this->discordBots ?: null);
        $user->save();
    }

    public function toggleBotSetting(int $index, string $setting): void
    {
        if (isset($this->discordBots[$index])) {
            $this->discordBots[$index][$setting] = !($this->discordBots[$index][$setting] ?? false);

            $user = Auth::user();
            $user->setData('discord_bots', $this->discordBots);
            $user->save();
        }
    }

    public function testBot(int $index): void
    {
        $bot = $this->discordBots[$index] ?? null;
        if (!$bot || empty($bot['hub_url']) || empty($bot['hub_api_key']) || empty($bot['guild_id'])) {
            $this->testResult = 'Bot configuration incomplete.';
            $this->testStatus = 'error';
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($bot['hub_api_key'])
                ->accept('application/json')
                ->timeout(15)
                ->post(rtrim($bot['hub_url'], '/') . '/api/post', [
                    'guild_id' => $bot['guild_id'],
                    'type' => 'checkin',
                    'payload' => [
                        'beer_name' => 'Test Beer',
                        'brewery' => 'Logr Brewing',
                        'rating' => 5.0,
                        'serving' => 'Can',
                        'notes' => 'This is a test notification from Logr!',
                        'user' => Auth::user()->name,
                        'username' => 'Logr',
                    ],
                ]);

            if ($response->successful()) {
                $this->testResult = 'Test message sent! Check your Discord channel.';
                $this->testStatus = 'success';
            } else {
                $this->testResult = "HTTP {$response->status()}: {$response->body()}";
                $this->testStatus = 'error';
            }
        } catch (\Exception $e) {
            $this->testResult = $e->getMessage();
            $this->testStatus = 'error';
        }
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Notifications</h2>
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
        @include('livewire.profile.partials.discord-bots')
        @include('livewire.profile.partials.discord-webhooks')
    </div>
</section>
