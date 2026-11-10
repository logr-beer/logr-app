<?php

namespace App\Livewire;

use App\Jobs\ScrapeUntappdProfile;
use App\Jobs\ScrapeUntappdVenues;
use App\Jobs\SyncUntappdRss;
use App\Models\User;
use App\Rules\DiscordWebhookUrl;
use App\Services\JsonImportService;
use App\Services\PubBeerDb;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class Setup extends Component
{
    use WithFileUploads;

    // Integration fields that can be set via env, mapped to their config keys
    private const ENV_SECRETS = [
        'catalog_beer_api_key' => 'services.catalog_beer.key',
        'untappd_api_key' => 'services.untappd.api_key',
        'untappd_api_secret' => 'services.untappd.api_secret',
    ];

    public function boot(): void
    {
        if (User::count() > 0) {
            $this->redirect(route('login'), navigate: true);
        }
    }

    // Account
    public string $username = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $loadDemoData = false;

    public $backupFile;

    public array $backupSummary = [];

    public string $themePreference = 'system';

    public bool $connectPub = true;

    public bool $geocodingEnabled = true;

    public bool $shareCheckinData = false;

    // Integrations (editable when not set via env)
    public string $untappd_username = '';

    public string $catalog_beer_api_key = '';

    public string $untappd_api_key = '';

    public string $untappd_api_secret = '';

    public string $newFeedLabel = '';

    public string $newFeedUrl = '';

    public array $rssFeeds = [];

    public string $newWebhookLabel = '';

    public string $newWebhookUrl = '';

    public array $discordWebhooks = [];

    // Track which fields are locked by env (their values stay server-side)
    #[Locked]
    public array $envLocked = [];

    public function mount(): void
    {
        $this->detectEnvVars();
    }

    private function detectEnvVars(): void
    {
        foreach (self::ENV_SECRETS as $key => $configKey) {
            if (config($configKey)) {
                $this->envLocked[] = $key;
            }
        }
    }

    private function integrationValue(string $key): string
    {
        return trim($this->isLocked($key) ? (string) config(self::ENV_SECRETS[$key]) : $this->{$key});
    }

    public function isLocked(string $key): bool
    {
        return in_array($key, $this->envLocked);
    }

    public function hasEnvVars(): bool
    {
        return ! empty($this->envLocked);
    }

    public function updatedBackupFile(): void
    {
        $this->validate([
            'backupFile' => 'required|file|max:51200',
        ]);

        $contents = file_get_contents($this->backupFile->getRealPath());
        $data = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->addError('backupFile', 'Invalid JSON file.');
            $this->backupSummary = [];

            return;
        }

        $this->backupSummary = JsonImportService::preview($data);

        // If we have a backup, disable demo data
        $this->loadDemoData = false;
    }

    public function removeBackup(): void
    {
        $this->backupFile = null;
        $this->backupSummary = [];
    }

    // -- RSS Feeds (only when not env-locked) --

    public function addFeed(): void
    {
        $this->validate([
            'newFeedUrl' => 'required|url|max:500',
            'newFeedLabel' => 'nullable|string|max:100',
        ]);

        if (collect($this->rssFeeds)->contains('url', $this->newFeedUrl)) {
            $this->addError('newFeedUrl', 'This feed URL has already been added.');

            return;
        }

        $this->rssFeeds[] = [
            'label' => $this->newFeedLabel ?: null,
            'url' => $this->newFeedUrl,
        ];

        $this->newFeedLabel = '';
        $this->newFeedUrl = '';
    }

    public function removeFeed(int $index): void
    {
        unset($this->rssFeeds[$index]);
        $this->rssFeeds = array_values($this->rssFeeds);
    }

    // -- Discord Webhooks (only when not env-locked) --

    public function addWebhook(): void
    {
        $this->validate([
            'newWebhookUrl' => ['required', 'url', 'max:500', new DiscordWebhookUrl],
            'newWebhookLabel' => 'nullable|string|max:100',
        ]);

        if (collect($this->discordWebhooks)->contains('url', $this->newWebhookUrl)) {
            $this->addError('newWebhookUrl', 'This webhook URL has already been added.');

            return;
        }

        $this->discordWebhooks[] = [
            'label' => $this->newWebhookLabel ?: null,
            'url' => $this->newWebhookUrl,
            'publish_checkins' => false,
            'publish_purchases' => false,
        ];

        $this->newWebhookLabel = '';
        $this->newWebhookUrl = '';
    }

    public function removeWebhook(int $index): void
    {
        unset($this->discordWebhooks[$index]);
        $this->discordWebhooks = array_values($this->discordWebhooks);
    }

    // -- Create Account --

    public function createAccount(): void
    {
        if (User::count() > 0) {
            $this->redirect(route('dashboard'), navigate: true);

            return;
        }

        $this->validate([
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $this->username,
            'username' => $this->username,
            'password' => Hash::make($this->password),
        ]);

        $user->setData('geocoding_enabled', $this->geocodingEnabled);
        $user->setData('share_checkin_data', $this->shareCheckinData);

        // Save integrations
        if (trim($this->untappd_username)) {
            $user->setData('untappd_username', trim($this->untappd_username));
        }
        if ($value = $this->integrationValue('catalog_beer_api_key')) {
            $user->setData('catalog_beer_api_key', $value);
        }
        if ($value = $this->integrationValue('untappd_api_key')) {
            $user->setData('untappd_client_id', $value);
        }
        if ($value = $this->integrationValue('untappd_api_secret')) {
            $user->setData('untappd_client_secret', $value);
        }
        if (! empty($this->rssFeeds)) {
            $user->setData('untappd_rss_feeds', $this->rssFeeds);
        }
        if (! empty($this->discordWebhooks)) {
            $user->setData('discord_webhooks', $this->discordWebhooks);
        }

        $user->save();

        // Provision a LogrDB API key for beer database search
        if ($this->connectPub) {
            PubBeerDb::provisionKey();
        }

        if ($this->backupFile) {
            Auth::login($user);
            $this->importBackup($user);
        } elseif ($this->loadDemoData) {
            $seeder = new DemoSeeder;
            $seeder->run();
        }

        // Dispatch import jobs if configured
        if ($user->getData('untappd_username')) {
            ScrapeUntappdProfile::dispatch($user);
            ScrapeUntappdVenues::dispatch($user);
        }
        if ($user->getData('untappd_rss_feeds')) {
            SyncUntappdRss::dispatch($user);
        }

        if (! Auth::check()) {
            Auth::login($user);
        }

        $this->redirect(route('dashboard'), navigate: true);
    }

    private function importBackup(User $user): void
    {
        $contents = file_get_contents($this->backupFile->getRealPath());
        $data = json_decode($contents, true);

        if (! $data) {
            return;
        }

        $service = new JsonImportService($user->id);
        $service->import($data);
    }

    public function render()
    {
        return view('livewire.setup')
            ->layout('layouts.setup')
            ->title('Setup | '.config('app.name'));
    }
}
