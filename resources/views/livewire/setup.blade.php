<div class="w-full sm:max-w-xl">
    <div class="bg-white dark:bg-gray-800 shadow-md sm:rounded-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Create Your Account</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Set up your login credentials to get started.</p>

        <form wire:submit="createAccount" class="space-y-4">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
                <input wire:model="username" id="username" type="text" required autofocus autocomplete="username"
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

            <div class="pt-2 space-y-3">
                {{-- Theme preference --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Theme</label>
                    <div x-data="{ theme: '{{ $themePreference }}' }" class="flex gap-2">
                        @foreach (['system' => 'System', 'light' => 'Light', 'dark' => 'Dark'] as $value => $label)
                            <button type="button"
                                wire:model.live="themePreference"
                                @click="
                                    theme = '{{ $value }}';
                                    $wire.set('themePreference', '{{ $value }}');
                                    if ('{{ $value }}' === 'system') {
                                        localStorage.removeItem('theme');
                                        document.documentElement.classList.toggle('dark', matchMedia('(prefers-color-scheme: dark)').matches);
                                    } else {
                                        localStorage.setItem('theme', '{{ $value }}');
                                        document.documentElement.classList.toggle('dark', '{{ $value }}' === 'dark');
                                    }
                                "
                                :class="theme === '{{ $value }}' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-amber-400'"
                                class="flex-1 px-3 py-2 text-sm font-medium rounded-lg border transition-colors text-center"
                            >{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- LogrDB --}}
                <label class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-0.5 cursor-pointer">
                    <input wire:model="connectPub" type="checkbox"
                        class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Connect to LogrDB beer database</span>
                    <span></span>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Get a free read-only API key to search 14k+ breweries and 55k+ beers when adding check-ins or new beers. No account required.</p>
                </label>

                <div>
                    <label class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-0.5 cursor-pointer">
                        <input wire:model="geocodingEnabled" type="checkbox"
                            class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable location geocoding</span>
                        <span></span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Automatically look up coordinates for breweries and venues using OpenStreetMap's Nominatim API. This sends city/state data to an external service.</p>
                    </label>
                    <div x-data="{ open: false }" class="ml-6 mt-1">
                        <button type="button" @click="open = !open" class="text-xs text-amber-500 hover:text-amber-700 dark:hover:text-amber-400 transition-colors">
                            <span x-text="open ? 'Hide example payload' : 'Show example payload'"></span>
                        </button>
                        <pre x-show="open" x-collapse class="mt-1.5 p-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-600 dark:text-gray-400 font-mono overflow-x-auto">{
  "query": "Bell's Brewery, Comstock, MI",
  "format": "json"
}</pre>
                    </div>
                </div>

                <div>
                    <label class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-0.5 cursor-pointer">
                        <input wire:model="shareCheckinData" type="checkbox"
                            class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Share anonymous check-in data</span>
                        <span></span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">When you check in a beer, share the beer name, brewery, style, ABV, IBU, rating, and serving type with the Logr community. No personal information, reviews, or venue data is shared.</p>
                    </label>
                    <div x-data="{ open: false }" class="ml-6 mt-1">
                        <button type="button" @click="open = !open" class="text-xs text-amber-500 hover:text-amber-700 dark:hover:text-amber-400 transition-colors">
                            <span x-text="open ? 'Hide example payload' : 'Show example payload'"></span>
                        </button>
                        <pre x-show="open" x-collapse class="mt-1.5 p-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-600 dark:text-gray-400 font-mono overflow-x-auto">{
  "beer_name": "Two Hearted Ale",
  "brewery": "Bell's Brewery",
  "style": "American IPA",
  "abv": 7.0,
  "ibu": 55,
  "rating": 4.5,
  "serving_type": "draft",
  "catalog_beer_id": "abc123",
  "checked_in_at": "2026-05-11T18:30:00+00:00"
}</pre>
                    </div>
                </div>

                {{-- Demo data --}}
                @if (count($backupSummary) === 0)
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-3 mt-1">
                        <label class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-0.5 cursor-pointer">
                            <input wire:model="loadDemoData" type="checkbox"
                                class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500 dark:bg-gray-700" />
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Load sample data</span>
                            <span></span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">New to Logr? Start with a curated set of beers, breweries, check-ins, and collections so you can explore the app right away.</p>
                        </label>
                    </div>
                @endif

                {{-- Restore from backup --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-3 mt-1">
                    @if (count($backupSummary) > 0)
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-green-700 dark:text-green-400">Backup loaded</span>
                                <button type="button" wire:click="removeBackup" class="text-xs text-gray-500 hover:text-red-500 transition-colors">Remove</button>
                            </div>
                            @if ($backupSummary['exported_at'])
                                <p class="text-xs text-green-600 dark:text-green-500">
                                    Exported {{ \Carbon\Carbon::parse($backupSummary['exported_at'])->diffForHumans() }}
                                    (v{{ $backupSummary['version'] }})
                                </p>
                            @endif
                            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-green-600 dark:text-green-500">
                                @foreach (['beers', 'breweries', 'checkins', 'inventory', 'venues', 'stores', 'collections', 'tags'] as $section)
                                    @if (($backupSummary[$section] ?? 0) > 0)
                                        <span>{{ $backupSummary[$section] }} {{ $section }}</span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @else
                        <details>
                            <summary class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer select-none hover:text-amber-500 transition-colors">Restore from backup</summary>
                            <div class="mt-2">
                                <input type="file" wire:model="backupFile" accept=".json"
                                    class="block w-full text-sm text-gray-500 dark:text-gray-400
                                        file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0
                                        file:text-sm file:font-medium
                                        file:bg-gray-200 dark:file:bg-gray-600 file:text-gray-700 dark:file:text-gray-200
                                        hover:file:bg-gray-300 dark:hover:file:bg-gray-500" />
                                @error('backupFile') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                                <div wire:loading wire:target="backupFile" class="text-xs text-gray-500 dark:text-gray-400 mt-1">Reading file...</div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Upload a <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded font-mono">logr-export-*.json</code> file from a previous Logr installation.</p>
                            </div>
                        </details>
                    @endif
                </div>
            </div>

            {{-- Integrations Section --}}
            @if($this->hasEnvVars())
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Detected Environment Configuration</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">These values were set in your <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">.env</code> file and will be applied to your account.</p>

                    <div class="space-y-3">
                        @if($this->isLocked('untappd_username'))
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Untappd Username</label>
                                    <x-env-badge name="UNTAPPD_USERNAME" />
                                </div>
                                <input type="text" value="{{ $untappd_username }}" disabled
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-500 dark:text-gray-400 cursor-not-allowed" />
                            </div>
                        @endif

                        @if($this->isLocked('catalog_beer_api_key'))
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Catalog.beer API Key</label>
                                    <x-env-badge name="CATALOG_BEER_API_KEY" />
                                </div>
                                <input type="text" value="{{ Str::mask($catalog_beer_api_key, '*', 4) }}" disabled
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-500 dark:text-gray-400 cursor-not-allowed font-mono" />
                            </div>
                        @endif

                        @if($this->isLocked('untappd_api_key'))
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Untappd API Key</label>
                                    <x-env-badge name="UNTAPPD_API_KEY" />
                                </div>
                                <input type="text" value="{{ Str::mask($untappd_api_key, '*', 4) }}" disabled
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-500 dark:text-gray-400 cursor-not-allowed font-mono" />
                            </div>
                        @endif

                        @if($this->isLocked('untappd_api_secret'))
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Untappd API Secret</label>
                                    <x-env-badge name="UNTAPPD_API_SECRET" />
                                </div>
                                <input type="text" value="{{ Str::mask($untappd_api_secret, '*', 4) }}" disabled
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-500 dark:text-gray-400 cursor-not-allowed font-mono" />
                            </div>
                        @endif

                        @if($this->isLocked('rss_feeds'))
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Untappd RSS Feeds</label>
                                    <x-env-badge name="UNTAPPD_RSS_FEEDS" />
                                </div>
                                @foreach($rssFeeds as $feed)
                                    <div class="flex items-center gap-3 p-2.5 bg-gray-50 dark:bg-gray-700/50 rounded-lg mt-1.5">
                                        <div class="flex-1 min-w-0">
                                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ $feed['label'] ?? 'Untitled Feed' }}</span>
                                            <p class="text-xs text-gray-400 truncate">{{ $feed['url'] }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($this->isLocked('discord_webhooks'))
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Discord Webhooks</label>
                                    <x-env-badge name="DISCORD_WEBHOOKS" />
                                </div>
                                @foreach($discordWebhooks as $webhook)
                                    <div class="flex items-center gap-3 p-2.5 bg-gray-50 dark:bg-gray-700/50 rounded-lg mt-1.5">
                                        <div class="flex-1 min-w-0">
                                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ $webhook['label'] ?? 'Untitled Webhook' }}</span>
                                            <p class="text-xs text-gray-400 truncate">{{ $webhook['url'] }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="pt-2">
                <x-primary-button :full="true" size="lg">Create Account</x-primary-button>
            </div>
        </form>
    </div>
</div>
