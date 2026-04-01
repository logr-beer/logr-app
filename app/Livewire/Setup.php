<?php

namespace App\Livewire;

use App\Models\User;
use App\Rules\DiscordWebhookUrl;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Setup extends Component
{
    public int $step = 1;
    public ?int $userId = null;

    // Step 1: Account
    public string $name = '';
    public string $username = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $loadDemoData = false;
    public bool $geocodingEnabled = true;

    // Step 2: API Credentials
    public string $catalog_beer_api_key = '';
    public string $untappd_username = '';
    public string $newFeedLabel = '';
    public string $newFeedUrl = '';
    public array $rssFeeds = [];

    // Step 3: Notifications
    public string $newWebhookLabel = '';
    public string $newWebhookUrl = '';
    public bool $newWebhookCheckins = true;
    public bool $newWebhookPurchases = true;
    public array $discordWebhooks = [];

    // -- Step 1: Create Account --

    public function createAccount(): void
    {
        if (User::count() > 0) {
            $this->redirect(route('dashboard'), navigate: true);
            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $this->name,
            'username' => $this->username,
            'password' => Hash::make($this->password),
        ]);

        $user->setData('geocoding_enabled', $this->geocodingEnabled);
        $user->save();

        $this->userId = $user->id;

        if ($this->loadDemoData) {
            $seeder = new \Database\Seeders\DemoSeeder();
            $seeder->run();
        }

        $this->step = 2;
    }

    // -- Step 2: API Credentials --

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

    public function saveApiSettings(): void
    {
        $user = User::find($this->userId);

        if ($user) {
            $user->setData('catalog_beer_api_key', trim($this->catalog_beer_api_key) ?: null);
            $user->setData('untappd_username', trim($this->untappd_username) ?: null);
            $user->setData('untappd_rss_feeds', $this->rssFeeds ?: null);
            $user->save();
        }

        $this->step = 3;
    }

    public function skipApiSettings(): void
    {
        $this->step = 3;
    }

    // -- Step 3: Notifications --

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
            'publish_checkins' => $this->newWebhookCheckins,
            'publish_purchases' => $this->newWebhookPurchases,
        ];

        $this->newWebhookLabel = '';
        $this->newWebhookUrl = '';
        $this->newWebhookCheckins = true;
        $this->newWebhookPurchases = true;
    }

    public function removeWebhook(int $index): void
    {
        unset($this->discordWebhooks[$index]);
        $this->discordWebhooks = array_values($this->discordWebhooks);
    }

    public function finishSetup(): void
    {
        $user = User::find($this->userId);

        if ($user && !empty($this->discordWebhooks)) {
            $user->setData('discord_webhooks', $this->discordWebhooks);
            $user->save();
        }

        $this->loginAndRedirect();
    }

    public function skipNotifications(): void
    {
        $this->loginAndRedirect();
    }

    private function loginAndRedirect(): void
    {
        $user = User::find($this->userId);

        if ($user) {
            Auth::login($user);
        }

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.setup')
            ->layout('layouts.setup')
            ->title('Setup | ' . config('app.name'));
    }
}
