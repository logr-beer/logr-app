<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Setup extends Component
{
    // Account
    public string $username = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $loadDemoData = false;

    public bool $geocodingEnabled = true;

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

    // Track which fields are locked by env
    public array $envLocked = [];

    public function mount(): void
    {
        $this->detectEnvVars();
    }

    private function detectEnvVars(): void
    {
        if ($value = config('services.untappd.username')) {
            $this->untappd_username = $value;
            $this->envLocked[] = 'untappd_username';
        }

        if ($value = config('services.catalog_beer.key')) {
            $this->catalog_beer_api_key = $value;
            $this->envLocked[] = 'catalog_beer_api_key';
        }

        if ($value = config('services.untappd.api_key')) {
            $this->untappd_api_key = $value;
            $this->envLocked[] = 'untappd_api_key';
        }

        if ($value = config('services.untappd.api_secret')) {
            $this->untappd_api_secret = $value;
            $this->envLocked[] = 'untappd_api_secret';
        }

        if ($value = config('services.untappd.rss_feeds')) {
            $this->rssFeeds = User::parseEnvList($value);
            $this->envLocked[] = 'rss_feeds';
        }

        if ($value = config('services.discord.webhooks')) {
            $this->discordWebhooks = User::parseEnvList($value, [
                'publish_checkins' => true,
                'publish_purchases' => true,
            ]);
            $this->envLocked[] = 'discord_webhooks';
        }
    }

    public function isLocked(string $key): bool
    {
        return in_array($key, $this->envLocked);
    }

    public function hasEnvVars(): bool
    {
        return ! empty($this->envLocked);
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
            'newWebhookUrl' => ['required', 'url', 'max:500', new \App\Rules\DiscordWebhookUrl],
            'newWebhookLabel' => 'nullable|string|max:100',
        ]);

        if (collect($this->discordWebhooks)->contains('url', $this->newWebhookUrl)) {
            $this->addError('newWebhookUrl', 'This webhook URL has already been added.');

            return;
        }

        $this->discordWebhooks[] = [
            'label' => $this->newWebhookLabel ?: null,
            'url' => $this->newWebhookUrl,
            'publish_checkins' => true,
            'publish_purchases' => true,
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

        // Save integrations
        if (trim($this->untappd_username)) {
            $user->setData('untappd_username', trim($this->untappd_username));
        }
        if (trim($this->catalog_beer_api_key)) {
            $user->setData('catalog_beer_api_key', trim($this->catalog_beer_api_key));
        }
        if (trim($this->untappd_api_key)) {
            $user->setData('untappd_client_id', trim($this->untappd_api_key));
        }
        if (trim($this->untappd_api_secret)) {
            $user->setData('untappd_client_secret', trim($this->untappd_api_secret));
        }
        if (! empty($this->rssFeeds)) {
            $user->setData('untappd_rss_feeds', $this->rssFeeds);
        }
        if (! empty($this->discordWebhooks)) {
            $user->setData('discord_webhooks', $this->discordWebhooks);
        }

        $user->save();

        if ($this->loadDemoData) {
            $seeder = new \Database\Seeders\DemoSeeder;
            $seeder->run();
        }

        // Dispatch import jobs if configured
        if ($user->getData('untappd_username')) {
            \App\Jobs\ScrapeUntappdProfile::dispatch($user);
            \App\Jobs\ScrapeUntappdVenues::dispatch($user);
        }
        if ($user->getData('untappd_rss_feeds')) {
            \App\Jobs\SyncUntappdRss::dispatch($user);
        }

        Auth::login($user);

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.setup')
            ->layout('layouts.setup')
            ->title('Setup | '.config('app.name'));
    }
}
