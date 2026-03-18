<div class="w-full sm:max-w-lg">
    {{-- Progress Steps --}}
    <div class="flex items-center justify-center gap-3 mb-8">
        @foreach([1 => 'Account', 2 => 'Integrations', 3 => 'Notifications'] as $num => $label)
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                    {{ $step > $num ? 'bg-green-500 text-white' : ($step === $num ? 'bg-amber-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400') }}">
                    @if($step > $num)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    @else
                        {{ $num }}
                    @endif
                </div>
                <span class="text-sm font-medium {{ $step === $num ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500' }} hidden sm:inline">{{ $label }}</span>
            </div>
            @if($num < 3)
                <div class="w-8 h-px {{ $step > $num ? 'bg-green-400' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
            @endif
        @endforeach
    </div>

    {{-- Step 1: Account --}}
    @if($step === 1)
        <div class="bg-white dark:bg-gray-800 shadow-md sm:rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Create Your Account</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Set up your login credentials to get started.</p>

            <form wire:submit="createAccount" class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Display Name</label>
                    <input wire:model="name" id="name" type="text" required autofocus
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                    @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
                    <input wire:model="username" id="username" type="text" required autocomplete="username"
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                    @error('username') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                    <input wire:model="password" id="password" type="password" required autocomplete="new-password"
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                    @error('password') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm Password</label>
                    <input wire:model="password_confirmation" id="password_confirmation" type="password" required autocomplete="new-password"
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                </div>

                <div class="pt-2">
                    <label class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-0.5 cursor-pointer">
                        <input wire:model="loadDemoData" type="checkbox"
                            class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Load demo data</span>
                        <span></span>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Pre-populate with sample beers, breweries, check-ins, and collections.</p>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full px-5 py-2.5 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors">
                        Create Account
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Step 2: API / Integrations --}}
    @if($step === 2)
        <div class="bg-white dark:bg-gray-800 shadow-md sm:rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Integrations</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Connect external services. You can always configure these later in Settings.</p>

            <div class="space-y-6">
                {{-- Catalog.beer --}}
                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg space-y-3">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">Catalog.beer</h3>
                    <div>
                        <label for="catalog_beer_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">API Key</label>
                        <input wire:model="catalog_beer_api_key" id="catalog_beer_api_key" type="text" autocomplete="off"
                            placeholder="Your catalog.beer API key"
                            class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Used for beer search. Get a free key at catalog.beer.</p>
                    </div>
                </div>

                {{-- Untappd --}}
                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg space-y-3">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">Untappd</h3>
                    <div>
                        <label for="untappd_username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
                        <input wire:model="untappd_username" id="untappd_username" type="text" autocomplete="off"
                            placeholder="Your Untappd username"
                            class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Used to scrape your public profile for beers and venues.</p>
                    </div>

                    {{-- RSS Feeds --}}
                    <div class="space-y-3">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">RSS Feeds</h4>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Found at untappd.com &rarr; Account Settings &rarr; RSS Feed.</p>

                        @foreach($rssFeeds as $index => $feed)
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="flex-1 min-w-0">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $feed['label'] ?? 'Untitled Feed' }}</span>
                                    <p class="text-xs text-gray-400 truncate">{{ $feed['url'] }}</p>
                                </div>
                                <button type="button" wire:click="removeFeed({{ $index }})" class="shrink-0 p-1.5 text-gray-400 hover:text-red-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        @endforeach

                        <div class="flex items-end gap-2">
                            <div class="w-28">
                                <label for="newFeedLabel" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Label</label>
                                <input wire:model="newFeedLabel" id="newFeedLabel" type="text" placeholder="Main"
                                    class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                            </div>
                            <div class="flex-1">
                                <label for="newFeedUrl" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">RSS URL</label>
                                <input wire:model="newFeedUrl" id="newFeedUrl" type="url" placeholder="https://untappd.com/rss/user/..."
                                    class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                            </div>
                            <button type="button" wire:click="addFeed" class="px-3 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors">
                                Add
                            </button>
                        </div>
                        @error('newFeedUrl') <p class="text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between mt-6">
                <button type="button" wire:click="skipApiSettings" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                    Skip for now
                </button>
                <button type="button" wire:click="saveApiSettings" class="px-5 py-2.5 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors">
                    Continue
                </button>
            </div>
        </div>
    @endif

    {{-- Step 3: Notifications --}}
    @if($step === 3)
        <div class="bg-white dark:bg-gray-800 shadow-md sm:rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Notifications</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Share check-ins and purchases to Discord. You can always configure these later in Settings.</p>

            <div class="space-y-6">
                {{-- Discord Webhooks --}}
                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg space-y-3">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-indigo-400" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
                        Discord Webhooks
                    </h3>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Server Settings &rarr; Integrations &rarr; Webhooks &rarr; New Webhook.</p>

                    @foreach($discordWebhooks as $index => $webhook)
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg space-y-2">
                            <div class="flex items-center gap-3">
                                <div class="flex-1 min-w-0">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $webhook['label'] ?? 'Untitled Webhook' }}</span>
                                    <p class="text-xs text-gray-400 truncate">{{ $webhook['url'] }}</p>
                                </div>
                                <button type="button" wire:click="removeWebhook({{ $index }})" class="shrink-0 p-1.5 text-gray-400 hover:text-red-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="flex items-center gap-4 text-xs">
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ !empty($webhook['publish_checkins']) ? 'Check-ins' : '' }}{{ !empty($webhook['publish_checkins']) && !empty($webhook['publish_purchases']) ? ' · ' : '' }}{{ !empty($webhook['publish_purchases']) ? 'Inventory' : '' }}
                                </span>
                            </div>
                        </div>
                    @endforeach

                    <div class="space-y-2">
                        <div class="flex items-end gap-2">
                            <div class="w-28">
                                <label for="newWebhookLabel" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Label</label>
                                <input wire:model="newWebhookLabel" id="newWebhookLabel" type="text" placeholder="#beer-log"
                                    class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                            </div>
                            <div class="flex-1">
                                <label for="newWebhookUrl" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Webhook URL</label>
                                <input wire:model="newWebhookUrl" id="newWebhookUrl" type="url" placeholder="https://discord.com/api/webhooks/..."
                                    class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-amber-500 focus:border-amber-500" />
                            </div>
                            <button type="button" wire:click="addWebhook" class="px-3 py-2 bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-600 transition-colors">
                                Add
                            </button>
                        </div>
                        <div class="flex items-center gap-4 text-xs">
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input wire:model="newWebhookCheckins" type="checkbox"
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-500 focus:ring-indigo-500 dark:bg-gray-700" />
                                <span class="text-gray-700 dark:text-gray-300">Check-ins</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input wire:model="newWebhookPurchases" type="checkbox"
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-500 focus:ring-indigo-500 dark:bg-gray-700" />
                                <span class="text-gray-700 dark:text-gray-300">Inventory additions</span>
                            </label>
                        </div>
                        @error('newWebhookUrl') <p class="text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between mt-6">
                <button type="button" wire:click="skipNotifications" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                    Skip for now
                </button>
                <button type="button" wire:click="finishSetup" class="px-5 py-2.5 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors">
                    Finish Setup
                </button>
            </div>
        </div>
    @endif
</div>
