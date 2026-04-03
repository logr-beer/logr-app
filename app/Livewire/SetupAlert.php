<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SetupAlert extends Component
{
    public bool $dismissed = false;

    public array $missing = [];

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $rssFeeds = $user->getData('untappd_rss_feeds') ?? [];
        $webhooks = $user->getData('discord_webhooks') ?? [];
        $bots = $user->getData('discord_bots') ?? [];

        if (empty($rssFeeds)) {
            $this->missing[] = 'untappd';
        }

        if (empty($webhooks) && empty($bots)) {
            $this->missing[] = 'discord';
        }

        $this->dismissed = session('setup_alert_dismissed', false);
    }

    public function dismiss(): void
    {
        $this->dismissed = true;
        session(['setup_alert_dismissed' => true]);
    }

    public function render()
    {
        return view('livewire.setup-alert');
    }
}
