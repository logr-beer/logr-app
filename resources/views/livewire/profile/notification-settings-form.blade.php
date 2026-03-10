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
            'newWebhookUrl' => 'required|url|max:500',
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
        {{-- Discord Bot (Logr) --}}
        @if(config('services.logr.hub_url'))
            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg space-y-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">
                    <svg class="w-4 h-4 inline-block mr-1 text-indigo-400" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
                    Discord Bot (Logr)
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Connect your Discord server to post check-ins and inventory updates via the Logr bot.</p>

                @foreach($discordBots as $index => $bot)
                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg space-y-2">
                        <div class="flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $bot['label'] ?? $bot['guild_name'] ?? 'Unknown Server' }}</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ $bot['guild_name'] ?? $bot['guild_id'] }}
                                    @if(!empty($bot['channel_name']))
                                        &middot; #{{ $bot['channel_name'] }}
                                    @endif
                                </p>
                            </div>
                            <button type="button" wire:click="loadChannels({{ $index }})" class="shrink-0 inline-flex items-center gap-1 px-2 py-1 bg-gray-600 text-white text-xs font-medium rounded hover:bg-gray-700 transition-colors" title="Change channel">
                                <span wire:loading wire:target="loadChannels({{ $index }})"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5-3.9 19.5m-2.1-19.5-3.9 19.5"/></svg>
                            </button>
                            <button type="button" wire:click="testBot({{ $index }})" class="shrink-0 inline-flex items-center gap-1 px-2 py-1 bg-gray-600 text-white text-xs font-medium rounded hover:bg-gray-700 transition-colors">
                                <span wire:loading wire:target="testBot({{ $index }})"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                                Test
                            </button>
                            <button type="button" wire:click="removeBot({{ $index }})" wire:confirm="Disconnect this server?" class="shrink-0 p-1.5 text-gray-400 hover:text-red-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            </button>
                        </div>

                        @if($loadingChannelsFor === $index && !empty($botChannels))
                            <div class="flex items-center gap-2">
                                <select wire:change="changeBotChannel({{ $index }}, $event.target.value)"
                                    class="flex-1 text-xs border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md">
                                    <option value="">Select channel...</option>
                                    @foreach($botChannels as $channel)
                                        <option value="{{ $channel['id'] }}">
                                            #{{ $channel['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" wire:click="cancelChannelPicker" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">Cancel</button>
                            </div>
                        @endif

                        <div class="flex items-center gap-4 text-xs">
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" wire:click="toggleBotSetting({{ $index }}, 'publish_checkins')" {{ !empty($bot['publish_checkins']) ? 'checked' : '' }}
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-500 focus:ring-indigo-500 dark:bg-gray-700" />
                                <span class="text-gray-700 dark:text-gray-300">Check-ins</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" wire:click="toggleBotSetting({{ $index }}, 'publish_purchases')" {{ !empty($bot['publish_purchases']) ? 'checked' : '' }}
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-500 focus:ring-indigo-500 dark:bg-gray-700" />
                                <span class="text-gray-700 dark:text-gray-300">Inventory additions</span>
                            </label>
                        </div>
                    </div>
                @endforeach

                <div class="flex items-center gap-3">
                    <a href="{{ route('logr.connect') }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#5865F2] text-white text-sm font-medium rounded-lg hover:bg-[#4752C4] transition-colors">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
                        Connect with Discord
                    </a>
                    <a href="{{ rtrim(config('services.logr.hub_url'), '/') }}" target="_blank"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Add Bot to Server
                    </a>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">A server admin must add the bot first, then any member can connect.</p>
            </div>
        @endif

        {{-- Discord Webhooks (direct) --}}
        <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">
                <svg class="w-4 h-4 inline-block mr-1 text-indigo-400" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
                Discord Webhooks
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Direct webhooks: Server Settings &rarr; Integrations &rarr; Webhooks &rarr; New Webhook.</p>

            @foreach($discordWebhooks as $index => $webhook)
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $webhook['label'] ?? 'Untitled Webhook' }}</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $webhook['url'] }}</p>
                        </div>
                        <button type="button" wire:click="testDiscord({{ $index }})" class="shrink-0 inline-flex items-center gap-1 px-2 py-1 bg-gray-600 text-white text-xs font-medium rounded hover:bg-gray-700 transition-colors">
                            <span wire:loading wire:target="testDiscord({{ $index }})"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                            Test
                        </button>
                        <button type="button" wire:click="removeWebhook({{ $index }})" wire:confirm="Remove this webhook?" class="shrink-0 p-1.5 text-gray-400 hover:text-red-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-4 text-xs">
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" wire:click="toggleWebhookSetting({{ $index }}, 'publish_checkins')" {{ !empty($webhook['publish_checkins']) ? 'checked' : '' }}
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-500 focus:ring-indigo-500 dark:bg-gray-700" />
                            <span class="text-gray-700 dark:text-gray-300">Check-ins</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" wire:click="toggleWebhookSetting({{ $index }}, 'publish_purchases')" {{ !empty($webhook['publish_purchases']) ? 'checked' : '' }}
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-500 focus:ring-indigo-500 dark:bg-gray-700" />
                            <span class="text-gray-700 dark:text-gray-300">Inventory additions</span>
                        </label>
                    </div>
                </div>
            @endforeach

            <div class="space-y-2">
                <div class="flex items-start gap-2">
                    <div class="w-36">
                        <x-input-label for="newWebhookLabel" value="Label" />
                        <input wire:model="newWebhookLabel" id="newWebhookLabel" type="text" placeholder="e.g. #beer-log"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="newWebhookUrl" value="Webhook URL" />
                        <input wire:model="newWebhookUrl" id="newWebhookUrl" type="url" placeholder="https://discord.com/api/webhooks/..."
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                    </div>
                    <div class="shrink-0">
                        <x-input-label class="invisible">&nbsp;</x-input-label>
                        <button type="button" wire:click="addWebhook" class="mt-1 inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-500 text-white text-sm font-medium rounded-md shadow-sm hover:bg-indigo-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Add
                        </button>
                    </div>
                </div>
                <div class="flex items-center gap-4 text-xs">
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input wire:model="newWebhookCheckins" type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-600 text-indigo-500 focus:ring-indigo-500 dark:bg-gray-700" />
                        <span class="text-gray-700 dark:text-gray-300">Auto-publish check-ins</span>
                    </label>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input wire:model="newWebhookPurchases" type="checkbox"
                            class="rounded border-gray-300 dark:border-gray-600 text-indigo-500 focus:ring-indigo-500 dark:bg-gray-700" />
                        <span class="text-gray-700 dark:text-gray-300">Auto-publish inventory</span>
                    </label>
                </div>
                <x-input-error class="mt-1" :messages="$errors->get('newWebhookUrl')" />
            </div>
        </div>
    </div>
</section>
