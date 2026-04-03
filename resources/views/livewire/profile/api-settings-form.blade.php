<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $untappd_username = '';
    public string $untappd_client_id = '';
    public string $untappd_client_secret = '';
    public string $logr_db_token = '';
    public string $catalog_beer_api_key = '';
    public array $rssFeeds = [];
    public string $newFeedLabel = '';
    public string $newFeedUrl = '';

    // Job status
    public string $syncStatus = '';
    public bool $syncing = false;
    public string $scrapeStatus = '';
    public bool $scraping = false;
    public string $venueScrapeStatus = '';
    public bool $scrapingVenues = false;
    public string $geocodeStatus = '';
    public bool $geocoding = false;

    // Test results
    public string $testResult = '';
    public string $testStatus = '';

    public function mount(): void
    {
        $this->loadFromUser();
    }

    private function loadFromUser(): void
    {
        $user = Auth::user();
        $keys = [
            'untappd_username', 'untappd_client_id', 'untappd_client_secret',
            'logr_db_token', 'catalog_beer_api_key',
        ];
        foreach ($keys as $key) {
            $this->{$key} = (string) ($user->getData($key) ?? '');
        }
        $this->rssFeeds = $user->getData('untappd_rss_feeds') ?? [];
    }

    public function save(): void
    {
        $user = Auth::user();

        $strings = [
            'untappd_username', 'untappd_client_id', 'untappd_client_secret',
            'logr_db_token', 'catalog_beer_api_key',
        ];
        foreach ($strings as $key) {
            $user->setData($key, trim($this->{$key}) ?: null);
        }
        $user->save();

        $this->dispatch('api-settings-updated');
    }

    public function addFeed(): void
    {
        $this->validate([
            'newFeedUrl' => 'required|url|max:500',
            'newFeedLabel' => 'nullable|string|max:100',
        ]);

        $exists = collect($this->rssFeeds)->contains('url', $this->newFeedUrl);
        if ($exists) {
            $this->addError('newFeedUrl', 'This feed URL has already been added.');
            return;
        }

        $this->rssFeeds[] = [
            'label' => $this->newFeedLabel ?: null,
            'url' => $this->newFeedUrl,
        ];

        $user = Auth::user();
        $user->setData('untappd_rss_feeds', $this->rssFeeds);
        $user->save();

        $this->newFeedLabel = '';
        $this->newFeedUrl = '';
    }

    public function removeFeed(int $index): void
    {
        unset($this->rssFeeds[$index]);
        $this->rssFeeds = array_values($this->rssFeeds);

        $user = Auth::user();
        $user->setData('untappd_rss_feeds', $this->rssFeeds ?: null);
        $user->save();
    }

    public function testLogrDb(): void
    {
        $url = config('services.logr_db.url');
        $token = $this->logr_db_token;

        if (!$url || !$token) {
            $this->testResult = 'Missing URL or token.';
            $this->testStatus = 'error';
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->accept('application/json')
                ->withoutVerifying()
                ->timeout(10)
                ->get(rtrim($url, '/') . '/api/beers', ['per_page' => 1]);

            if ($response->successful()) {
                $data = $response->json();
                $total = $data['meta']['total'] ?? $data['total'] ?? count($data['data'] ?? []);
                $this->testResult = "Connected! {$total} result(s).";
                $this->testStatus = 'success';
            } else {
                $this->testResult = "HTTP {$response->status()}";
                $this->testStatus = 'error';
            }
        } catch (\Exception $e) {
            $this->testResult = $e->getMessage();
            $this->testStatus = 'error';
        }
    }

    public function testUntappd(): void
    {
        if (!$this->untappd_client_id || !$this->untappd_client_secret) {
            $this->testResult = 'Missing Client ID or Secret.';
            $this->testStatus = 'error';
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::accept('application/json')
                ->withHeaders(['User-Agent' => 'Logr/1.0'])
                ->timeout(10)
                ->get('https://api.untappd.com/v4/search/beer', [
                    'client_id' => $this->untappd_client_id,
                    'client_secret' => $this->untappd_client_secret,
                    'q' => 'test',
                    'limit' => 1,
                ]);

            if ($response->successful()) {
                $remaining = $response->json('response.rate_limit_remaining', '?');
                $this->testResult = "Connected! Rate limit remaining: {$remaining}.";
                $this->testStatus = 'success';
            } else {
                $this->testResult = "HTTP {$response->status()}: " . ($response->json('meta.error_detail') ?? $response->body());
                $this->testStatus = 'error';
            }
        } catch (\Exception $e) {
            $this->testResult = $e->getMessage();
            $this->testStatus = 'error';
        }
    }

    public function testCatalogBeer(): void
    {
        if (!$this->catalog_beer_api_key) {
            $this->testResult = 'Missing API key.';
            $this->testStatus = 'error';
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withBasicAuth($this->catalog_beer_api_key, '')
                ->accept('application/json')
                ->timeout(10)
                ->get('https://api.catalog.beer/beer/search', ['q' => 'test', 'count' => 1]);

            if ($response->successful()) {
                $this->testResult = 'Connected!';
                $this->testStatus = 'success';
            } else {
                $this->testResult = "HTTP {$response->status()}";
                $this->testStatus = 'error';
            }
        } catch (\Exception $e) {
            $this->testResult = $e->getMessage();
            $this->testStatus = 'error';
        }
    }

    public function syncRss(): void
    {
        $user = Auth::user();
        $feeds = $user->getData('untappd_rss_feeds') ?? [];
        if (empty($feeds)) {
            $this->syncStatus = 'No RSS feeds configured.';
            return;
        }
        $this->syncing = true;
        $this->syncStatus = 'Queued...';
        \App\Jobs\SyncUntappdRss::dispatch($user);
    }

    public function scrapeProfile(): void
    {
        $user = Auth::user();
        if (!$user->untappd_username) {
            $this->scrapeStatus = 'No username configured.';
            return;
        }
        $this->scraping = true;
        $this->scrapeStatus = 'Queued...';
        \App\Jobs\ScrapeUntappdProfile::dispatch($user);
    }

    public function scrapeVenues(): void
    {
        $user = Auth::user();
        if (!$user->untappd_username) {
            $this->venueScrapeStatus = 'No username configured.';
            return;
        }
        $this->scrapingVenues = true;
        $this->venueScrapeStatus = 'Queued...';
        \App\Jobs\ScrapeUntappdVenues::dispatch($user);
    }

    public function geocodeVenues(): void
    {
        $pending = \App\Models\Venue::whereNull('latitude')->count();
        if ($pending === 0) {
            $this->geocodeStatus = 'All venues already have coordinates.';
            return;
        }
        $this->geocoding = true;
        $this->geocodeStatus = "Geocoding {$pending} venue(s)...";
        \App\Jobs\GeocodeVenues::dispatch();
    }

    public function pollJobStatus(): void
    {
        $userId = Auth::id();

        if ($this->syncing) {
            $status = \Illuminate\Support\Facades\Cache::get("rss_status_{$userId}");
            if ($status) {
                $this->syncStatus = $status['message'];
                if ($status['status'] !== 'running') {
                    $this->syncing = false;
                    \Illuminate\Support\Facades\Cache::forget("rss_status_{$userId}");
                }
            }
        }

        if ($this->scraping) {
            $status = \Illuminate\Support\Facades\Cache::get("scrape_status_{$userId}");
            if ($status) {
                $this->scrapeStatus = $status['message'];
                if ($status['status'] !== 'running') {
                    $this->scraping = false;
                    \Illuminate\Support\Facades\Cache::forget("scrape_status_{$userId}");
                }
            }
        }

        if ($this->scrapingVenues) {
            $status = \Illuminate\Support\Facades\Cache::get("venue_scrape_status_{$userId}");
            if ($status) {
                $this->venueScrapeStatus = $status['message'];
                if ($status['status'] !== 'running') {
                    $this->scrapingVenues = false;
                    \Illuminate\Support\Facades\Cache::forget("venue_scrape_status_{$userId}");
                }
            }
        }

        if ($this->geocoding) {
            $status = \Illuminate\Support\Facades\Cache::get('geocode_status');
            if ($status) {
                $this->geocodeStatus = $status['message'];
                if ($status['status'] !== 'running') {
                    $this->geocoding = false;
                    \Illuminate\Support\Facades\Cache::forget('geocode_status');
                }
            }
        }
    }
}; ?>

