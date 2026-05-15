<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $pub_secret_key = '';
    public string $untappd_username = '';
    public string $untappd_client_id = '';
    public string $untappd_client_secret = '';
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

    // Test results (per-section)
    public string $testResult = '';
    public string $testStatus = '';
    public string $testSection = '';

    public function mount(): void
    {
        $this->loadFromUser();

        // Exchange claim code for secret key (server-to-server)
        $claimCode = request()->query('pub_claim_code');
        if ($claimCode && !config('app.demo_mode')) {
            $this->exchangeClaimCode($claimCode);
        }
    }

    private function exchangeClaimCode(string $code): void
    {
        $pubUrl = rtrim(config('services.logr.pub_url', ''), '/');
        $apiKey = \App\Models\Setting::get('pub_api_key');

        if (!$pubUrl || !$apiKey) {
            $this->testSection = 'pub';
            $this->testResult = 'No instance API key found.';
            $this->testStatus = 'error';
            return;
        }

        try {
            $http = \Illuminate\Support\Facades\Http::withToken($apiKey)
                ->accept('application/json')
                ->timeout(10);

            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($pubUrl . '/api/claim-exchange', [
                'code' => $code,
            ]);

            if ($response->successful()) {
                $token = $response->json('token');
                if ($token) {
                    $user = Auth::user();
                    $user->setData('pub_secret_key', $token);
                    $user->save();
                    $this->pub_secret_key = $token;

                    $this->testSection = 'pub';
                    $this->testResult = 'Account linked! Your secret key has been saved.';
                    $this->testStatus = 'success';
                    return;
                }
            }

            $this->testSection = 'pub';
            $this->testResult = 'The claim code has expired or is invalid. Please try again.';
            $this->testStatus = 'error';
        } catch (\Exception $e) {
            $this->testSection = 'pub';
            $this->testResult = 'Could not exchange claim code: ' . $e->getMessage();
            $this->testStatus = 'error';
        }
    }

    private function loadFromUser(): void
    {
        if (config('app.demo_mode')) {
            $this->rssFeeds = [];
            return;
        }

        $user = Auth::user();
        $keys = [
            'pub_secret_key', 'untappd_username', 'untappd_client_id', 'untappd_client_secret',
            'catalog_beer_api_key',
        ];
        foreach ($keys as $key) {
            $this->{$key} = (string) ($user->getData($key) ?? '');
        }
        $this->rssFeeds = $user->getData('untappd_rss_feeds') ?? [];
    }

    public function save(): void
    {
        if (config('app.demo_mode')) {
            return;
        }

        $user = Auth::user();

        $strings = [
            'pub_secret_key', 'untappd_username', 'untappd_client_id', 'untappd_client_secret',
            'catalog_beer_api_key',
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

    public function resetPubConnection(): void
    {
        $this->testSection = 'pub';

        // Clear keys and linked account
        \App\Models\Setting::remove('pub_api_key');
        $user = Auth::user();
        $user->setData('pub_secret_key', null);
        $user->save();
        $this->pub_secret_key = '';

        // Provision a fresh instance key
        $token = \App\Services\PubBeerDb::provisionKey();

        if ($token) {
            $this->testResult = 'Connection reset. New instance key provisioned.';
            $this->testStatus = 'success';
        } else {
            $this->testResult = 'Connection cleared, but could not provision a new key. Try "Get API Key" again later.';
            $this->testStatus = 'error';
        }
    }

    public function provisionPubKey(): void
    {
        $this->testSection = 'pub';
        $token = \App\Services\PubBeerDb::provisionKey();

        if ($token) {
            $this->testResult = 'Connected! Beer database search is now active.';
            $this->testStatus = 'success';
        } else {
            $this->testResult = 'Could not get API key. The service may be temporarily unavailable — try again later.';
            $this->testStatus = 'error';
        }
    }

    public function claimPubKey(): void
    {
        $this->testSection = 'pub';
        $pubUrl = rtrim(config('services.logr.pub_url', ''), '/');
        $apiKey = \App\Models\Setting::get('pub_api_key');

        if (!$pubUrl || !$apiKey) {
            $this->testResult = 'No instance API key found. Get an API key first.';
            $this->testStatus = 'error';
            return;
        }

        try {
            $http = \Illuminate\Support\Facades\Http::withToken($apiKey)
                ->accept('application/json')
                ->timeout(10);

            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($pubUrl . '/api/claim-token', [
                'redirect_url' => route('admin.api'),
            ]);

            if ($response->status() === 409) {
                $this->testResult = 'This API key has already been linked to a Logr Pub account.';
                $this->testStatus = 'success';
                return;
            }

            if ($response->successful()) {
                $claimUrl = $response->json('claim_url');
                if ($claimUrl) {
                    $this->redirect($claimUrl);
                    return;
                }
            }

            $this->testResult = 'Could not start claim flow. The service may be temporarily unavailable.';
            $this->testStatus = 'error';
        } catch (\Exception $e) {
            $this->testResult = 'Connection error: ' . $e->getMessage();
            $this->testStatus = 'error';
        }
    }

    public function testUntappd(): void
    {
        $this->testSection = 'untappd';
        if (!$this->untappd_client_id || !$this->untappd_client_secret) {
            $this->testResult = 'Missing Client ID or Secret.';
            $this->testStatus = 'error';
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::accept('application/json')
                ->withHeaders(['User-Agent' => config('logr.user_agent')])
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
        $this->testSection = 'catalog';
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
        $this->syncStatus = 'Syncing ' . count($feeds) . ' feed(s)...';
        \App\Jobs\SyncUntappdRss::dispatch($user);
    }

    public function syncFeed(int $index): void
    {
        $user = Auth::user();
        $feeds = $user->getData('untappd_rss_feeds') ?? [];
        $feed = $feeds[$index] ?? null;
        if (! $feed || empty($feed['url'])) {
            return;
        }

        $label = $feed['label'] ?? 'feed';

        try {
            $rss = app(\App\Services\UntappdRss::class);
            $result = $rss->syncFeed($user, $feed['url']);
            $message = "{$label}: Imported {$result['imported']}, Skipped {$result['skipped']}";
            $this->syncStatus = $message;
            $this->dispatch('toast', message: $message);
        } catch (\Exception $e) {
            $message = "{$label}: " . $e->getMessage();
            $this->syncStatus = $message;
            $this->dispatch('toast', message: $message, type: 'error');
        }
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

@php $demoMode = config('app.demo_mode'); @endphp
<section @if($syncing || $scraping || $scrapingVenues || $geocoding) wire:poll.3s="pollJobStatus" @endif>
    <header>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">API Settings</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Connect external services to enhance your beer library.</p>
    </header>

    <form wire:submit="save" class="mt-6 space-y-6">
        {{-- Logr Pub --}}
        @php
            $pubUrl = config('services.logr.pub_url');
            $instanceToken = \App\Models\Setting::get('pub_api_key');
        @endphp
        <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">Logr Pub</h3>
                @if(env('LOGR_PUB_URL'))
                    <x-env-badge name="LOGR_PUB_URL" />
                @endif
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                The <a href="{{ $pubUrl }}" target="_blank" rel="noopener" class="font-medium underline hover:text-amber-500">Logr Pub</a> beer database powers beer and brewery search with 14k+ breweries and 55k+ beers.
            </p>

            @if($instanceToken)
                <div>
                    <x-input-label value="Instance API Key (read access)" />
                    <div x-data="{ show: false }" class="mt-1">
                        <div class="flex items-center gap-2">
                            <input :type="show ? 'text' : 'password'" value="{{ $instanceToken }}" readonly
                                class="flex-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm font-mono text-sm opacity-80 cursor-default" />
                            <button type="button" @click="show = !show" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors" title="Toggle visibility">
                                <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                            </button>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Read-only access to the Logr Pub beer and brewery database.</p>
                </div>

                <div>
                    <x-input-label for="pub_secret_key" value="Secret Key (write access)" />
                    <div x-data="{ show: false }" class="mt-1">
                        <div class="flex items-center gap-2">
                            <input :type="show ? 'text' : 'password'" wire:model.live="pub_secret_key" id="pub_secret_key" autocomplete="off" {{ $demoMode ? 'disabled' : '' }}
                                class="flex-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm font-mono text-sm {{ $demoMode ? 'opacity-60 cursor-not-allowed' : '' }}" />
                            <button type="button" @click="show = !show" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors" title="Toggle visibility">
                                <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                            </button>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Link your Pub account to contribute beers and breweries you check in back to the community database. Click "Link to Logr Pub Account" below or paste a token manually.</p>
                </div>

                @unless($demoMode)
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="claimPubKey" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-600 text-white text-xs font-medium rounded-lg hover:bg-gray-700 transition-colors">
                            <span wire:loading wire:target="claimPubKey"><svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                            <x-icon name="external-link" size="3" />
                            Link to Logr Pub Account
                        </button>
                        <button type="button" wire:click="resetPubConnection" wire:confirm="This will clear your instance key and secret key, and provision a new one. Continue?" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                            <x-icon name="refresh" size="3" />
                            Reset
                        </button>
                    </div>
                @endunless
            @else
                <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-sm text-amber-800 dark:text-amber-200 space-y-3">
                    <p>Connect to the Logr Pub to enable beer and brewery search.</p>
                    <button type="button" wire:click="provisionPubKey" {{ $demoMode ? 'disabled' : '' }} class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber-600 text-white text-xs font-medium rounded-lg hover:bg-amber-700 transition-colors disabled:opacity-50">
                        <span wire:loading wire:target="provisionPubKey"><svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                        Get API Key
                    </button>
                </div>
            @endif

            @if($testResult && $testSection === 'pub')
                <div class="p-3 rounded-lg text-sm {{ $testStatus === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' }}">
                    {{ $testResult }}
                </div>
            @endif
        </div>

        {{-- Catalog.beer --}}
        <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">Catalog.beer</h3>
            <div>
                <x-input-label for="catalog_beer_api_key" value="API Key" />
                <input wire:model.live="catalog_beer_api_key" id="catalog_beer_api_key" type="password" autocomplete="off" {{ $demoMode ? 'disabled' : '' }}
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm font-mono text-sm {{ $demoMode ? 'opacity-60 cursor-not-allowed' : '' }}" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    An additional beer database for search and auto-submission of new beers. Get a free API key at <a href="https://catalog.beer" target="_blank" rel="noopener" class="font-medium underline hover:text-amber-500">catalog.beer</a>.
                </p>
            </div>
            <button type="button" wire:click="testCatalogBeer" {{ $demoMode ? 'disabled' : '' }} class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-600 text-white text-xs font-medium rounded-lg hover:bg-gray-700 transition-colors disabled:opacity-50">
                <span wire:loading wire:target="testCatalogBeer"><svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                Test Connection
            </button>
            @if($testResult && $testSection === 'catalog')
                <div class="p-3 rounded-lg text-sm {{ $testStatus === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' }}">
                    {{ $testResult }}
                </div>
            @endif
        </div>

        {{-- Untappd --}}
        <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">Untappd</h3>

            {{-- Username --}}
            <div>
                <x-input-label for="untappd_username" value="Username" />
                <input wire:model.live="untappd_username" id="untappd_username" type="text" autocomplete="off" placeholder="e.g. username"
                    {{ $demoMode ? 'disabled' : '' }}
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm {{ $demoMode ? 'opacity-60 cursor-not-allowed' : '' }}" />
            </div>

            @if($untappd_username && !$demoMode)
                <div class="flex flex-wrap items-center gap-3">
                    <x-primary-button type="button" wire:click="scrapeProfile" :disabled="$scraping">
                        @if($scraping) <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> @endif
                        Scrape Public
                    </x-primary-button>
                    <x-primary-button type="button" wire:click="scrapeVenues" :disabled="$scrapingVenues">
                        @if($scrapingVenues) <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> @endif
                        Scrape Venues
                    </x-primary-button>
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
                <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-xs text-amber-700 dark:text-amber-400 space-y-1.5">
                    <p>To find your RSS feed URL:</p>
                    <ol class="list-decimal list-inside space-y-0.5 ml-1">
                        <li>Go to <a href="https://untappd.com/account/settings" target="_blank" rel="noopener" class="font-medium underline hover:text-amber-800 dark:hover:text-amber-300">untappd.com/account/settings</a> and scroll down to "RSS Feed"</li>
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
                        @unless($demoMode)
                            <button type="button" wire:click="syncFeed({{ $index }})" class="shrink-0 p-1.5 text-gray-400 hover:text-amber-500 transition-colors" title="Sync this feed">
                                <x-icon name="refresh" size="4" wire:loading.class="animate-spin" wire:target="syncFeed({{ $index }})" />
                            </button>
                            <button type="button" wire:click="removeFeed({{ $index }})" wire:confirm="Remove this RSS feed?" class="shrink-0 p-1.5 text-gray-400 hover:text-red-500 transition-colors" title="Remove this feed">
                                <x-icon name="trash" size="4" />
                            </button>
                        @endunless
                    </div>
                @endforeach

                @unless($demoMode)
                    <div class="flex items-start gap-2">
                        <div class="w-36">
                            <x-input-label for="newFeedLabel" value="Label" />
                            <input wire:model="newFeedLabel" id="newFeedLabel" type="text" placeholder="e.g. Main"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm" />
                        </div>
                        <div class="flex-1">
                            <x-input-label for="newFeedUrl" value="RSS URL" />
                            <input wire:model="newFeedUrl" id="newFeedUrl" type="url" placeholder="https://untappd.com/rss/user/..."
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm" />
                        </div>
                        <div class="shrink-0">
                            <x-input-label class="invisible">&nbsp;</x-input-label>
                            <x-primary-button type="button" wire:click="addFeed" class="mt-1">
                                <x-icon name="plus" size="4" /> Add
                            </x-primary-button>
                        </div>
                    </div>
                    <x-input-error class="mt-1" :messages="$errors->get('newFeedUrl')" />
                @endunless
            </div>

            {{-- Sync Feeds --}}
            @if(count($rssFeeds) && !$demoMode)
                <div class="flex flex-wrap items-center gap-3">
                    <x-primary-button type="button" wire:click="syncRss" :disabled="$syncing">
                        @if($syncing) <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> @endif
                        Sync All Feeds
                    </x-primary-button>
                </div>
                @if($syncStatus) <p class="text-sm text-green-600 dark:text-green-400">{{ $syncStatus }}</p> @endif
            @endif

            {{-- API Credentials (collapsible) --}}
            <details {{ $untappd_client_id || config('services.untappd.api_key') ? 'open' : '' }}>
                <summary class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer select-none hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                    API Credentials
                    @if(!$untappd_client_id && !$untappd_client_secret && !config('services.untappd.api_key'))
                        <span class="text-gray-500 dark:text-gray-400 normal-case font-normal">&mdash; optional</span>
                    @endif
                </summary>
                <div class="mt-3 space-y-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">If you're lucky enough to have Untappd API credentials, enter them here to enable beer and brewery search via the Untappd database. Untappd has not been accepting new API applications for some time.</p>
                    <div>
                        <div class="flex items-center justify-between">
                            <x-input-label for="untappd_client_id" value="Client ID" />
                            @if(config('services.untappd.api_key'))
                                <x-env-badge name="UNTAPPD_API_KEY" />
                            @endif
                        </div>
                        <input wire:model.live="untappd_client_id" id="untappd_client_id" type="password" autocomplete="off"
                            {{ config('services.untappd.api_key') || $demoMode ? 'disabled' : '' }}
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm font-mono text-sm {{ config('services.untappd.api_key') || $demoMode ? 'opacity-60 cursor-not-allowed' : '' }}" />
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <x-input-label for="untappd_client_secret" value="Client Secret" />
                            @if(config('services.untappd.api_secret'))
                                <x-env-badge name="UNTAPPD_API_SECRET" />
                            @endif
                        </div>
                        <input wire:model.live="untappd_client_secret" id="untappd_client_secret" type="password" autocomplete="off"
                            {{ config('services.untappd.api_secret') || $demoMode ? 'disabled' : '' }}
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm font-mono text-sm {{ config('services.untappd.api_secret') || $demoMode ? 'opacity-60 cursor-not-allowed' : '' }}" />
                    </div>
                    <button type="button" wire:click="testUntappd" {{ $demoMode ? 'disabled' : '' }} class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-600 text-white text-xs font-medium rounded-lg hover:bg-gray-700 transition-colors disabled:opacity-50">
                        <span wire:loading wire:target="testUntappd"><svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></span>
                        Test Connection
                    </button>
                    @if($testResult && $testSection === 'untappd')
                        <div class="p-3 rounded-lg text-sm {{ $testStatus === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' }}">
                            {{ $testResult }}
                        </div>
                    @endif
                </div>
            </details>
        </div>

        {{-- Venues --}}
        <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-wider">Venues</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Geocode venues without coordinates using OpenStreetMap/Nominatim.</p>
            <x-primary-button type="button" wire:click="geocodeVenues" :disabled="$geocoding || $demoMode">
                @if($geocoding) <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> @endif
                Geocode Venues
            </x-primary-button>
            @if($geocodeStatus) <p class="text-sm text-green-600 dark:text-green-400">{{ $geocodeStatus }}</p> @endif
        </div>

        @unless($demoMode)
            <div class="flex items-center gap-4">
                <x-primary-button><x-icon name="check" size="4" /> Save</x-primary-button>
                <x-action-message class="me-3" on="api-settings-updated">Saved.</x-action-message>
            </div>
        @endunless
    </form>
</section>