<section @if($syncing || $scraping || $scrapingVenues || $geocoding) wire:poll.3s="pollJobStatus" @endif>
    <header>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">API Settings</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Connect external services to enhance your beer library.</p>
    </header>

    @if($testResult)
        <div class="mt-4 p-3 rounded-lg text-sm {{ $testStatus === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' }}">
            {{ $testResult }}
        </div>
    @endif

    <form wire:submit="save" class="mt-6 space-y-6">
        {{-- Logr DB --}}
        @if(config('services.logr_db.url'))
            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">Logr DB</h3>
                    <x-env-badge name="LOGR_DB_URL" />
                </div>
                <div>
                    <x-input-label for="logr_db_token" value="API Token" />
                    <input wire:model.live="logr_db_token" id="logr_db_token" type="text" autocomplete="off"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Bearer token for the Logr DB API.</p>
                </div>
                <button type="button" wire:click="testLogrDb" class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-600 text-white text-xs font-medium rounded-lg hover:bg-gray-700 transition-colors">
                    <span wire:loading wire:target="testLogrDb"><svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                    Test Connection
                </button>
            </div>
        @endif

        {{-- Catalog.beer --}}
        <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">Catalog.beer</h3>
                @if(config('services.catalog_beer.key'))
                    <x-env-badge name="CATALOG_BEER_API_KEY" />
                @endif
            </div>
            <div>
                <x-input-label for="catalog_beer_api_key" value="API Key" />
                <input wire:model.live="catalog_beer_api_key" id="catalog_beer_api_key" type="text" autocomplete="off"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Get a free API key at <span class="font-medium">catalog.beer</span>.
                    @if(config('services.catalog_beer.key'))
                        Default provided by environment variable.
                    @endif
                </p>
            </div>
            <button type="button" wire:click="testCatalogBeer" class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-600 text-white text-xs font-medium rounded-lg hover:bg-gray-700 transition-colors">
                <span wire:loading wire:target="testCatalogBeer"><svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                Test Connection
            </button>
        </div>

        {{-- Untappd --}}
        <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">Untappd</h3>

            {{-- Username --}}
            <div>
                <div class="flex items-center justify-between">
                    <x-input-label for="untappd_username" value="Username" />
                    @if(config('services.untappd.username'))
                        <x-env-badge name="UNTAPPD_USERNAME" />
                    @endif
                </div>
                <input wire:model.live="untappd_username" id="untappd_username" type="text" autocomplete="off" placeholder="e.g. ajp"
                    {{ config('services.untappd.username') ? 'disabled' : '' }}
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm {{ config('services.untappd.username') ? 'opacity-60 cursor-not-allowed' : '' }}" />
            </div>

            @if($untappd_username)
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" wire:click="scrapeProfile" {{ $scraping ? 'disabled' : '' }} class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors disabled:opacity-50">
                        @if($scraping) <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> @endif
                        Scrape Public
                    </button>
                    <button type="button" wire:click="scrapeVenues" {{ $scrapingVenues ? 'disabled' : '' }} class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors disabled:opacity-50">
                        @if($scrapingVenues) <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> @endif
                        Scrape Venues
                    </button>
                </div>
                @if($scrapeStatus) <p class="text-sm text-green-600 dark:text-green-400">{{ $scrapeStatus }}</p> @endif
                @if($venueScrapeStatus) <p class="text-sm text-green-600 dark:text-green-400">{{ $venueScrapeStatus }}</p> @endif
            @endif

            {{-- RSS Feeds --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">RSS Feeds</h4>
                    @if(config('services.untappd.rss_feeds'))
                        <x-env-badge name="UNTAPPD_RSS_FEEDS" />
                    @endif
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-xs text-blue-700 dark:text-blue-400 space-y-1.5">
                    <p>To find your RSS feed URL:</p>
                    <ol class="list-decimal list-inside space-y-0.5 ml-1">
                        <li>Go to <a href="https://untappd.com/account/settings" target="_blank" rel="noopener" class="font-medium underline hover:text-blue-800 dark:hover:text-blue-300">untappd.com/account/settings</a> and scroll down to "RSS Feed"</li>
                        <li>Copy the RSS feed URL shown on the page</li>
                    </ol>
                    <p class="pt-1">For the <strong>Label</strong>, use something descriptive like your Untappd display name or "Main" if you only have one feed.</p>
                </div>

                @foreach($rssFeeds as $index => $feed)
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $feed['label'] ?? 'Untitled Feed' }}</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $feed['url'] }}</p>
                        </div>
                        <button type="button" wire:click="removeFeed({{ $index }})" wire:confirm="Remove this RSS feed?" class="shrink-0 p-1.5 text-gray-400 hover:text-red-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        </button>
                    </div>
                @endforeach

                <div class="flex items-start gap-2">
                    <div class="w-36">
                        <x-input-label for="newFeedLabel" value="Label" />
                        <input wire:model="newFeedLabel" id="newFeedLabel" type="text" placeholder="e.g. Main"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="newFeedUrl" value="RSS URL" />
                        <input wire:model="newFeedUrl" id="newFeedUrl" type="url" placeholder="https://untappd.com/rss/user/..."
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                    </div>
                    <div class="shrink-0">
                        <x-input-label class="invisible">&nbsp;</x-input-label>
                        <button type="button" wire:click="addFeed" class="mt-1 inline-flex items-center gap-1.5 px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-md shadow-sm hover:bg-amber-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Add
                        </button>
                    </div>
                </div>
                <x-input-error class="mt-1" :messages="$errors->get('newFeedUrl')" />
            </div>

            {{-- Sync Feeds --}}
            @if(count($rssFeeds))
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" wire:click="syncRss" {{ $syncing ? 'disabled' : '' }} class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors disabled:opacity-50">
                        @if($syncing) <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> @endif
                        Sync All Feeds
                    </button>
                </div>
                @if($syncStatus) <p class="text-sm text-green-600 dark:text-green-400">{{ $syncStatus }}</p> @endif
            @endif

            {{-- API Credentials (collapsible) --}}
            <details {{ $untappd_client_id || config('services.untappd.api_key') ? 'open' : '' }}>
                <summary class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer select-none hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                    API Credentials
                    @if(!$untappd_client_id && !$untappd_client_secret && !config('services.untappd.api_key'))
                        <span class="text-gray-400 dark:text-gray-500 normal-case font-normal">&mdash; optional</span>
                    @endif
                </summary>
                <div class="mt-3 space-y-4">
                    <div>
                        <div class="flex items-center justify-between">
                            <x-input-label for="untappd_client_id" value="Client ID" />
                            @if(config('services.untappd.api_key'))
                                <x-env-badge name="UNTAPPD_API_KEY" />
                            @endif
                        </div>
                        <input wire:model.live="untappd_client_id" id="untappd_client_id" type="text" autocomplete="off"
                            {{ config('services.untappd.api_key') ? 'disabled' : '' }}
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm {{ config('services.untappd.api_key') ? 'opacity-60 cursor-not-allowed' : '' }}" />
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <x-input-label for="untappd_client_secret" value="Client Secret" />
                            @if(config('services.untappd.api_secret'))
                                <x-env-badge name="UNTAPPD_API_SECRET" />
                            @endif
                        </div>
                        <input wire:model.live="untappd_client_secret" id="untappd_client_secret" type="text" autocomplete="off"
                            {{ config('services.untappd.api_secret') ? 'disabled' : '' }}
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm {{ config('services.untappd.api_secret') ? 'opacity-60 cursor-not-allowed' : '' }}" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Register at <span class="font-medium">untappd.com/api/docs</span>.</p>
                    </div>
                    <button type="button" wire:click="testUntappd" class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-600 text-white text-xs font-medium rounded-lg hover:bg-gray-700 transition-colors">
                        <span wire:loading wire:target="testUntappd"><svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                        Test Connection
                    </button>
                </div>
            </details>
        </div>

        {{-- Venues --}}
        <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">Venues</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Geocode venues without coordinates using OpenStreetMap/Nominatim.</p>
            <button type="button" wire:click="geocodeVenues" {{ $geocoding ? 'disabled' : '' }} class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition-colors disabled:opacity-50">
                @if($geocoding) <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> @endif
                Geocode Venues
            </button>
            @if($geocodeStatus) <p class="text-sm text-green-600 dark:text-green-400">{{ $geocodeStatus }}</p> @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Save</x-primary-button>
            <x-action-message class="me-3" on="api-settings-updated">Saved.</x-action-message>
        </div>
    </form>
</section>
